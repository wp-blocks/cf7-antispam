( function( $ ) {
  // save on ctrl-s keypress
  if (document.body.classList.contains('contact_page_cf7-antispam')) {
    document.addEventListener('keydown', e => {
      if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('submit').click();
      }
    });
  }

  $( function() {
    var welcomePanel = $('#welcome-panel');

    $( 'a.welcome-panel-close', welcomePanel ).click( function( event ) {
      event.preventDefault();
      welcomePanel.addClass( 'hidden' )
    });
  });
});