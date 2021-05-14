( function( $ ) {

  'use strict';

  function browserFingerprint() {
    let tests;

    tests = {
      timezone : window.Intl.DateTimeFormat().resolvedOptions().timeZone,
      platform : navigator.platform,
      hardware_concurrency : navigator.hardwareConcurrency,
      screens : [window.screen.width, window.screen.height],
      memory : navigator.deviceMemory,
      user_agent : navigator.userAgent,
      app_version : navigator.appVersion,
      webdriver : window.navigator.webdriver,
      session_storage : sessionStorage !== void 0
    };

    return tests;
  }

  const wpcf7Forms = document.querySelectorAll( '.wpcf7' );

  if (wpcf7Forms.length) {
    for (const wpcf7Form of wpcf7Forms) {

      const tests = browserFingerprint();

      for (const [key, value] of Object.entries(tests)) {
        let e = document.createElement('input');
        e.setAttribute("type", "hidden");
        e.setAttribute("name", '_wpcf7a_' + key );
        e.setAttribute("value", value );
        $(wpcf7Form).find('form > div').append(e);
      }

      let key = $(wpcf7Form)[0].querySelector('form > div input[name=_wpcf7a_bot_fingerprint]').getAttribute("value");
      $(wpcf7Form)[0].querySelector('form > div input[name=_wpcf7a_bot_fingerprint]').setAttribute("value", key.slice(0,5) );
    }
  }

} )( jQuery );
