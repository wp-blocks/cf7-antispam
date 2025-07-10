/* eslint-disable camelcase */

/**
 * Script Types declaration
 */
import { cf7antispam } from './frontend/script';

/**
 * Run the function as IIFE
 */
(function () {
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

	ready(cf7antispam);
})();
