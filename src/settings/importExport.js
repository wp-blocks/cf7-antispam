window.onload = function () {
	// Example for download button
	document
		.getElementById('cf7a_download_button')
		.addEventListener('click', () => downloadOptions());

	document
		.getElementById('import-export-options')
		.addEventListener('submit', (e) => importExportOptions(e));
};

/**
 * Import and export options from/to JSON
 *
 * @param {SubmitEvent} e The submit event from the form
 */
function importExportOptions(e) {
	e.preventDefault();

	// eslint-disable-next-line no-alert
	const confirmImport = confirm(
		'Are you sure you want to import options? This will overwrite your current settings.'
	);
	if (!confirmImport) {
		return;
	}
	/**
	 * Parse the JSON string and get the cf7-antispam options
	 */
	const optionsContent = document.getElementById('cf7a_options_area').value;
	let cf7aOptions = null;
	try {
		cf7aOptions = JSON.parse(optionsContent);
	} catch (err) {
		// eslint-disable-next-line no-console
		console.error(err);
		// eslint-disable-next-line no-alert
		alert('Invalid JSON. Please check your file and try again.');
		return;
	}
	/**
	 * Get the submit form data and append the cf7-ntispam options to the form data options
	 * @type {FormData}
	 */
	const data = new FormData(e.target);
	data.append('to-import', encodeURIComponent(JSON.stringify(cf7aOptions)));

	// Make an AJAX request to save the merged options
	fetch(e.target.getAttribute('action'), {
		method: 'POST',
		body: data,
	})
		.then((response) => response)
		.then((response) => {
			// Handle the response
			// eslint-disable-next-line no-console
			console.log(response);
			if (response.status === 200) {
				// emulate the php non async behavior
				window.location.href = response.url;
			}
		})
		.catch((error) => {
			// Handle the error
			// eslint-disable-next-line no-console
			console.error(error);
		});
}

function downloadOptions() {
	const optionsContent = document.getElementById('cf7a_options_area').value;
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
	// eslint-disable-next-line no-alert
	alert('Your file has downloaded!');
}
