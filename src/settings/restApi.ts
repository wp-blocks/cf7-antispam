import apiFetch from '@wordpress/api-fetch';

type ApiResponse = { message: string; success: boolean; log?: string };
type CallbackFunction = null | ((res: ApiResponse) => void);

/**
 * A function that handles the actions for the admin tabs
 *
 * @param e               HTMLElement The button element
 * @param dataset         object The dataset of the button element
 * @param dataset.message string The message to display in the confirmation alert
 * @param dataset.nonce   string T
 * @param dataset.action
 * @param dataset.id
 */
function actionHandler(e: HTMLElement) {
	const { action, message, callback, nonce } = e.dataset as {
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
				e.closest('.row')?.classList.add('hidden');
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

	if (e.dataset.id) {
		data.id = Number(e.dataset.id);
	}

	apiFetch({
		path: '/cf7-antispam/v1/' + action,
		method: 'POST',
		data,
	})
		.then((res) => {
			const { message, success, log } = res as ApiResponse;
			if (success) {
				if (message) {
					alert(message);
				}
				if (cb) {
					cb(res as ApiResponse);
				}
			} else {
				console.error('Error:', message, log as string);
			}
		})
		.catch((error: any) => {
			console.error('Error:', error.message);
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
