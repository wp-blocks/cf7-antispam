window.onload = function () {
	/* This is the code that adds the confirmation alert to the delete buttons on the settings page. */
	if (
		document.body.classList.contains( 'cf7-antispam-admin' ) ||
		document.body.classList.contains( 'flamingo_page_flamingo_inbound' )
	) {
		// eslint-disable-next-line
    const alertMessage = cf7a_admin_settings.alertMessage;

		// the confirmation alert script
		const alerts = document.querySelectorAll( '.cf7a_alert' );

		function confirmationAlert( e, message ) {
			// eslint-disable-next-line no-alert,no-undef
			if ( confirm( message || alertMessage ) )
				window.location.href = e.dataset.href;
		}

		alerts.forEach( ( alert ) => {
			alert.addEventListener( 'click', () => {
				confirmationAlert( alert, alert.dataset.message || false );
			} );
		} );
	}

	/* This is the code that saves the settings when the user presses ctrl-s. */
	if ( document.body.classList.contains( 'cf7-antispam-admin' ) ) {
		// save on ctrl-s keypress
		document.addEventListener( 'keydown', ( e ) => {
			if ( e.ctrlKey && e.key === 's' ) {
				e.preventDefault();
				document.getElementById( 'submit' ).click();
			}
		} );
	}

	/* This is the code that hides the welcome panel,
    and shows the advanced settings. */
	if ( document.body.classList.contains( 'cf7-antispam-admin' ) ) {
		// shows the advanced section
		const showAdvanced = () => {
			const advancedCheckbox = document.getElementById(
				'enable_advanced_settings'
			);
			const AdvSettingsCard = document.getElementById(
				'advanced-setting-card'
			);
			const AdvSettingsTitle =
				document.querySelectorAll( '#cf7a_settings h2' );
			const AdvSettingsTitleEl =
				AdvSettingsTitle[ AdvSettingsTitle.length - 1 ];

			const AdvSettingsTxt =
				document.querySelectorAll( '#cf7a_settings p' );
			const AdvSettingsTxtEl =
				AdvSettingsTxt[ AdvSettingsTxt.length - 2 ];

			const AdvSettingsForm = document.querySelectorAll(
				'#cf7a_settings table'
			);
			const AdvSettingsFormEl =
				AdvSettingsForm[ AdvSettingsForm.length - 1 ];

			if ( advancedCheckbox.checked !== false ) {
				if ( AdvSettingsCard )
					AdvSettingsCard.classList.remove( 'hidden' );

				AdvSettingsTitleEl.classList.remove( 'hidden' );
				AdvSettingsTxtEl.classList.remove( 'hidden' );
				AdvSettingsFormEl.classList.remove( 'hidden' );
			} else {
				if ( AdvSettingsCard )
					AdvSettingsCard.classList.add( 'hidden' );

				AdvSettingsTitleEl.classList.add( 'hidden' );
				AdvSettingsTxtEl.classList.add( 'hidden' );
				AdvSettingsFormEl.classList.add( 'hidden' );
			}
		};
		/* on click show advanced options */
		document
			.getElementById( 'enable_advanced_settings' )
			.addEventListener( 'click', showAdvanced );
		showAdvanced();
	}
};
