// save on ctrl-s keypress
if (document.body.classList.contains('cf7-antispam-admin')) {

	const welcomePanel = document.getElementById('welcome-panel');
	const welcomePanelCloseBtn = welcomePanel.querySelector('a.welcome-panel-close');

	welcomePanelCloseBtn.click( function( event ) {
		event.preventDefault();
		welcomePanel.classList.add( 'hidden' )
	});

	// saves on ctrl-s
  document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 's') {
      e.preventDefault();
      document.getElementById('submit').click();
    }
  });

  // show the advanced section
	showAdvanced = () => {
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

	showAdvanced();
	document.getElementById('enable_advanced_settings').addEventListener('click', showAdvanced );
}
