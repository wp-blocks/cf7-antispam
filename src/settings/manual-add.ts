/**
 * Internal dependencies
 */
/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

document.addEventListener('DOMContentLoaded', () => {
	if (!document.body.classList.contains('cf7-antispam-admin')) {
		return;
	}

	// Create modal dynamically
	let modal = document.getElementById('cf7a-manual-add-modal');
	if (!modal) {
		modal = document.createElement('div');
		modal.id = 'cf7a-manual-add-modal';
		modal.className = 'cf7a-modal';
		modal.style.display = 'none';
		modal.innerHTML = `
			<div class="cf7a-modal-content">
				<h3 class="cf7a-modal-title">Add IP / CIDR Entry</h3>
				<div class="cf7a-modal-body">
					<p>Enter the IP address (e.g., <code>192.168.1.1</code>) or CIDR block (e.g., <code>192.168.1.0/24</code>) to add to the <strong id="cf7a-modal-list-label"></strong>:</p>
					<input type="text" id="cf7a-manual-add-ip" class="regular-text" placeholder="e.g. 192.168.1.1 or 192.168.1.0/24" style="width:100%; margin-bottom:15px; padding:8px; border:1px solid #ccc; border-radius:4px;" />
					<div class="cf7a-modal-actions" style="display:flex; justify-content:flex-end; gap:10px;">
						<button type="button" id="cf7a-modal-cancel" class="button">Cancel</button>
						<button type="button" id="cf7a-modal-submit" class="button button-primary">Add Entry</button>
					</div>
				</div>
			</div>
		`;
		document.body.appendChild(modal);
	}

	const ipInput = document.getElementById(
		'cf7a-manual-add-ip'
	) as HTMLInputElement;
	const listLabel = document.getElementById(
		'cf7a-modal-list-label'
	) as HTMLElement;
	const submitBtn = document.getElementById(
		'cf7a-modal-submit'
	) as HTMLButtonElement;
	const cancelBtn = document.getElementById(
		'cf7a-modal-cancel'
	) as HTMLButtonElement;

	let currentTargetList = '';
	let currentNonce = '';

	// Hook "+ Add Entry" buttons
	document.addEventListener('click', (e) => {
		const target = e.target as HTMLElement;
		if (target && target.classList.contains('cf7a-add-entry')) {
			e.preventDefault();
			const list = target.getAttribute('data-list') || '';
			const nonce = target.getAttribute('data-nonce') || '';
			if (!list || !nonce) {
				return;
			}

			currentTargetList = list;
			currentNonce = nonce;

			listLabel.textContent =
				list === 'ip_allowlist'
					? 'Allowlist'
					: 'Permanently Banned IPs (Manual List)';
			ipInput.value = '';
			modal!.style.display = 'flex';
			ipInput.focus();
		}

		// Handle remove button
		if (target && target.classList.contains('cf7a-remove-manual')) {
			e.preventDefault();
			const list = target.getAttribute('data-list') || '';
			const ip = target.getAttribute('data-ip') || '';
			const nonce = target.getAttribute('data-nonce') || '';
			if (!list || !ip || !nonce) {
				return;
			}

			if (
				confirm(`Are you sure you want to remove ${ip} from the list?`)
			) {
				apiFetch({
					path: '/cf7-antispam/v1/blocklist/remove',
					method: 'POST',
					data: { list, ip, nonce },
				})
					.then((response: any) => {
						if (response && response.success) {
							window.location.reload();
						} else {
							alert(response.message || 'Error removing entry');
						}
					})
					.catch((error: any) => {
						alert(error.message || 'Request failed');
					});
			}
		}
		// Handle quick allow button (fake card)
		const tipCard = target.closest('.cf7a-tip-card') as HTMLElement;
		if (tipCard) {
			e.preventDefault();
			const ip = tipCard.getAttribute('data-ip') || '';
			const nonce = tipCard.getAttribute('data-nonce') || '';

			if (!ip || !nonce) {
				return;
			}

			// Add visual loading state
			tipCard.style.opacity = '1';
			tipCard.style.pointerEvents = 'none';

			apiFetch({
				path: '/cf7-antispam/v1/blocklist/add',
				method: 'POST',
				data: {
					list: 'ip_allowlist',
					ip,
					nonce,
				},
			})
				.then((response: any) => {
					if (response && response.success) {
						window.location.reload();
					} else {
						alert(response.message || 'Error adding entry');
						tipCard.style.opacity = '0.6';
						tipCard.style.pointerEvents = 'auto';
					}
				})
				.catch((error: any) => {
					alert(error.message || 'Request failed');
					tipCard.style.opacity = '0.6';
					tipCard.style.pointerEvents = 'auto';
				});
		}
	});

	cancelBtn.addEventListener('click', () => {
		modal!.style.display = 'none';
	});

	// Close on background click
	modal.addEventListener('click', (e) => {
		if (e.target === modal) {
			modal!.style.display = 'none';
		}
	});

	submitBtn.addEventListener('click', () => {
		const ip = ipInput.value.trim();
		if (!ip) {
			alert('Please enter an IP address or CIDR range.');
			return;
		}

		submitBtn.disabled = true;
		submitBtn.textContent = 'Adding...';

		apiFetch({
			path: '/cf7-antispam/v1/blocklist/add',
			method: 'POST',
			data: {
				list: currentTargetList,
				ip,
				nonce: currentNonce,
			},
		})
			.then((response: any) => {
				submitBtn.disabled = false;
				submitBtn.textContent = 'Add Entry';
				if (response && response.success) {
					modal!.style.display = 'none';
					window.location.reload();
				} else {
					alert(response.message || 'Error adding entry');
				}
			})
			.catch((error: any) => {
				submitBtn.disabled = false;
				submitBtn.textContent = 'Add Entry';
				alert(error.message || 'Request failed');
			});
	});
});
