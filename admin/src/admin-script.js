// save on ctrl-s keypress
if (document.body.classList.contains('cf7-antispam-admin')) {

	let jquery = ( function($) {
		$( function() {
			var welcomePanel = $('#welcome-panel');

			// welcome panel script needed to hide the div
			$( 'a.welcome-panel-close', welcomePanel ).click( function( event ) {
				event.preventDefault();
				welcomePanel.addClass( 'hidden' )
			});
		});
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
		if (advancedCheckbox.checked !== true) {
			document.getElementById('advanced-setting-card').classList.add('hidden');
			AdvSettingsTitle[AdvSettingsTitle.length - 1].classList.add('hidden');
			AdvSettingsForm[AdvSettingsForm.length - 1].classList.add('hidden');
		} else {
			document.getElementById('advanced-setting-card').classList.remove('hidden');
			AdvSettingsTitle[AdvSettingsTitle.length - 1].classList.remove('hidden');
			AdvSettingsForm[AdvSettingsForm.length - 1].classList.remove('hidden');
		}
	}

	showAdvanced();
	document.getElementById('enable_advanced_settings').addEventListener('click', showAdvanced );
}
