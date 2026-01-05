/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
const loader = (): HTMLDivElement => {
	// create an element to show loading with a svg loader
	const i = document.createElement('div') as HTMLDivElement;
	i.className = 'cf7a-loader';
	i.innerHTML = `<svg viewBox="0 0 50 50" class="circular-loader">
    <circle cx="25" cy="25" r="20" fill="none" stroke-linecap="round" stroke="#222" stroke-width="6" stroke-dasharray="140,250" stroke-dashoffset="360" >
        <animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="3s" additive="sum" repeatCount="indefinite" />
    </circle></svg>`;
	return i;
};

/**
 * Import and export options from/to JSON
 *
 * @param {SubmitEvent} e The submit event from the form
 */
function importExportOptions(e: SubmitEvent) {
	e.preventDefault();

	if (
		// eslint-disable-next-line no-alert
		!confirm(
			__(
				'Are you sure you want to import options? This will overwrite your current settings.',
				'cf7-antispam'
			)
		)
	) {
		return;
	}

	// Get the cf7-antispam options
	const optionsArea = document.getElementById(
		'cf7a_options_area'
	) as HTMLTextAreaElement;
	const optionsContent = optionsArea?.value;

	// Parse the JSON string
	let cf7aOptions = null;
	try {
		cf7aOptions = JSON.parse(optionsContent);
	} catch (err) {
		// eslint-disable-next-line no-console
		console.error(err);
		// eslint-disable-next-line no-alert
		alert(
			__(
				'Invalid JSON. Please check your file and try again.',
				'cf7-antispam'
			)
		);
		return;
	}

	// Get the submit form data and append the cf7-ntispam options to the form data options
	const data: FormData = new FormData(e.target as HTMLFormElement);
	const nonce = optionsArea.dataset.nonce || '';
	data.append('cf7a-nonce', nonce);
	data.append('to-import', JSON.stringify(cf7aOptions));

	// Append after the form data options a spinning loader
	const loaderElement = loader();
	const targetElement = e.target as HTMLFormElement;
	targetElement
		?.querySelector('#cf7a_import_button')
		?.insertAdjacentElement('afterend', loaderElement);

	// Make an AJAX request to save the merged options
	const actionOptions = targetElement.getAttribute('action');
	if (actionOptions) {
		fetch(actionOptions, {
			method: 'POST',
			body: data,
		})
			.then((response) => response)
			.then((response) => {
				// Handle the response
				if (response.status === 200) {
					// eslint-disable-next-line no-alert
					alert('Data imported successfully');
					// emulate the php non async behavior
					window.location.reload();
				}
			})
			.catch((error) => {
				// Handle the error
				// eslint-disable-next-line no-console
				console.error(error);
				loaderElement.remove();
			});
	}
}

/**
 * Download the options content to a file
 *
 * @param {string} optionsContent The options content to download
 */
function downloadText(optionsContent: string) {
	try {
		const blob = new Blob([optionsContent], {
			type: 'application/json',
		});
		const url = window.URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.style.display = 'none';
		a.href = url;
		a.download = 'cf7a-' + new Date().getTime() / 1000 + '.json';
		document.body.appendChild(a);
		a.click();
		window.URL.revokeObjectURL(url);
		return true;
	} catch (err) {
		// eslint-disable-next-line no-console
		console.error(err);
		return false;
	}
}

/**
 * Download the options content to a file
 */
function downloadOptions() {
	const optionsArea = document.getElementById(
		'cf7a_options_area'
	) as HTMLTextAreaElement;
	const optionsContent = optionsArea?.value;

	downloadText(optionsContent);

	// eslint-disable-next-line no-alert
	alert('Your file has downloaded!');
}

/**
 * Initialize the import and export options
 */
window.onload = function () {
	/**
	 * The download button action
	 */
	document
		.getElementById('cf7a_download_button')
		?.addEventListener('click', () => downloadOptions());

	/**
	 * The import button action
	 */
	document
		.getElementById('import-export-options')
		?.addEventListener('submit', (e) => importExportOptions(e));
};
