import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

window.addEventListener('load', adminSettingsHelper);

function adminSettingsHelper() {
	/* This is the code that adds the confirmation alert to the delete buttons on the settings page. */
	if (
		document.body.classList.contains('cf7-antispam-admin') ||
		document.body.classList.contains('flamingo_page_flamingo_inbound')
	) {
		// eslint-disable-next-line camelcase
		const alertMessage = cf7a_admin_settings.alertMessage;

		// the confirmation alert script
		const alerts = document.querySelectorAll(
			'.cf7a_alert'
		) as NodeListOf<HTMLElement>;

		function confirmationAlert(e: HTMLElement, message: any) {
			if (
				// eslint-disable-next-line no-alert
				confirm(message || alertMessage) &&
				'dataset' in e
			) {
				const dataset = e.dataset as { [key: string]: string };

				apiFetch({
					path: '/cf7-antispam/v1/resend_message',
					method: 'POST',
					data: {
						id: dataset.id,
						nonce: dataset.nonce,
					},
				})
					.then((res) => {
						if (res) {
							alert(res);
						} else {
							alert('Error');
						}
					})
					.catch((error: any) => {
						console.error('API Error:', error);
						alert('Request failed');
					});
			}
		}

		alerts.forEach((alert: HTMLElement) => {
			alert.addEventListener('click', () => {
				confirmationAlert(alert, alert.dataset.message || false);
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

			if (advancedCheckbox.checked) {
				if (AdvSettingsCard) {
					AdvSettingsCard.classList.remove('hidden');
				}

				AdvSettingsTitleEl.classList.remove('hidden');
				AdvSettingsTxtEl.classList.remove('hidden');
				AdvSettingsFormEl.classList.remove('hidden');
			} else {
				if (AdvSettingsCard) {
					AdvSettingsCard.classList.add('hidden');
				}

				AdvSettingsTitleEl.classList.add('hidden');
				AdvSettingsTxtEl.classList.add('hidden');
				AdvSettingsFormEl.classList.add('hidden');
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

			for (const remove of removeSelect) {
				for (const add of addSelect) {
					if (remove.value === add.value) {
						addSelect.removeChild(add);
					}
				}
			}
			addListButton.addEventListener('click', () => {
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

			removeListButton.addEventListener('click', () => {
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
						const { status, version, timestamp } = response as {
							status: string;
							version: string;
							timestamp: string;
						};
						restApiStatus.innerHTML = `<p>${__('Status', 'cf7-antispam')}: ${status}</p><p>${__('CF7 Antispam plugin version is', 'cf7-antispam')} ${version} - (${__('Request timestamp', 'cf7-antispam')}: ${timestamp})</p>`;
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
