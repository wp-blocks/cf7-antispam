( function( $ ) {

  'use strict';

  function browserFingerprint() {
    let tests;

    tests = {
      "activity" : 0,
      "timezone" : window.Intl.DateTimeFormat().resolvedOptions().timeZone,
      "platform" : navigator.platform,
      "hardware_concurrency" : navigator.hardwareConcurrency,
      "screens" : [window.screen.width, window.screen.height],
      "memory" : navigator.deviceMemory,
      "user_agent" : navigator.userAgent,
      "app_version" : navigator.appVersion,
      "webdriver" : window.navigator.webdriver,
      "session_storage" : sessionStorage !== void 0
    };

    return tests;
  }

  const wpcf7Forms = document.querySelectorAll( '.wpcf7' );

  if (wpcf7Forms.length) {
    for (const wpcf7Form of wpcf7Forms) {

      const tests = browserFingerprint();

      for (const [key, value] of Object.entries(tests).sort(() => Math.random() - 0.5)) {
        let e = document.createElement('input');
        e.setAttribute("type", "hidden");
        e.setAttribute("name", '_wpcf7a_' + key );
        e.setAttribute("value", value );
        $(wpcf7Form).find('form > div').append(e);
      }

      let bot_fingerprint_key = $(wpcf7Form)[0].querySelector('form > div input[name=_wpcf7a_bot_fingerprint]');
      let fingerprint_key = bot_fingerprint_key.getAttribute("value");
      bot_fingerprint_key.setAttribute("value", fingerprint_key.slice(0,5) );

      let activity = $(wpcf7Form)[0].querySelector('form > div input[name=_wpcf7a_activity]');
      let activity_value = 0;

      document.body.addEventListener('click', function (e) {
        activity.setAttribute("value", activity_value++ );
      }, true);

    }
  }

} )( jQuery );
