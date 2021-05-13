( function( $ ) {

  'use strict';

  console.log('CF7-AntiSpam is ready! (user scripts)');

  var wpcf7Elm = document.querySelector( '.wpcf7' );

  wpcf7Elm.addEventListener( 'wpcf7submit', function( event ) {
    alert( "Fire!" );
  }, false );

} )( jQuery );
