import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

if (document.body.classList.contains('cf7-antispam-admin')) {
	window.addEventListener('load', adminSettingsHelper);
}

function adminSettingsHelper() {
	/**
	 *  This is the code that saves the settings when the user presses ctrl-s.
	 */
	document.addEventListener('keydown', (e) => {
		if (e.ctrlKey && e.key === 's') {
			e.preventDefault();
			document.getElementById('submit')?.click();
		}
	});

	/**
	 * This is the code that shows the advanced settings.
	 */
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
		const AdvSettingsFormEl = AdvSettingsForm[AdvSettingsForm.length - 1];

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

	/** Handle the onclick event for displaying advanced options */
	document
		.getElementById('enable_advanced_settings')
		?.addEventListener('click', showAdvanced);
	showAdvanced();

	/** Honeyform page exclusion logic */
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

	/** Rest API status */
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
					const { status, plugin_version, timestamp } = response as {
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

	/**
	 * GeoIP file upload
	 */
	const fileInput = document.getElementById(
		'geoip_dbfile'
	) as HTMLInputElement;
	const displaySpan = document.getElementById('file_name_display');
	if (fileInput && displaySpan) {
		// Event listener for when a file is selected
		fileInput?.addEventListener('change', (event) => {
			const files = (event.target as HTMLInputElement)?.files as FileList;

			if (files.length > 0) {
				// Update the display span with the name of the first selected file
				const fileName = files[0].name;
				displaySpan.textContent = fileName;
			} else {
				// If the user cancels the dialog without selecting a file
				displaySpan.textContent = 'No file selected.';
			}
		});
	}
}
