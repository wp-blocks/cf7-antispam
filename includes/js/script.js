/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./includes/src/script.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./includes/src/script.js":
/*!********************************!*\
  !*** ./includes/src/script.js ***!
  \********************************/
/*! no static exports found */
/***/ (function(module, exports) {

/* global cf7a_settings */
(function ($) {
  'use strict';

  const cf7a_prefix = cf7a_settings.prefix;

  let testTouch = () => {
    if ("maxTouchPoints" in navigator) {
      testTouch = navigator.maxTouchPoints > 0;
    } else if ("msMaxTouchPoints" in navigator) {
      testTouch = navigator.msMaxTouchPoints > 0;
    } else {
      var mQ = window.matchMedia && matchMedia("(pointer:coarse)");

      if (mQ && mQ.media === "(pointer:coarse)") {
        testTouch = !!mQ.matches;
      } else if ('orientation' in window) {
        testTouch = true; // deprecated, but good fallback
      } else {
        // Only as a last resort, fall back to user agent sniffing
        var UA = navigator.userAgent;
        testTouch = /\b(BlackBerry|webOS|iPhone|IEMobile)\b/i.test(UA) || /\b(Android|Windows Phone|iPad|iPod)\b/i.test(UA);
      }
    }

    return testTouch;
  };

  const browserFingerprint = () => {
    var _Intl$DateTimeFormat$, _navigator$platform, _navigator$hardwareCo, _ref, _navigator$deviceMemo, _navigator$appVersion, _ref2;

    // as reference https://developer.mozilla.org/en-US/docs/Web/API/Navigator/hardwareConcurrency
    const ua = navigator.userAgent;
    let tests = {
      "timezone": (_Intl$DateTimeFormat$ = Intl.DateTimeFormat().resolvedOptions().timeZone) !== null && _Intl$DateTimeFormat$ !== void 0 ? _Intl$DateTimeFormat$ : null,
      "platform": (_navigator$platform = navigator.platform) !== null && _navigator$platform !== void 0 ? _navigator$platform : null,
      "hardware_concurrency": (_navigator$hardwareCo = navigator.hardwareConcurrency) !== null && _navigator$hardwareCo !== void 0 ? _navigator$hardwareCo : null,
      "screens": (_ref = [screen.width, screen.height]) !== null && _ref !== void 0 ? _ref : null,
      "memory": (_navigator$deviceMemo = navigator.deviceMemory) !== null && _navigator$deviceMemo !== void 0 ? _navigator$deviceMemo : null,
      "user_agent": ua !== null && ua !== void 0 ? ua : null,
      "app_version": (_navigator$appVersion = navigator.appVersion) !== null && _navigator$appVersion !== void 0 ? _navigator$appVersion : null,
      "webdriver": (_ref2 = navigator.webdriver === false) !== null && _ref2 !== void 0 ? _ref2 : null,
      "session_storage": sessionStorage ? 1 : null
    }; // detect browser
    // https://developer.mozilla.org/en-US/docs/Web/API/Window/navigator

    if (ua.indexOf("Firefox") > -1) {
      tests.isFFox = true;
    } else if (ua.indexOf("SamsungBrowser") > -1) {
      tests.isSamsung = true;
    } else if (ua.indexOf("Opera") > -1 || ua.indexOf("OPR") > -1) {
      tests.isOpera = true;
    } else if (ua.indexOf("Trident") > -1) {
      tests.isIE = true;
    } else if (ua.indexOf("Edge") > -1) {
      tests.isIELegacy = true;
    } else if (ua.indexOf("Edg") > -1) {
      tests.isEdge = true;
    } else if (ua.indexOf("Chrome") > -1 || ua.indexOf("CriOS") > -1) {
      // crios stands for chrome for ios...
      tests.isChrome = true;
    } else if (ua.indexOf("Safari") > -1) {
      tests.isSafari = true;
    } else {
      tests.isUnknown = true;
    }

    if (typeof navigator.standalone === 'boolean') {
      // Available on Apple's iOS Safari only, I can detect ios in this way - https://developer.mozilla.org/en-US/docs/Web/API/Navigator#non-standard_properties
      tests.isIos = true;
    } else if (ua.indexOf("Android") > -1) {
      tests.isAndroid = true;
    }

    if (tests.isIos || tests.isAndroid || testTouch()) tests.touch = true;
    return tests;
  };

  const wpcf7Forms = document.querySelectorAll('.wpcf7');

  const createCF7Afield = (key, value, prefix = cf7a_prefix) => {
    let e = document.createElement('input');
    e.setAttribute("type", "hidden");
    e.setAttribute("name", prefix + key);
    e.setAttribute("value", typeof value === 'string' ? value : JSON.stringify(value));
    return e;
  };

  if (wpcf7Forms.length) {
    let oldy = 0,
        mouseMove_value = 0,
        mouseActivity_value = 0;

    for (const wpcf7Form of wpcf7Forms) {
      const hiddenInputsContainer = $(wpcf7Form)[0].querySelector('form > div'); // 1) Standard bot checks

      const bot_fingerprint_key = $(hiddenInputsContainer)[0].querySelector('input[name=' + cf7a_prefix + 'bot_fingerprint]'); // 2) Bot fingerprint extra checks

      const bot_fingerprint_extra = $(hiddenInputsContainer)[0].querySelector('input[name=' + cf7a_prefix + 'bot_fingerprint_extras]'); // how append bot fingerprint into hidden fields

      const append_on_submit = $(hiddenInputsContainer)[0].querySelector('input[name=' + cf7a_prefix + 'append_on_submit]');
      let tests = {};

      if (bot_fingerprint_key) {
        // 1.0 hijack the value of the bot_fingerprint
        bot_fingerprint_key.setAttribute("value", bot_fingerprint_key.getAttribute("value").slice(0, 5)); // 1.1) test browser fingerprint

        tests = browserFingerprint(); // then append the fields on submit

        if (append_on_submit) {
          const formElem = $(wpcf7Form)[0].querySelector('form');
          let formData = new FormData(formElem.formData);
          formElem.addEventListener('formdata', e => {
            for (const [key, value] of Object.entries(tests).sort(() => Math.random() - 0.5)) {
              e.formData.append(cf7a_prefix + key, value);
            }

            formData = e.formData;
          });
        } else {
          // or add them directly to hidden input container
          for (const [key, value] of Object.entries(tests).sort(() => Math.random() - 0.5)) {
            $(hiddenInputsContainer)[0].append(createCF7Afield(key, value));
          }
        }
      } // 2) Bot fingerprint extra checks


      if (bot_fingerprint_extra) {
        // 2.1) check for mouse clicks
        const activity = function (e) {
          let bot_activity = $(hiddenInputsContainer)[0].querySelector('input[name=' + cf7a_prefix + 'activity]');
          if (bot_activity) bot_activity.remove();
          $(hiddenInputsContainer)[0].append(createCF7Afield("activity", mouseActivity_value++));

          if (mouseActivity_value > 3) {
            document.body.removeEventListener('mouseup', activity);
            document.body.removeEventListener('touchend', activity);
            $(hiddenInputsContainer)[0].append(createCF7Afield("mouseclick_activity", "passed"));
          }
        };

        document.body.addEventListener('mouseup', activity);
        document.body.addEventListener('touchend', activity); // 2.2) detect the mouse/touch direction change OR touchscreen iterations

        const mouseMove = function (e) {
          if (e.pageY > oldy) mouseMove_value += 1;
          oldy = e.pageY;

          if (mouseMove_value > 3) {
            document.removeEventListener('mousemove', mouseMove);
            $(hiddenInputsContainer)[0].append(createCF7Afield("mousemove_activity", "passed"));
          }
        };

        document.addEventListener('mousemove', mouseMove); // set mousemove_activity true as fallback in mobile devices (we have already tested the ability to use the touchscreen)

        if (tests.isIos || tests.isAndroid) {
          $(hiddenInputsContainer)[0].append(createCF7Afield("mousemove_activity", "passed"));
        } // 2.3) WebGL Tests
        // credits //bot.sannysoft.com


        let wpcf7box = document.createElement('div');
        wpcf7box.id = 'hidden';
        hiddenInputsContainer.append(wpcf7box);

        String.prototype.hashCode = function () {
          var hash = 0,
              i,
              chr;
          if (this.length === 0) return hash;

          for (i = 0; i < this.length; i++) {
            chr = this.charCodeAt(i);
            hash = (hash << 5) - hash + chr;
            hash |= 0; // Convert to 32bit integer
          }

          return hash;
        };

        let wglv = document.createElement('div');
        wglv.id = 'webgl-vendor';
        wpcf7box.append(wglv);
        const webGLVendorElement = document.getElementById('webgl-vendor');
        let wgle = document.createElement('div');
        wgle.id = 'webgl-renderer';
        wpcf7box.append(wgle);
        const webGLRendererElement = document.getElementById('webgl-renderer');
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('webgl-experimental');

        if (gl) {
          const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');

          try {
            // WebGL Vendor Test
            const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
            webGLVendorElement.innerHTML = vendor;

            if (vendor === 'Brian Paul' || vendor === "Google Inc.") {
              $(hiddenInputsContainer)[0].append(createCF7Afield("webgl", "failed"));
            } else {
              $(hiddenInputsContainer)[0].append(createCF7Afield("webgl", "passed"));
            }
          } catch (e) {
            webGLVendorElement.innerHTML = "Error: " + e;
          }

          try {
            // WebGL Renderer Test
            const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
            webGLRendererElement.innerHTML = renderer;

            if (renderer === 'Mesa OffScreen' || renderer.indexOf("Swift") !== -1) {
              $(hiddenInputsContainer)[0].append(createCF7Afield("webgl_render", "failed"));
            } else $(hiddenInputsContainer)[0].append(createCF7Afield("webgl_render", "passed"));
          } catch (e) {
            webGLRendererElement.innerHTML = "Error: " + e;
          }
        } else {
          $(hiddenInputsContainer)[0].append(createCF7Afield("webgl", "failed"));
          $(hiddenInputsContainer)[0].append(createCF7Afield("webgl_render", "failed"));
        } // TODO: change the canvas name


        let testCanvas = [];
        let testCanvasIframe = [];
        testCanvas[1] = document.createElement('div');
        testCanvas[1].id = 'canvas1';
        testCanvas[2] = document.createElement('div');
        testCanvas[2].id = 'canvas2';
        testCanvas[3] = document.createElement('div');
        testCanvas[3].id = 'canvas3';
        testCanvasIframe[3] = document.createElement('iframe');
        testCanvasIframe[3].id = 'canvas3-iframe';
        testCanvasIframe[3].class = 'canvased';
        testCanvasIframe[3].setAttribute("sandbox", "allow-same-origin");
        testCanvas[3].append(testCanvasIframe[3]);
        testCanvas[4] = document.createElement('div');
        testCanvas[4].id = 'canvas4';
        testCanvasIframe[4] = document.createElement('iframe');
        testCanvasIframe[4].id = 'canvas4-iframe';
        testCanvasIframe[4].class = 'canvased';
        testCanvasIframe[4].setAttribute("sandbox", "allow-same-origin");
        testCanvas[4].append(testCanvasIframe[4]);
        testCanvas[5] = document.createElement('div');
        testCanvas[5].id = 'canvas5';
        testCanvasIframe[5] = document.createElement('iframe');
        testCanvasIframe[5].id = 'canvas5-iframe';
        testCanvasIframe[5].class = 'canvased';
        testCanvas[5].append(testCanvasIframe[5]);
        testCanvas.forEach(function (e) {
          wpcf7box.appendChild(e);
        });

        let drawCanvas2 = function (num, useIframe = false) {
          var canvas2d;
          /** @type {boolean} */

          var isOkCanvas = true;
          /** @type {string} */

          var canvasText = "Bot test <canvas> 1.1";
          let canvasContainer = document.getElementById("canvas" + num);
          let iframe = document.getElementById("canvas" + num + "-iframe");
          var canvasElement = useIframe ? iframe.contentDocument.createElement("canvas") : document.createElement("canvas");

          if (canvasElement.getContext) {
            canvas2d = canvasElement.getContext("2d");

            try {
              canvasElement.setAttribute("width", 220);
              canvasElement.setAttribute("height", 30);
              canvas2d.textBaseline = "top";
              canvas2d.font = "14px 'Arial'";
              canvas2d.textBaseline = "alphabetic";
              canvas2d.fillStyle = "#f60";
              canvas2d.fillRect(53, 1, 62, 20);
              canvas2d.fillStyle = "#069";
              canvas2d.fillText(canvasText, 2, 15);
              canvas2d.fillStyle = "rgba(102, 204, 0, 0.7)";
              canvas2d.fillText(canvasText, 4, 17);
            } catch (b) {
              /** @type {!Element} */
              canvasElement = document.createElement("canvas");
              canvas2d = canvasElement.getContext("2d");

              if (void 0 === canvas2d || "function" != typeof canvasElement.getContext("2d").fillText) {
                isOkCanvas = false;
              } else {
                canvasElement.setAttribute("width", 220);
                canvasElement.setAttribute("height", 30);
                /** @type {string} */

                canvas2d.textBaseline = "top";
                /** @type {string} */

                canvas2d.font = "14px 'Arial'";
                /** @type {string} */

                canvas2d.textBaseline = "alphabetic";
                /** @type {string} */

                canvas2d.fillStyle = "#f60";
                canvas2d.fillRect(125, 1, 62, 20);
                /** @type {string} */

                canvas2d.fillStyle = "#069";
                canvas2d.fillText(canvasText, 2, 15);
                /** @type {string} */

                canvas2d.fillStyle = "rgba(102, 204, 0, 0.7)";
                canvas2d.fillText(canvasText, 4, 17);
              }
            }

            if (isOkCanvas && "function" == typeof canvasElement.toDataURL) {
              var datUrl = canvasElement.toDataURL("image/png");

              try {
                if ("boolean" == typeof datUrl || void 0 === datUrl) {
                  throw e;
                }
              } catch (a) {
                /** @type {string} */
                datUrl = "";
              }

              if (0 === datUrl.indexOf("data:image/png")) {} else {
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

          if (isOkCanvas) {
            let newDiv = document.createElement("div");
            newDiv.innerHTML = "Hash: " + datUrl.hashCode();
            canvasContainer.appendChild(canvasElement);
            canvasContainer.appendChild(newDiv);
          } else {
            let newDiv = document.createElement("div");
            newDiv.innerHTML = "Canvas failed";
            canvasContainer.appendChild(newDiv);
          }
        };

        window.canvasCount = 0;
        drawCanvas2("1");
        drawCanvas2("2");
        drawCanvas2("3", true);
        drawCanvas2("4", true);
        drawCanvas2("5", true); // then remove the useless div

        wpcf7box.remove();
      }
    }
  }
})(jQuery);

/***/ })

/******/ });