/* eslint-disable camelcase */
/* global cf7a_settings, wpcf7 */

/**
 * Script Types declaration
 */
declare global {
	interface Window {
		wpcf7: () => void;
		canvasCount: number;
	}
	interface String {
		hashCode(): number;
	}
}

declare let wpcf7: {
	cached: boolean;
};

declare let cf7a_settings: {
	prefix: string;
	version: string;
	disableReload: string;
};

// Extend navigator to support msMaxTouchPoints and deviceMemory
interface ExtendedNavigator extends Navigator {
	msMaxTouchPoints?: number;
	deviceMemory?: number;
}

interface Tests {
	timezone: string | null;
	platform: string | null;
	screens: number[] | null;
	memory: number | null;
	user_agent: string | null;
	app_version: string | null;
	webdriver: boolean | null;
	session_storage: number | null;
	touch?: boolean | null;
	isFFox?: boolean | null;
	isSamsung?: boolean | null;
	isOpera?: boolean | null;
	isIE?: boolean | null;
	isIELegacy?: boolean | null;
	isEdge?: boolean | null;
	isChrome?: boolean | null;
	isSafari?: boolean | null;
	isUnknown?: boolean | null;
	isIos?: boolean | null;
	isAndroid?: boolean | null;
}

/**
 * Wait for the document to be ready and then execute the function
 *
 * @param fn the function to execute
 */
function ready(fn: { (): void }): void {
	if (document.readyState !== 'loading') {
		fn();
		return;
	}
	document.addEventListener('DOMContentLoaded', fn);
}

/**
 * Process a single CF7 form
 * @param wpcf7Form
 */
function processCF7Form(wpcf7Form: HTMLFormElement): void {
	if (!window.wpcf7) {
		return;
	}

	const cf7aPrefix: string = cf7a_settings.prefix;
	const cf7aVersion: string = cf7a_settings.version;

	let oldy = 0,
		mouseMoveValue = 0,
		mouseActivityValue = 0;

	const hiddenInputsContainer =
		wpcf7Form.querySelector('form > .hidden-fields-container') ??
		wpcf7Form.querySelector('form > div') ??
		null;

	if (!hiddenInputsContainer) {
		console.error('CF7 Antispam: hidden-fields-container not found');
		return;
	}

	// Check if form is already processed
	const alreadyProcessed = hiddenInputsContainer.querySelector(
		'input[name=' + cf7aPrefix + 'processed]'
	);
	if (alreadyProcessed) {
		return; // Skip if already processed
	}

	// Mark form as processed
	hiddenInputsContainer.appendChild(createCF7Afield('processed', '1'));

	// 1) Standard bot checks
	const botFingerprintKey = hiddenInputsContainer.querySelector(
		'input[name=' + cf7aPrefix + 'bot_fingerprint]'
	);

	// 2) Bot fingerprint extra checks
	const botFingerprintExtra = hiddenInputsContainer.querySelector(
		'input[name=' + cf7aPrefix + 'bot_fingerprint_extras]'
	);

	// 3) Language check
	const languageChecksEnabled = hiddenInputsContainer.querySelector(
		'input[name=' + cf7aPrefix + '_language]'
	);

	// how append bot fingerprint into hidden fields
	const appendOnSubmit = hiddenInputsContainer.querySelector(
		'input[name=' + cf7aPrefix + 'append_on_submit]'
	);

	// how append bot fingerprint into hidden fields
	const cf7aVersionInput = hiddenInputsContainer.querySelector(
		'input[name=' + cf7aPrefix + 'version]'
	) as HTMLInputElement | null;

	// get the fake field and skip it
	if (wpcf7Form.querySelector('form')?.getAttribute('autocomplete')) {
		return;
	}

	// then set the cf7 antispam version field
	if (cf7aVersionInput) {
		cf7aVersionInput?.setAttribute('value', cf7aVersion);
	}

	// fingerprint browser data
	const tests = browserFingerprint();

	if (botFingerprintKey) {
		// 1.0 hijack the value of the bot_fingerprint
		const val = botFingerprintKey.getAttribute('value');
		botFingerprintKey.setAttribute('value', val?.slice(0, 5) || '');

		/**
		 * then append the fields on submit
		 * not supported in safari <11.3 https://developer.mozilla.org/en-US/docs/Web/API/HTMLFormElement/formdata_event#browser_compatibility
		 * update 2022/10: finally safari seems to support decently and widely formData! adding anyway a check to avoid failures with old browsers
		 */
		if (!appendOnSubmit || tests.isIos || tests.isIE || !window.FormData) {
			// or add them directly to hidden input container
			for (const key in tests) {
				hiddenInputsContainer.appendChild(
					createCF7Afield(key, String(tests[key as keyof Tests]))
				);
			}
		} else {
			const formElem = wpcf7Form.querySelector(
				'form'
			) as HTMLFormElement | null;
			if (!formElem) {
				console.error('CF7 Antispam: form not found');
				return;
			}
			new FormData(formElem);

			formElem.addEventListener('formdata', (e) => {
				const data = e.formData;
				for (const key in tests) {
					data.append(
						cf7aPrefix + key,
						String(tests[key as keyof Tests])
					);
				}
				return data;
			});
		}
	}

	// 2) Bot fingerprint extra checks
	if (botFingerprintExtra) {
		// 2.1) check for mouse clicks
		const activity = function () {
			const botActivity = hiddenInputsContainer.querySelector(
				'input[name=' + cf7aPrefix + 'activity]'
			);
			if (botActivity) {
				botActivity.remove();
			}
			hiddenInputsContainer.append(
				createCF7Afield('activity', String(mouseActivityValue++))
			);

			if (mouseActivityValue > 3) {
				document.body.removeEventListener('mouseup', activity);
				document.body.removeEventListener('touchend', activity);
				hiddenInputsContainer.append(
					createCF7Afield('mouseclick_activity', 'passed')
				);
			}
		};
		document.body.addEventListener('mouseup', activity);
		document.body.addEventListener('touchend', activity);

		// 2.2) detect the mouse/touch direction change OR touchscreen iterations
		const mouseMove = function (e: MouseEvent) {
			if (e.pageY > oldy) {
				mouseMoveValue += 1;
			}
			oldy = e.pageY;

			if (mouseMoveValue > 3) {
				document.removeEventListener('mousemove', mouseMove);
				hiddenInputsContainer.append(
					createCF7Afield('mousemove_activity', 'passed')
				);
			}
		};
		document.addEventListener('mousemove', mouseMove);

		// set mousemove_activity true as fallback in mobile devices (we have already tested the ability to use the touchscreen)
		if (tests.isIos || tests.isAndroid) {
			hiddenInputsContainer.append(
				createCF7Afield('mousemove_activity', 'passed')
			);
		}

		// 2.3) WebGL Tests
		// credits //bot.sannysoft.com
		const wpcf7box = document.createElement('div');
		wpcf7box.id = 'hidden';
		hiddenInputsContainer.append(wpcf7box);
		String.prototype.hashCode = function () {
			let hash = 0,
				i,
				chr;
			if (this.length === 0) {
				return hash;
			}
			for (i = 0; i < this.length; i++) {
				chr = this.charCodeAt(i);
				// eslint-disable-next-line no-bitwise
				hash = (hash << 5) - hash + chr;
				// eslint-disable-next-line no-bitwise
				hash |= 0; // Convert to 32bit integer
			}
			return hash;
		};

		const wglv = document.createElement('div');
		wglv.id = 'webgl-vendor';
		wpcf7box.append(wglv);
		const webGLVendorElement = wglv;
		const wgle = document.createElement('div');
		wgle.id = 'webgl-renderer';
		wpcf7box.append(wgle);
		const webGLRendererElement = wgle;
		const canvas = document.createElement('canvas');
		const gl: WebGLRenderingContext | null =
			(canvas.getContext('webgl') as WebGLRenderingContext) ||
			(canvas.getContext('experimental-webgl') as WebGLRenderingContext);

		if (gl) {
			const debugInfo =
				// @ts-ignore
				gl.getExtension('WEBGL_debug_renderer_info');

			try {
				// WebGL Vendor Test
				const vendor = debugInfo?.UNMASKED_VENDOR_WEBGL
					? gl.getParameter(debugInfo?.UNMASKED_VENDOR_WEBGL)
					: null;
				webGLVendorElement.innerHTML = vendor || 'Unknown';
				if (vendor === 'Brian Paul' || vendor === 'Google Inc.') {
					hiddenInputsContainer.append(
						createCF7Afield('webgl', 'failed')
					);
				} else {
					hiddenInputsContainer.append(
						createCF7Afield('webgl', 'passed')
					);
				}
			} catch (e) {
				webGLVendorElement.innerHTML = 'Error: ' + e;
			}

			try {
				// WebGL Renderer Test
				const renderer = debugInfo
					? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL)
					: null;
				webGLRendererElement.innerHTML = renderer || 'Unknown';
				if (
					renderer === 'Mesa OffScreen' ||
					(renderer && renderer.indexOf('Swift') !== -1)
				) {
					hiddenInputsContainer.append(
						createCF7Afield('webgl_render', 'failed')
					);
				} else {
					hiddenInputsContainer.append(
						createCF7Afield('webgl_render', 'passed')
					);
				}
			} catch (e) {
				webGLRendererElement.innerHTML = 'Error: ' + e;
			}
		} else {
			hiddenInputsContainer.append(createCF7Afield('webgl', 'failed'));
			hiddenInputsContainer.append(
				createCF7Afield('webgl_render', 'failed')
			);
		}

		// TODO: change the canvas name
		const testCanvas: HTMLDivElement[] = [];
		const testCanvasIframe: HTMLIFrameElement[] = [];
		testCanvas[1] = document.createElement('div');
		testCanvas[1].id = 'canvas1';

		testCanvas[2] = document.createElement('div');
		testCanvas[2].id = 'canvas2';

		testCanvas[3] = document.createElement('div');
		testCanvas[3].id = 'canvas3';
		testCanvasIframe[3] = document.createElement('iframe');
		testCanvasIframe[3].id = 'canvas3-iframe';
		testCanvasIframe[3].className = 'canvased';
		testCanvasIframe[3].setAttribute('sandbox', 'allow-same-origin');
		testCanvas[3].append(testCanvasIframe[3]);

		testCanvas[4] = document.createElement('div');
		testCanvas[4].id = 'canvas4';
		testCanvasIframe[4] = document.createElement('iframe');
		testCanvasIframe[4].id = 'canvas4-iframe';
		testCanvasIframe[4].className = 'canvased';
		testCanvasIframe[4].setAttribute('sandbox', 'allow-same-origin');
		testCanvas[4].append(testCanvasIframe[4]);

		testCanvas[5] = document.createElement('div');
		testCanvas[5].id = 'canvas5';
		testCanvasIframe[5] = document.createElement('iframe');
		testCanvasIframe[5].id = 'canvas5-iframe';
		testCanvasIframe[5].className = 'canvased';
		testCanvas[5].append(testCanvasIframe[5]);

		testCanvas.forEach(function (e) {
			wpcf7box.appendChild(e);
		});

		const drawCanvas2 = function (num: string, useIframe = false) {
			let datUrl: string = '';
			let canvas2d;

			let isOkCanvas: boolean = true;

			const canvasText: string = 'Bot test <canvas> 1.1';

			const canvasContainer = document.getElementById('canvas' + num);
			const iframe = document.getElementById(
				'canvas' + num + '-iframe'
			) as HTMLIFrameElement;

			// Get safely the inner iframe document
			const iframeDoc =
				iframe?.contentDocument || iframe?.contentWindow?.document;

			let canvasElement =
				useIframe && iframeDoc
					? iframeDoc.createElement('canvas')
					: document.createElement('canvas');

			if (
				canvasElement &&
				typeof canvasElement.getContext === 'function'
			) {
				canvas2d = canvasElement.getContext('2d');

				try {
					canvasElement.setAttribute('width', '220');
					canvasElement.setAttribute('height', '30');

					if (canvas2d === null) {
						isOkCanvas = false;
					} else {
						canvas2d.textBaseline = 'top';
						canvas2d.font = "14px 'Arial'";
						canvas2d.textBaseline = 'alphabetic';
						canvas2d.fillStyle = '#f60';
						canvas2d.fillRect(53, 1, 62, 20);
						canvas2d.fillStyle = '#069';
						canvas2d.fillText(canvasText, 2, 15);
						canvas2d.fillStyle = 'rgba(102, 204, 0, 0.7)';
						canvas2d.fillText(canvasText, 4, 17);
					}
				} catch (b) {
					canvasElement = document.createElement(
						'canvas'
					) as HTMLCanvasElement;
					canvas2d = canvasElement?.getContext('2d');
					if (
						void 0 === canvas2d ||
						'function' !==
							typeof canvasElement?.getContext('2d')?.fillText
					) {
						isOkCanvas = false;
					} else {
						canvasElement.setAttribute('width', '220');
						canvasElement.setAttribute('height', '30');
						if (canvas2d === null) {
							isOkCanvas = false;
						} else {
							/** @type {string} */
							canvas2d.textBaseline = 'top';
							/** @type {string} */
							canvas2d.font = "14px 'Arial'";
							/** @type {string} */
							canvas2d.textBaseline = 'alphabetic';
							/** @type {string} */
							canvas2d.fillStyle = '#f60';
							canvas2d.fillRect(125, 1, 62, 20);
							/** @type {string} */
							canvas2d.fillStyle = '#069';
							canvas2d.fillText(canvasText, 2, 15);
							/** @type {string} */
							canvas2d.fillStyle = 'rgba(102, 204, 0, 0.7)';
							canvas2d.fillText(canvasText, 4, 17);
						}
					}
				}

				if (
					isOkCanvas &&
					canvasElement &&
					'function' === typeof canvasElement.toDataURL
				) {
					datUrl = canvasElement.toDataURL('image/png');
					try {
						if ('boolean' === typeof datUrl || void 0 === datUrl) {
							throw new Error('Unable to load image');
						}
					} catch (a) {
						datUrl = '';
					}
					if (0 === datUrl.indexOf('data:image/png')) {
					} else {
						isOkCanvas = false;
					}
				} else {
					isOkCanvas = false;
				}
			} else {
				isOkCanvas = false;
			}

			if (isOkCanvas) {
				const newDiv = document.createElement('div');
				newDiv.innerHTML = 'Hash: ' + datUrl.hashCode();

				if (canvasContainer && canvasElement) {
					canvasContainer.appendChild(canvasElement);
					canvasContainer.appendChild(newDiv);
				}
			} else {
				const newDiv = document.createElement('div');
				newDiv.innerHTML = 'Canvas failed';
				if (canvasContainer) {
					canvasContainer.appendChild(newDiv);
				}
			}
		};

		window.canvasCount = 0;

		drawCanvas2('1');
		drawCanvas2('2');

		drawCanvas2('3', true);
		drawCanvas2('4', true);
		drawCanvas2('5', true);

		// then remove the useless div
		wpcf7box.remove();
	}

	// 3) check the browser language
	if (languageChecksEnabled) {
		hiddenInputsContainer.append(
			createCF7Afield('browser_language', getBrowserLanguage())
		);
	}
}

/**
 * The main function of the script - processes existing forms
 */
function main(): void {
	// disable cf7 antispam script if contact form is not loaded in this page
	if (!window.wpcf7) {
		return;
	}

	// disable cf7 refill on load if disableReload is enabled
	wpcf7.cached = parseInt(cf7a_settings.disableReload) === 0 && wpcf7.cached;

	// Process existing forms
	processExistingForms();

	// Setup MutationObserver to watch for new forms
	setupMutationObserver();
}

/**
 * Process all existing CF7 forms on the page
 */
function processExistingForms(): void {
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
function setupMutationObserver(): void {
	// Check if MutationObserver is supported
	if (typeof MutationObserver === 'undefined') {
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

/**
 * If the browser supports the `maxTouchPoints` property, then return the value of that property. If the browser supports
 * the `msMaxTouchPoints` property, then return the value of that property. If the browser supports the `matchMedia`
 * method, then return the value of the `matches` property of the `matchMedia` method. If the browser supports the
 * `orientation` property, then return `true`. If the browser doesn't support any of the above, then return the value of
 * the `test` method of the `RegExp` object
 *
 * @return {boolean} if has touchscreen or not
 */
function testTouch(): boolean {
	const navigator: ExtendedNavigator = window.navigator;
	const UA = navigator.userAgent;
	// if has no navigator return false
	if (!navigator) {
		return false;
	} else if ('maxTouchPoints' in navigator) {
		return navigator.maxTouchPoints > 0;
	} else if ('msMaxTouchPoints' in navigator) {
		// @ts-ignore
		return navigator.msMaxTouchPoints > 0;
	}
	// If the browser supports the `matchMedia` method
	const mQ = window.matchMedia && window.matchMedia('(pointer:coarse)');
	if (mQ && mQ.media === '(pointer:coarse)') {
		return !!mQ.matches;
	}
	if ('orientation' in window) {
		return true; // deprecated, but good fallback
	}
	// Only as a last resort, fall back to user agent sniffing
	return (
		/\b(BlackBerry|webOS|iPhone|IEMobile)\b/i.test(UA) ||
		/\b(Android|Windows Phone|iPad|iPod)\b/i.test(UA)
	);
}

/**
 * If the user is on an iOS device, return true
 * https://stackoverflow.com/questions/9038625/detect-if-device-is-ios
 *
 * @return {boolean} true if the device is apple ios
 */
function isIOS(): boolean {
	return (
		[
			'iPad Simulator',
			'iPhone Simulator',
			'iPod Simulator',
			'iPad',
			'iPhone',
			'iPod',
		].includes(navigator.platform) ||
		// iPad on iOS 13 detection
		(navigator.userAgent.includes('Mac') && 'ontouchend' in document)
	);
}

/**
 * It returns an object with the browser's name, version, and other information
 *
 * @return {Tests} An object with the following properties:
 */
function browserFingerprint(): Tests {
	const ua = window.navigator.userAgent as string;

	// holds the object with the tested props
	const tests = {
		timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
		// @ts-ignore reason: is deprecated but we are looking for the fallback response
		platform: window.navigator.platform || null,
		screens: [window.screen.width, window.screen.height] || null,
		memory:
			'deviceMemory' in window.navigator
				? (window.navigator.deviceMemory as number)
				: null,
		user_agent: ua || null,
		// @ts-ignore reason: is deprecated but we are looking for the fallback response
		app_version: (window.navigator.appVersion as string) || null,
		webdriver: window.navigator.webdriver || false,
		session_storage: window.sessionStorage ? 1 : null,
	} as Tests;

	// detect browser
	// https://developer.mozilla.org/en-US/docs/Web/API/Window/navigator
	if (ua.indexOf('Firefox') > -1) {
		tests.isFFox = true;
	} else if (ua.indexOf('SamsungBrowser') > -1) {
		tests.isSamsung = true;
	} else if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) {
		tests.isOpera = true;
	} else if (ua.indexOf('Trident') > -1) {
		tests.isIE = true;
	} else if (ua.indexOf('Edge') > -1) {
		tests.isIELegacy = true;
	} else if (ua.indexOf('Edg') > -1) {
		tests.isEdge = true;
	} else if (ua.indexOf('Chrome') > -1 || ua.indexOf('CriOS') > -1) {
		// criOS stands for chrome for ios
		tests.isChrome = true;
	} else if (ua.indexOf('Safari') > -1 || ua.indexOf('GSA') > -1) {
		// GSA stand for Google Search Appliance
		tests.isSafari = true;
	} else {
		tests.isUnknown = true;
	}

	if (isIOS()) {
		tests.isIos = true;
	} else if (ua.indexOf('Android') > -1) {
		tests.isAndroid = true;
	}

	if (tests.isIos || tests.isAndroid) {
		tests.touch = testTouch();
	}

	return tests;
}

/**
 * It returns the browser language.
 *
 * @return {string} The language of the browser.
 */
const getBrowserLanguage = (): string => {
	return window.navigator.languages.join() || window.navigator.language;
};

/**
 * It creates a hidden input field with a name and value
 *
 * @param {string}                    key      - the name of the field
 * @param {string | number | boolean} value    - The value of the field.
 * @param {string}                    [prefix] - The prefix for the field name.
 *
 * @return {HTMLElement} A new input element with the type of hidden, name of the key, and value of the value.
 */
function createCF7Afield(
	key: string,
	value: string | number | boolean,
	prefix: string = cf7a_settings.prefix
): HTMLElement {
	const e = document.createElement('input');
	e.setAttribute('type', 'hidden');
	e.setAttribute('name', prefix + key);

	let stringValue: string;
	if (typeof value === 'string') {
		stringValue = value;
	} else if (typeof value === 'number' || typeof value === 'boolean') {
		stringValue = String(value);
	} else {
		stringValue = JSON.stringify(value);
	}

	e.setAttribute('value', stringValue);
	return e;
}

/**
 * Run the function as IIFE
 */
(function () {
	ready(main);
})();

export { main as cf7antispam };
