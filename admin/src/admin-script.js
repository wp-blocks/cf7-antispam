let jquery = ( function($) {
  $( function() {
    var welcomePanel = $('#welcome-panel');

    $( 'a.welcome-panel-close', welcomePanel ).click( function( event ) {
      event.preventDefault();
      welcomePanel.addClass( 'hidden' )
    });
  });
});

// save on ctrl-s keypress
if (document.body.classList.contains('cf7-antispam-admin')) {
  document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 's') {
      e.preventDefault();
      document.getElementById('submit').click();
    }
  });
}