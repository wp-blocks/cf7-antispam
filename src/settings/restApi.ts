/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

type ApiResponse = { message: string; success: boolean; log?: string };
type CallbackFunction = null | ((res: ApiResponse) => void);

/**
 * A function that handles the actions for the admin tabs
 *
 * @param {HTMLElement} el                  The button element
 * @param {Object}      el.dataset          The dataset of the button element
 * @param {string}      el.dataset.message  The message to display in the confirmation alert
 * @param {string}      el.dataset.nonce    The nonce to verify the request
 * @param {string}      el.dataset.action   The action to run
 * @param {string}      el.dataset.id       The id of the row to hide
 * @param {string}      el.dataset.callback The callback function to run
 */
function actionHandler(el: HTMLElement) {
	const { action, message, callback, nonce } = el.dataset as {
		action: string;
		message: string;
		nonce: string;
		id?: string;
		callback?: string;
	};

	/**
	 * Confirmation alert
	 * We are going to ask the user to confirm the action before proceeding using the confirm() function
	 */
	if (
		// eslint-disable-next-line no-alert
		message &&
		// eslint-disable-next-line no-alert
		!confirm(message)
	) {
		return;
	}

	/**
	 * Callback function
	 */
	let cb: CallbackFunction = null;
	if (callback && typeof callback === 'string') {
		/**
		 * Callback functions
		 *
		 * HIDE
		 * We are going to create a callback function to hide the row
		 */
		if (callback === 'hide') {
			cb = function () {
				el.closest('.row')?.classList.add('hidden');
			};
		}

		/**
		 * Update GeoIP status
		 */
		if (callback === 'update-geoip-status') {
			cb = function (res) {
				const geoipStatus = document.querySelector(
					'.cf7a_geoip_is_enabled'
				) as HTMLElement;
				// update the status
				geoipStatus.innerHTML = res.success ? '✅' : '❌';
			};
		}

		/**
		 * If needed we can add more callback function here
		 */
	}

	/**
	 * Data object
	 * We are going to create a data object to send to the API
	 * because we cannot send the dataset directly to the API
	 */
	const data: { nonce: string; id?: number } = {
		nonce,
	};

	if (el.dataset.id) {
		data.id = Number(el.dataset.id);
	}

	apiFetch({
		path: '/cf7-antispam/v1/' + action,
		method: 'POST',
		data,
	})
		.then((r) => {
			const response = r as ApiResponse;
			if (response.success) {
				if (response.message) {
					// eslint-disable-next-line no-alert
					alert(response.message);
				}
				if (cb) {
					cb(response);
				}
			} else {
				// eslint-disable-next-line no-console
				console.error(
					'Error:',
					response.message,
					response.log as string
				);
			}
		})
		.catch((error: any) => {
			// eslint-disable-next-line no-console
			console.error('Error:', error.message);
			// eslint-disable-next-line no-alert
			alert('Request failed: ' + error.message);
		});
}

/**
 * This is the code that finds the buttons with the class cf7a_action and
 * cf7a_export_action and adds the event listener to them.
 */
if (
	document.body.classList.contains('cf7-antispam-admin') ||
	document.body.classList.contains('flamingo_page_flamingo_inbound')
) {
	// the confirmation alert script
	const actions = document.querySelectorAll(
		'.cf7a_action'
	) as NodeListOf<HTMLElement>;
	actions.forEach((action: HTMLElement) => {
		action.addEventListener('click', () => {
			actionHandler(action);
		});
	});
}
