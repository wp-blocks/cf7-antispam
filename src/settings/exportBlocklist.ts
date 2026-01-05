/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * A function that handles all the function that download some files from the server via API
 *
 * @param {HTMLElement} el HTMLElement The button element
 */
function exportActionHandler(el: HTMLElement) {
	const { action, nonce } = el.dataset as {
		action: string;
		nonce: string;
	};

	apiFetch({
		path: '/cf7-antispam/v1/' + action,
		method: 'POST',
		data: {
			nonce,
		},
	})
		.then((res) => {
			const { success, message, filetype, filename, data } = res as {
				success: boolean;
				message: string;
				filetype: string;
				filename: string;
				data: string;
			};
			if (success) {
				if (filetype === 'csv') {
					const blob = new Blob([data], { type: 'text/csv' });
					const url = window.URL.createObjectURL(blob);
					const a = document.createElement('a');
					a.style.display = 'none';
					a.href = url;
					a.download = filename;
					document.body.appendChild(a);
					a.click();
					window.URL.revokeObjectURL(url);
				}
			} else {
				// eslint-disable-next-line no-console
				console.error('Error: Failed to export file', message);
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
 * This is the code that finds the buttons with the class cf7a_export_action and
 * adds the event listener to them.
 *
 * At the moment is used only for blocklist export function
 */
if (document.body.classList.contains('cf7-antispam-admin')) {
	const exportActions = document.querySelectorAll(
		'.cf7a_export_action'
	) as NodeListOf<HTMLElement>;

	exportActions.forEach((action: HTMLElement) => {
		action.addEventListener('click', () => {
			exportActionHandler(action);
		});
	});
}
