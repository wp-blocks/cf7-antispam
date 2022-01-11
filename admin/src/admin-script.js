window.onload = function() {

	if (document.body.classList.contains('cf7-antispam-admin') || document.body.classList.contains('flamingo_page_flamingo_inbound')) {

		const alertMessage = cf7a_admin_settings.alertMessage;

		// the confirmation alert script
		const alerts = document.querySelectorAll('.cf7a_alert');

		function confirmationAlert(e, message) {
			if (confirm(message || alertMessage)) location.href = e.dataset.href;
		}

		alerts.forEach((alert) => {
			alert.addEventListener("click", () => {
				confirmationAlert(alert, alert.dataset.message || false);
			});
		});
	}

	if (document.body.classList.contains('cf7-antispam-admin')) {
		// hide the welcome panel
		const welcomePanel = document.getElementById('welcome-panel');
		const welcomePanelCloseBtn = welcomePanel.querySelector('a.welcome-panel-close');
		welcomePanelCloseBtn.addEventListener('click', event => {
			event.preventDefault();
			welcomePanel.classList.add('hidden');
		});

		// save on ctrl-s keypress
		document.addEventListener('keydown', e => {
			if (e.ctrlKey && e.key === 's') {
				e.preventDefault();
				document.getElementById('submit').click();
			}
		});

		// show the advanced section
		const showAdvanced = () => {
			const advancedCheckbox = document.getElementById('enable_advanced_settings');
			const AdvSettingsTitle = document.querySelectorAll('#cf7a_settings h2');
			const AdvSettingsForm = document.querySelectorAll('#cf7a_settings table');
			const AdvSettingsCard = document.getElementById('advanced-setting-card');
			if (advancedCheckbox.checked !== true) {
				if (AdvSettingsCard) {
					AdvSettingsCard.classList.add('hidden');
					AdvSettingsTitle[AdvSettingsTitle.length - 1].classList.add('hidden');
					AdvSettingsForm[AdvSettingsForm.length - 1].classList.add('hidden');
				}
			} else {
				if (AdvSettingsCard) {
					AdvSettingsCard.classList.remove('hidden');
					AdvSettingsTitle[AdvSettingsTitle.length - 1].classList.remove('hidden');
					AdvSettingsForm[AdvSettingsForm.length - 1].classList.remove('hidden');
				}
			}
		}

		document.getElementById('enable_advanced_settings').addEventListener('click', showAdvanced);
	}
}
