window.onload = function () {
	// Example for download button
	document
		.getElementById('cf7a_download_button')
		.addEventListener('click', function () {
			const optionsContent =
				document.getElementById('cf7a_options_area').value;
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
		});

	// Example for import button
	document
		.getElementById('cf7a_import_button')
		.addEventListener('click', function () {
			// eslint-disable-next-line no-alert
			const confirmImport = confirm(
				'Are you sure you want to import options? This will overwrite your current settings.'
			);
			if (confirmImport) {
				const optionsContent =
					document.getElementById('cf7a_options_area').value;
				let cf7aOptions = null;
				try {
					cf7aOptions = JSON.parse(optionsContent);
				} catch (e) {
					// eslint-disable-next-line no-alert
					alert(
						'Invalid JSON. Please check your file and try again.'
					);
				}
				if (cf7aOptions) {
					const postData = new FormData();
					postData.append('cf7a', cf7aOptions);

					// Make an AJAX request to save the merged options
					fetch('.', {
						method: 'POST',
						body: postData,
					})
						.then((response) => response.json())
						.then((data) => {
							// Handle the response
							// eslint-disable-next-line no-console
							console.log(data);
						})
						.catch((error) => {
							// Handle the error
							// eslint-disable-next-line no-console
							console.error(error);
						});
				}
			}
		});
};
