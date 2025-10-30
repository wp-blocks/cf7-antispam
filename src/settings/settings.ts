import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

window.addEventListener('load', adminSettingsHelper);

/**
 * A function that handles all the function that download some files from the server via API
 * @param e HTMLElement The button element
 */
function exportActionHandler(e: HTMLElement) {
	const { action, nonce } = e.dataset as {
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
				console.error('Error: Failed to export file', message);
			}
		})
		.catch((error: any) => {
			console.error('Error:', error.message);
			alert('Request failed: ' + error.message);
		});
}

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

	let cb: null | (() => void) = null;
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
			const { message, success, log } = res as {
				message: string;
				success: boolean;
				log?: string;
			};
			if (success) {
				if (message) {
					alert(message);
				}
				if (cb) {
					cb();
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

function adminSettingsHelper() {
	/* This is the code that adds the confirmation alert to the delete buttons on the settings page. */
	if (
		document.body.classList.contains('cf7-antispam-admin') ||
		document.body.classList.contains('flamingo_page_flamingo_inbound')
	) {
		// the confirmation alert script
		const actions = document.querySelectorAll(
			'.cf7a_action'
		) as NodeListOf<HTMLElement>;
		const exportActions = document.querySelectorAll(
			'.cf7a_export_action'
		) as NodeListOf<HTMLElement>;

		actions.forEach((action: HTMLElement) => {
			action.addEventListener('click', () => {
				actionHandler(action);
			});
		});
		exportActions.forEach((action: HTMLElement) => {
			action.addEventListener('click', () => {
				exportActionHandler(action);
			});
		});
	}

	/* This is the code that saves the settings when the user presses ctrl-s. */
	if (document.body.classList.contains('cf7-antispam-admin')) {
		// save on ctrl-s keypress
		document.addEventListener('keydown', (e) => {
			if (e.ctrlKey && e.key === 's') {
				e.preventDefault();
				document.getElementById('submit')?.click();
			}
		});
	}

	/* This is the code that hides the welcome panel,
    and shows the advanced settings. */
	if (document.body.classList.contains('cf7-antispam-admin')) {
		// shows the advanced section
		const showAdvanced = () => {
			const advancedCheckbox = document.getElementById(
				'enable_advanced_settings'
			) as HTMLInputElement;
			const AdvSettingsCard = document.getElementById(
				'advanced-setting-card'
			) as HTMLElement;
			const AdvSettingsTitle = document.querySelectorAll(
				'#cf7a_settings h2'
			) as NodeListOf<HTMLElement>;
			const AdvSettingsTitleEl =
				AdvSettingsTitle[AdvSettingsTitle.length - 1];

			const AdvSettingsTxt = document.querySelectorAll(
				'#cf7a_settings p'
			) as NodeListOf<HTMLElement>;
			const AdvSettingsTxtEl = AdvSettingsTxt[AdvSettingsTxt.length - 2];

			const AdvSettingsForm = document.querySelectorAll(
				'#cf7a_settings table'
			) as NodeListOf<HTMLElement>;
			const AdvSettingsFormEl =
				AdvSettingsForm[AdvSettingsForm.length - 1];

			if (advancedCheckbox?.checked) {
				if (AdvSettingsCard) {
					AdvSettingsCard.classList.remove('hidden');
				}

				AdvSettingsTitleEl?.classList.remove('hidden');
				AdvSettingsTxtEl?.classList.remove('hidden');
				AdvSettingsFormEl?.classList.remove('hidden');
			} else {
				if (AdvSettingsCard) {
					AdvSettingsCard.classList.add('hidden');
				}

				AdvSettingsTitleEl?.classList.add('hidden');
				AdvSettingsTxtEl?.classList.add('hidden');
				AdvSettingsFormEl?.classList.add('hidden');
			}
		};

		// Honeyform page exclusion logic
		if (document.body.classList.contains('cf7-antispam-admin')) {
			const addListButton = document.querySelector(
				'.add-list'
			) as HTMLButtonElement;
			const addSelect = document.querySelector(
				'.add-select'
			) as HTMLSelectElement;
			const removeListButton = document.querySelector(
				'.remove-list'
			) as HTMLButtonElement;
			const removeSelect = document.querySelector(
				'.remove-select'
			) as HTMLSelectElement;

			if (removeSelect) {
				for (const remove of removeSelect) {
					if (addSelect) {
						for (const add of addSelect) {
							if (remove.value === add.value) {
								addSelect.removeChild(add);
							}
						}
					}
				}
			}

			addListButton?.addEventListener('click', () => {
				for (const option of addSelect.options) {
					if (option.selected) {
						const name = option.textContent;
						const value = option.value;

						if (!removeSelect.options[Number(value)]) {
							const newOption = document.createElement('option');
							newOption.selected = true;
							newOption.value = value;
							newOption.textContent = name;

							removeSelect.appendChild(newOption);
						}
						option.remove();
					}
				}
			});

			removeListButton?.addEventListener('click', () => {
				for (const option of removeSelect.options) {
					if (option.selected) {
						const name = option.textContent;
						const value = option.value;

						if (!removeSelect.options[Number(value)]) {
							const newOption = document.createElement('option');
							newOption.value = value;
							newOption.textContent = name;

							addSelect.appendChild(newOption);
						}
						option.remove();
					}
				}
			});
		}

		// Rest API status
		const restApiStatus = document.getElementById(
			'rest-api-status'
		) as HTMLDivElement | null;
		if (restApiStatus) {
			apiFetch({
				path: '/cf7-antispam/v1/status',
				method: 'GET',
			})
				.then((response) => {
					if (response) {
						const { status, plugin_version, timestamp } =
							response as {
								status: string;
								plugin_version: string;
								timestamp: string;
							};
						restApiStatus.innerHTML = `<p>${__('Status', 'cf7-antispam')}: ${status}</p><p>${__('CF7 Antispam plugin version is', 'cf7-antispam')} ${plugin_version} - (${__('Request timestamp', 'cf7-antispam')}: ${timestamp})</p>`;
					} else {
						restApiStatus.textContent = 'No response';
					}
				})
				.catch((error) => {
					restApiStatus.textContent = 'Error: ' + error.message;
					console.error('CF7A Error:', error.message, error.code);
				});
		}

		/* on click show advanced options */
		document
			.getElementById('enable_advanced_settings')
			?.addEventListener('click', showAdvanced);
		showAdvanced();
	}
}
