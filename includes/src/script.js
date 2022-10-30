/* eslint-disable camelcase */
/* global cf7a_settings, wpcf7 */
'use strict';

window.onload = function () {
	// disable cf7 antispam script if contact form is not loaded in this page
	if ( ! window.wpcf7 ) return;

	const cf7aPrefix = cf7a_settings.prefix;
	const cf7aVersion = cf7a_settings.version;

	// disable cf7 refill on load
	wpcf7.cached =
		parseInt( cf7a_settings.disableReload ) === 0 && wpcf7.cached; // if is cached disable reload

	/**
	 * If the browser supports the `maxTouchPoints` property, then return the value of that property. If the browser supports
	 * the `msMaxTouchPoints` property, then return the value of that property. If the browser supports the `matchMedia`
	 * method, then return the value of the `matches` property of the `matchMedia` method. If the browser supports the
	 * `orientation` property, then return `true`. If the browser doesn't support any of the above, then return the value of
	 * the `test` method of the `RegExp` object
	 *
	 * @return {boolean} if has touchscreen or not
	 */
	function testTouch() {
		let hasTouch;
		if ( 'maxTouchPoints' in window.navigator ) {
			hasTouch = window.navigator.maxTouchPoints > 0;
		} else if ( 'msMaxTouchPoints' in window.navigator ) {
			hasTouch = window.navigator.msMaxTouchPoints > 0;
		} else {
			const mQ =
				window.matchMedia && window.matchMedia( '(pointer:coarse)' );
			if ( mQ && mQ.media === '(pointer:coarse)' ) {
				hasTouch = !! mQ.matches;
			} else if ( 'orientation' in window ) {
				hasTouch = true; // deprecated, but good fallback
			} else {
				// Only as a last resort, fall back to user agent sniffing
				const UA = window.navigator.userAgent;
				hasTouch =
					/\b(BlackBerry|webOS|iPhone|IEMobile)\b/i.test( UA ) ||
					/\b(Android|Windows Phone|iPad|iPod)\b/i.test( UA );
			}
		}
		return hasTouch;
	}

	/**
	 * If the user is on an iOS device, return true
	 * https://stackoverflow.com/questions/9038625/detect-if-device-is-ios
	 *
	 * @return {boolean} true if the device is apple ios
	 */
	function isIOS() {
		return (
			[
				'iPad Simulator',
				'iPhone Simulator',
				'iPod Simulator',
				'iPad',
				'iPhone',
				'iPod',
			].includes( navigator.platform ) ||
			// iPad on iOS 13 detection
			( navigator.userAgent.includes( 'Mac' ) &&
				'ontouchend' in document )
		);
	}

	/**
	 * It returns an object with the browser's name, version, and other information
	 *
	 * @return {Object} An object with the following properties:
	 * 	- isFFox
	 * 	- isSamsung
	 * 	- isOpera
	 * 	- isIE
	 * 	- isIELegacy
	 * 	- isEdge
	 * 	- isChrome
	 * 	- isSafari
	 * 	- isUnknown
	 * 	- isIos
	 * 	- isAndroid
	 * 	- touch
	 */
	const browserFingerprint = () => {
		const ua = window.navigator.userAgent;

		// holds the object with the tested props
		const tests = {
			timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
			platform: window.navigator.platform || null,
			screens: [ window.screen.width, window.screen.height ] || null,
			memory: window.navigator.deviceMemory || null,
			user_agent: ua || null,
			app_version: window.navigator.appVersion || null,
			webdriver: window.navigator.webdriver === false || null,
			session_storage: window.sessionStorage ? 1 : null,
		};

		// detect browser
		// https://developer.mozilla.org/en-US/docs/Web/API/Window/navigator
		if ( ua.indexOf( 'Firefox' ) > -1 ) {
			tests.isFFox = true;
		} else if ( ua.indexOf( 'SamsungBrowser' ) > -1 ) {
			tests.isSamsung = true;
		} else if ( ua.indexOf( 'Opera' ) > -1 || ua.indexOf( 'OPR' ) > -1 ) {
			tests.isOpera = true;
		} else if ( ua.indexOf( 'Trident' ) > -1 ) {
			tests.isIE = true;
		} else if ( ua.indexOf( 'Edge' ) > -1 ) {
			tests.isIELegacy = true;
		} else if ( ua.indexOf( 'Edg' ) > -1 ) {
			tests.isEdge = true;
		} else if (
			ua.indexOf( 'Chrome' ) > -1 ||
			ua.indexOf( 'CriOS' ) > -1
		) {
			// criOS stands for chrome for ios
			tests.isChrome = true;
		} else if ( ua.indexOf( 'Safari' ) > -1 || ua.indexOf( 'GSA' ) > -1 ) {
			// GSA stand for Google Search Appliance
			tests.isSafari = true;
		} else {
			tests.isUnknown = true;
		}

		if ( isIOS() ) {
			tests.isIos = true;
		} else if ( ua.indexOf( 'Android' ) > -1 ) {
			tests.isAndroid = true;
		}

		if ( tests.isIos || tests.isAndroid ) tests.touch = testTouch();

		return tests;
	};

	/**
	 * It returns the browser language.
	 *
	 * @return {string} The language of the browser.
	 */
	const getBrowserLanguage = () => {
		return (
			window.navigator.languages.join() ||
			window.navigator.language ||
			window.navigator.browserLanguage ||
			window.navigator.userLanguage
		);
	};

	/**
	 * It creates a hidden input field with a name and value
	 *
	 * @param {string} key      - the name of the field
	 * @param {string} value    - The value of the field.
	 * @param {string} [prefix] - The prefix for the field name.
	 *
	 * @return {HTMLElement} A new input element with the type of hidden, name of the key, and value of the value.
	 */
	const createCF7Afield = ( key, value, prefix = cf7aPrefix ) => {
		const e = document.createElement( 'input' );
		e.setAttribute( 'type', 'hidden' );
		e.setAttribute( 'name', prefix + key );
		e.setAttribute(
			'value',
			typeof value === 'string' ? value : JSON.stringify( value )
		);
		return e;
	};

	// get all page forms
	const wpcf7Forms = document.querySelectorAll( '.wpcf7' );

	if ( wpcf7Forms.length ) {
		let oldy = 0,
			mouseMoveValue = 0,
			mouseActivityValue = 0;

		for ( const wpcf7Form of wpcf7Forms ) {
			const hiddenInputsContainer =
				wpcf7Form.querySelector( 'form > div' );

			// 1) Standard bot checks
			const botFingerprintKey = hiddenInputsContainer.querySelector(
				'input[name=' + cf7aPrefix + 'bot_fingerprint]'
			);

			// 2) Bot fingerprint extra checks
			const botFingerprintExtra = hiddenInputsContainer.querySelector(
				'input[name=' + cf7aPrefix + 'bot_fingerprint_extras]'
			);

			// 3) Language check
			const languageChecksEnabled = hiddenInputsContainer.querySelector(
				'input[name=' + cf7aPrefix + '_language]'
			);

			// how append bot fingerprint into hidden fields
			const appendOnSubmit = hiddenInputsContainer.querySelector(
				'input[name=' + cf7aPrefix + 'append_on_submit]'
			);

			// how append bot fingerprint into hidden fields
			const cf7aVersionInput = hiddenInputsContainer.querySelector(
				'input[name=' + cf7aPrefix + 'version]'
			);

			// get the fake field and skip it
			if (
				wpcf7Form.querySelector( 'form' ).getAttribute( 'autocomplete' )
			)
				continue;

			// then set the cf7 antispam version field
			cf7aVersionInput.setAttribute( 'value', cf7aVersion );

			// fingerprint browser data
			const tests = browserFingerprint();

			if ( botFingerprintKey ) {
				// 1.0 hijack the value of the bot_fingerprint
				botFingerprintKey.setAttribute(
					'value',
					botFingerprintKey.getAttribute( 'value' ).slice( 0, 5 )
				);

				/**
				 * then append the fields on submit
				 * not supported in safari <11.3 https://developer.mozilla.org/en-US/docs/Web/API/HTMLFormElement/formdata_event#browser_compatibility
				 * update 2022/10: finally safari seems to support decently and widely formData! adding anyway a check to avoid failures with old browsers
				 */
				if (
					! appendOnSubmit ||
					tests.isIos ||
					tests.isIE ||
					!! window.FormData
				) {
					// or add them directly to hidden input container
					for ( const key in tests ) {
						hiddenInputsContainer.appendChild(
							createCF7Afield( key, tests[ key ] )
						);
					}
				} else {
					const formElem = wpcf7Form.querySelector( 'form' );
					new window.FormData( formElem.formData );

					formElem.addEventListener( 'formdata', ( e ) => {
						const data = e.formData;
						for ( const key in tests ) {
							data.append( cf7aPrefix + key, tests[ key ] );
						}
						return data;
					} );
				}
			}

			// 2) Bot fingerprint extra checks
			if ( botFingerprintExtra ) {
				// 2.1) check for mouse clicks
				const activity = function () {
					const botActivity = hiddenInputsContainer.querySelector(
						'input[name=' + cf7aPrefix + 'activity]'
					);
					if ( botActivity ) botActivity.remove();
					hiddenInputsContainer.append(
						createCF7Afield( 'activity', mouseActivityValue++ )
					);

					if ( mouseActivityValue > 3 ) {
						document.body.removeEventListener(
							'mouseup',
							activity
						);
						document.body.removeEventListener(
							'touchend',
							activity
						);
						hiddenInputsContainer.append(
							createCF7Afield( 'mouseclick_activity', 'passed' )
						);
					}
				};
				document.body.addEventListener( 'mouseup', activity );
				document.body.addEventListener( 'touchend', activity );

				// 2.2) detect the mouse/touch direction change OR touchscreen iterations
				const mouseMove = function ( e ) {
					if ( e.pageY > oldy ) mouseMoveValue += 1;
					oldy = e.pageY;

					if ( mouseMoveValue > 3 ) {
						document.removeEventListener( 'mousemove', mouseMove );
						hiddenInputsContainer.append(
							createCF7Afield( 'mousemove_activity', 'passed' )
						);
					}
				};
				document.addEventListener( 'mousemove', mouseMove );

				// set mousemove_activity true as fallback in mobile devices (we have already tested the ability to use the touchscreen)
				if ( tests.isIos || tests.isAndroid ) {
					hiddenInputsContainer.append(
						createCF7Afield( 'mousemove_activity', 'passed' )
					);
				}

				// 2.3) WebGL Tests
				// credits //bot.sannysoft.com
				const wpcf7box = document.createElement( 'div' );
				wpcf7box.id = 'hidden';
				hiddenInputsContainer.append( wpcf7box );
				String.prototype.hashCode = function () {
					let hash = 0,
						i,
						chr;
					if ( this.length === 0 ) return hash;
					for ( i = 0; i < this.length; i++ ) {
						chr = this.charCodeAt( i );
						// eslint-disable-next-line no-bitwise
						hash = ( hash << 5 ) - hash + chr;
						// eslint-disable-next-line no-bitwise
						hash |= 0; // Convert to 32bit integer
					}
					return hash;
				};

				const wglv = document.createElement( 'div' );
				wglv.id = 'webgl-vendor';
				wpcf7box.append( wglv );
				const webGLVendorElement =
					document.getElementById( 'webgl-vendor' );
				const wgle = document.createElement( 'div' );
				wgle.id = 'webgl-renderer';
				wpcf7box.append( wgle );
				const webGLRendererElement =
					document.getElementById( 'webgl-renderer' );
				const canvas = document.createElement( 'canvas' );
				const gl =
					canvas.getContext( 'webgl' ) ||
					canvas.getContext( 'webgl-experimental' );

				if ( gl ) {
					const debugInfo = gl.getExtension(
						'WEBGL_debug_renderer_info'
					);

					try {
						// WebGL Vendor Test
						const vendor = gl.getParameter(
							debugInfo.UNMASKED_VENDOR_WEBGL
						);
						webGLVendorElement.innerHTML = vendor;
						if (
							vendor === 'Brian Paul' ||
							vendor === 'Google Inc.'
						) {
							hiddenInputsContainer.append(
								createCF7Afield( 'webgl', 'failed' )
							);
						} else {
							hiddenInputsContainer.append(
								createCF7Afield( 'webgl', 'passed' )
							);
						}
					} catch ( e ) {
						webGLVendorElement.innerHTML = 'Error: ' + e;
					}

					try {
						// WebGL Renderer Test
						const renderer = gl.getParameter(
							debugInfo.UNMASKED_RENDERER_WEBGL
						);
						webGLRendererElement.innerHTML = renderer;
						if (
							renderer === 'Mesa OffScreen' ||
							renderer.indexOf( 'Swift' ) !== -1
						) {
							hiddenInputsContainer.append(
								createCF7Afield( 'webgl_render', 'failed' )
							);
						} else
							hiddenInputsContainer.append(
								createCF7Afield( 'webgl_render', 'passed' )
							);
					} catch ( e ) {
						webGLRendererElement.innerHTML = 'Error: ' + e;
					}
				} else {
					hiddenInputsContainer.append(
						createCF7Afield( 'webgl', 'failed' )
					);
					hiddenInputsContainer.append(
						createCF7Afield( 'webgl_render', 'failed' )
					);
				}

				// TODO: change the canvas name
				const testCanvas = [];
				const testCanvasIframe = [];
				testCanvas[ 1 ] = document.createElement( 'div' );
				testCanvas[ 1 ].id = 'canvas1';

				testCanvas[ 2 ] = document.createElement( 'div' );
				testCanvas[ 2 ].id = 'canvas2';

				testCanvas[ 3 ] = document.createElement( 'div' );
				testCanvas[ 3 ].id = 'canvas3';
				testCanvasIframe[ 3 ] = document.createElement( 'iframe' );
				testCanvasIframe[ 3 ].id = 'canvas3-iframe';
				testCanvasIframe[ 3 ].class = 'canvased';
				testCanvasIframe[ 3 ].setAttribute(
					'sandbox',
					'allow-same-origin'
				);
				testCanvas[ 3 ].append( testCanvasIframe[ 3 ] );

				testCanvas[ 4 ] = document.createElement( 'div' );
				testCanvas[ 4 ].id = 'canvas4';
				testCanvasIframe[ 4 ] = document.createElement( 'iframe' );
				testCanvasIframe[ 4 ].id = 'canvas4-iframe';
				testCanvasIframe[ 4 ].class = 'canvased';
				testCanvasIframe[ 4 ].setAttribute(
					'sandbox',
					'allow-same-origin'
				);
				testCanvas[ 4 ].append( testCanvasIframe[ 4 ] );

				testCanvas[ 5 ] = document.createElement( 'div' );
				testCanvas[ 5 ].id = 'canvas5';
				testCanvasIframe[ 5 ] = document.createElement( 'iframe' );
				testCanvasIframe[ 5 ].id = 'canvas5-iframe';
				testCanvasIframe[ 5 ].class = 'canvased';
				testCanvas[ 5 ].append( testCanvasIframe[ 5 ] );

				testCanvas.forEach( function ( e ) {
					wpcf7box.appendChild( e );
				} );

				const drawCanvas2 = function ( num, useIframe = false ) {
					let datUrl;
					let canvas2d;

					/** @type {boolean} */
					let isOkCanvas = true;

					/** @type {string} */
					const canvasText = 'Bot test <canvas> 1.1';

					const canvasContainer = document.getElementById(
						'canvas' + num
					);
					const iframe = document.getElementById(
						'canvas' + num + '-iframe'
					);

					let canvasElement = useIframe
						? iframe.contentDocument.createElement( 'canvas' )
						: document.createElement( 'canvas' );

					if ( canvasElement.getContext ) {
						canvas2d = canvasElement.getContext( '2d' );

						try {
							canvasElement.setAttribute( 'width', 220 );
							canvasElement.setAttribute( 'height', 30 );

							canvas2d.textBaseline = 'top';
							canvas2d.font = "14px 'Arial'";
							canvas2d.textBaseline = 'alphabetic';
							canvas2d.fillStyle = '#f60';
							canvas2d.fillRect( 53, 1, 62, 20 );
							canvas2d.fillStyle = '#069';
							canvas2d.fillText( canvasText, 2, 15 );
							canvas2d.fillStyle = 'rgba(102, 204, 0, 0.7)';
							canvas2d.fillText( canvasText, 4, 17 );
						} catch ( b ) {
							/** @type {!Element} */
							canvasElement = document.createElement( 'canvas' );
							canvas2d = canvasElement.getContext( '2d' );
							if (
								void 0 === canvas2d ||
								'function' !==
									typeof canvasElement.getContext( '2d' )
										.fillText
							) {
								isOkCanvas = false;
							} else {
								canvasElement.setAttribute( 'width', 220 );
								canvasElement.setAttribute( 'height', 30 );
								/** @type {string} */
								canvas2d.textBaseline = 'top';
								/** @type {string} */
								canvas2d.font = "14px 'Arial'";
								/** @type {string} */
								canvas2d.textBaseline = 'alphabetic';
								/** @type {string} */
								canvas2d.fillStyle = '#f60';
								canvas2d.fillRect( 125, 1, 62, 20 );
								/** @type {string} */
								canvas2d.fillStyle = '#069';
								canvas2d.fillText( canvasText, 2, 15 );
								/** @type {string} */
								canvas2d.fillStyle = 'rgba(102, 204, 0, 0.7)';
								canvas2d.fillText( canvasText, 4, 17 );
							}
						}

						if (
							isOkCanvas &&
							'function' === typeof canvasElement.toDataURL
						) {
							datUrl = canvasElement.toDataURL( 'image/png' );
							try {
								if (
									'boolean' === typeof datUrl ||
									void 0 === datUrl
								) {
									throw new Error( 'Unable to load image' );
								}
							} catch ( a ) {
								/** @type {string} */
								datUrl = '';
							}
							if ( 0 === datUrl.indexOf( 'data:image/png' ) ) {
							} else {
								/** @type {boolean} */
								isOkCanvas = false;
							}
						} else {
							/** @type {boolean} */
							isOkCanvas = false;
						}
					} else {
						/** @type {boolean} */
						isOkCanvas = false;
					}

					if ( isOkCanvas ) {
						const newDiv = document.createElement( 'div' );
						newDiv.innerHTML = 'Hash: ' + datUrl.hashCode();
						canvasContainer.appendChild( canvasElement );
						canvasContainer.appendChild( newDiv );
					} else {
						const newDiv = document.createElement( 'div' );
						newDiv.innerHTML = 'Canvas failed';
						canvasContainer.appendChild( newDiv );
					}
				};

				window.canvasCount = 0;

				drawCanvas2( '1' );
				drawCanvas2( '2' );

				drawCanvas2( '3', true );
				drawCanvas2( '4', true );
				drawCanvas2( '5', true );

				// then remove the useless div
				wpcf7box.remove();
			}

			// 3) check the browser language
			if ( languageChecksEnabled ) {
				hiddenInputsContainer.append(
					createCF7Afield( 'browser_language', getBrowserLanguage() )
				);
			}
		}
	}
};
