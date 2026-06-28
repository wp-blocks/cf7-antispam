/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Handles the import blocklist logic.
 */
if (document.body.classList.contains('cf7-antispam-admin')) {
	const importButton = document.querySelector(
		'.cf7a_import_action'
	) as HTMLButtonElement | null;
	const fileInput = document.getElementById(
		'cf7a_import_file'
	) as HTMLInputElement | null;

	if (importButton && fileInput) {
		importButton.addEventListener('click', (e) => {
			e.preventDefault();
			fileInput.click();
		});

		fileInput.addEventListener('change', () => {
			if (!fileInput.files || fileInput.files.length === 0) {
				return;
			}

			const file = fileInput.files[0];
			const { action, nonce } = importButton.dataset as {
				action: string;
				nonce: string;
			};

			const originalText = importButton.textContent;
			importButton.textContent = 'Importing...';
			importButton.disabled = true;

			const formData = new FormData();
			formData.append('file', file);
			formData.append('nonce', nonce);

			apiFetch({
				path: '/cf7-antispam/v1/' + action,
				method: 'POST',
				body: formData,
			})
				.then((res: any) => {
					importButton.textContent = originalText;
					importButton.disabled = false;
					fileInput.value = '';

					if (res.success) {
						alert(res.message);
						window.location.reload();
					} else {
						console.error('Error:', res.message);

						alert('Import failed: ' + res.message);
					}
				})
				.catch((error: any) => {
					importButton.textContent = originalText;
					importButton.disabled = false;
					fileInput.value = '';

					console.error('Error:', error.message);

					alert('Request failed: ' + error.message);
				});
		});
	}
}
