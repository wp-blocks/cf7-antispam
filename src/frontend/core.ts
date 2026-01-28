/**
 * Internal dependencies
 */
import {
	browserFingerprint,
	getBrowserLanguage,
	setupMouseActivityTest,
	setupMouseMovementTest,
	setupWebGLTest,
	setupCanvasTest,
	setupLanguageTest,
	setupBotFingerprintTest,
} from './tests';
import { createCF7Afield, setTimestamp, randomString } from './utils';

// eslint-disable-next-line camelcase
declare const cf7a_settings: {
	prefix: string;
	version: string;
	restUrl: string;
};

/**
 * Process a single CF7 form
 *
 * @param {HTMLFormElement} wpcf7Form The Contact form to process
 */
function processCF7Form(wpcf7Form: HTMLFormElement): void {
	if (!window.wpcf7) {
		return;
	}

	// eslint-disable-next-line camelcase
	const { prefix, version, restUrl } = cf7a_settings;

	const hiddenInputsContainer = (wpcf7Form.querySelector(
		'form > .hidden-fields-container'
	) ??
		wpcf7Form.querySelector('form > div') ??
		null) as HTMLElement | null;

	if (!hiddenInputsContainer) {
		// eslint-disable-next-line no-console
		console.error('CF7 Antispam: hidden-fields-container not found');
		return;
	}

	// Check if the form is already processed
	const alreadyProcessed = hiddenInputsContainer.querySelector(
		'input[name=' + prefix + 'processed]'
	);
	if (alreadyProcessed) {
		return; // Skip if already processed
	}

	// Mark form as processed
	hiddenInputsContainer.appendChild(createCF7Afield('processed', '1'));

	// Get the fake field and skip it
	if (wpcf7Form.querySelector('form')?.getAttribute('autocomplete')) {
		return;
	}

	// Hash Field (With Cache Compatibility)
	const cf7aHashInput = hiddenInputsContainer.querySelector(
		'input[name=' + prefix + 'hash]'
	) as HTMLInputElement | null;
	if (cf7aHashInput && !cf7aHashInput.value) {
		cf7aHashInput.setAttribute('value', randomString());
	}

	// Timestamp Field (With Cache Compatibility)
	const tsInput = hiddenInputsContainer.querySelector(
		'input[name=' + prefix + '_timestamp]'
	) as HTMLInputElement | null;
	// We should replace the initial timestamp with a new one since it could be cached
	if (tsInput) {
		setTimestamp(tsInput, restUrl);
	}

	// Set the cf7 antispam version field
	const cf7aVersionInput = hiddenInputsContainer.querySelector(
		'input[name=' + prefix + 'version]'
	) as HTMLInputElement | null;

	if (cf7aVersionInput) {
		cf7aVersionInput?.setAttribute('value', version);
	}

	// Get browser fingerprint data
	const tests = browserFingerprint();

	// 1) Standard bot checks
	setupBotFingerprintTest(hiddenInputsContainer, prefix, tests, wpcf7Form);

	// 2) Bot fingerprint extra checks
	if (
		hiddenInputsContainer.querySelector(
			'input[name=' + prefix + 'bot_fingerprint_extras]'
		)
	) {
		// 2.1) Mouse activity test
		setupMouseActivityTest(hiddenInputsContainer, prefix);

		// 2.2) Mouse movement test
		setupMouseMovementTest(hiddenInputsContainer, tests);

		// 2.3) WebGL Tests
		setupWebGLTest(hiddenInputsContainer);

		// 2.4) Canvas Tests
		setupCanvasTest(hiddenInputsContainer);
	}

	// 3) Language check
	const languageChecksEnabled = hiddenInputsContainer.querySelector(
		'input[name=' + prefix + '_language]'
	);

	if (languageChecksEnabled) {
		setupLanguageTest(hiddenInputsContainer, getBrowserLanguage);
	}
}

/**
 * Process all existing CF7 forms on the page
 */
export function processExistingForms(): void {
	const wpcf7Forms = document.querySelectorAll(
		'.wpcf7'
	) as NodeListOf<HTMLFormElement>;

	if (wpcf7Forms.length) {
		for (const wpcf7Form of wpcf7Forms) {
			processCF7Form(wpcf7Form);
		}
	}
}

/**
 * Setup MutationObserver to watch for dynamically added forms
 */
export function setupMutationObserver(): void {
	// Check if MutationObserver is supported
	if (typeof MutationObserver === 'undefined') {
		// eslint-disable-next-line no-console
		console.warn('CF7 Antispam: MutationObserver not supported');
		return;
	}

	const observer = new MutationObserver((mutations) => {
		mutations.forEach((mutation) => {
			if (mutation.type === 'childList') {
				mutation.addedNodes.forEach((node) => {
					if (node.nodeType === Node.ELEMENT_NODE) {
						const element = node as Element;

						// Check if the added node is a CF7 form
						if (element.classList.contains('wpcf7')) {
							processCF7Form(element as HTMLFormElement);
						}

						// Check if the added node contains CF7 forms
						const cf7Forms = element.querySelectorAll('.wpcf7');
						cf7Forms.forEach((form) => {
							processCF7Form(form as HTMLFormElement);
						});
					}
				});
			}
		});
	});

	// Start observing the document body for changes
	observer.observe(document.body, {
		childList: true,
		subtree: true,
	});
}
