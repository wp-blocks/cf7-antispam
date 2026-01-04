/**
 * Internal dependencies
 */
import { processExistingForms, setupMutationObserver } from './core';

declare const wpcf7: {
	cached: boolean;
};

/**
 * The main function of the script - processes existing forms
 */
function main(): void {
	// disable cf7 antispam script if 'contact form 7' is not loaded in this page
	if (!window.wpcf7) {
		return;
	}

	// disable cf7 refill onload if disableReload is enabled
	// eslint-disable-next-line camelcase
	wpcf7.cached = parseInt(cf7a_settings.disableReload) === 0 && wpcf7.cached;

	// Process existing forms
	processExistingForms();

	// Setup MutationObserver to watch for new forms
	setupMutationObserver();
}

export { main as cf7antispam };
