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
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);


function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function (_e) { function e(_x) { return _e.apply(this, arguments); } e.toString = function () { return _e.toString(); }; return e; }(function (e) { throw e; }), f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function (_e2) { function e(_x2) { return _e2.apply(this, arguments); } e.toString = function () { return _e2.toString(); }; return e; }(function (e) { didErr = true; err = e; }), f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

(function ($) {
  'use strict';

  function browserFingerprint() {
    var tests;
    tests = {
      "timezone": window.Intl.DateTimeFormat().resolvedOptions().timeZone,
      "platform": navigator.platform,
      "hardware_concurrency": navigator.hardwareConcurrency,
      "screens": [window.screen.width, window.screen.height],
      "memory": navigator.deviceMemory,
      "user_agent": navigator.userAgent,
      "app_version": navigator.appVersion,
      "webdriver": window.navigator.webdriver,
      "plugins": navigator.plugins.length,
      "session_storage": sessionStorage !== void 0
    };
    return tests;
  }

  function browserFingerprintExtras() {
    var tests;
    tests = {
      "activity": 0
    };
    return tests;
  }

  var wpcf7Forms = document.querySelectorAll('.wpcf7');

  function createCF7Afield(key, value) {
    var e = document.createElement('input');
    e.setAttribute("type", "hidden");
    e.setAttribute("name", '_wpcf7a_' + key);
    e.setAttribute("value", value);
    return e;
  }

  var oldy = 0,
      moved = 0;

  if (wpcf7Forms.length) {
    var _iterator = _createForOfIteratorHelper(wpcf7Forms),
        _step;

    try {
      var _loop = function _loop() {
        var wpcf7Form = _step.value;
        // I) bot_fingerprint checks
        var tests = browserFingerprint();

        var _iterator2 = _createForOfIteratorHelper(Object.entries(tests).sort(function () {
          return Math.random() - 0.5;
        })),
            _step2;

        try {
          for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
            var _step2$value = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_step2.value, 2),
                _key = _step2$value[0],
                _value = _step2$value[1];

            $(wpcf7Form).find('form > div').append(createCF7Afield(_key, _value));
          } // hijack the value of the bot_fingerprint

        } catch (err) {
          _iterator2.e(err);
        } finally {
          _iterator2.f();
        }

        var bot_fingerprint_key = $(wpcf7Form)[0].querySelector('form > div input[name=_wpcf7a_bot_fingerprint]');
        var fingerprint_key = bot_fingerprint_key.getAttribute("value");
        bot_fingerprint_key.setAttribute("value", fingerprint_key.slice(0, 5)); // II) bot_fingerprint extra checks

        var bot_fingerprint_extra = $(wpcf7Form)[0].querySelector('form > div input[name=_wpcf7a_bot_fingerprint_extras]');

        if (bot_fingerprint_extra) {
          // load the extras
          var tests_extras = browserFingerprintExtras();

          var _iterator3 = _createForOfIteratorHelper(Object.entries(tests_extras).sort(function () {
            return Math.random() - 0.5;
          })),
              _step3;

          try {
            for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
              var _step3$value = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_step3.value, 2),
                  key = _step3$value[0],
                  value = _step3$value[1];

              $(wpcf7Form).find('form > div').append(createCF7Afield(key, value));
            } // check for mouse clicks

          } catch (err) {
            _iterator3.e(err);
          } finally {
            _iterator3.f();
          }

          var activity = $(wpcf7Form)[0].querySelector('form > div input[name=_wpcf7a_activity]');
          var activity_value = 0;
          document.body.addEventListener('click touchstart', function (e) {
            activity.setAttribute("value", activity_value++);
          }, true); // detect the mouse/touch movements

          var mouseMove = function mouseMove(e) {
            if (e.pageY > oldy) {
              moved += 1;
            }

            oldy = e.pageY;

            if (moved > 3) {
              document.removeEventListener('mousemove', mouseMove);
              document.removeEventListener('touchstart', onFirstTouch);
              $(wpcf7Form).find('form > div').append(createCF7Afield("mousemove_activity", "passed"));
            }
          };

          document.addEventListener('mousemove', mouseMove);

          var onFirstTouch = function onFirstTouch(e) {
            moved += 1;

            if (moved > 3) {
              document.removeEventListener('mousemove', mouseMove);
              document.removeEventListener('touchstart', onFirstTouch);
              $(wpcf7Form).find('form > div').append(createCF7Afield("touchmove_activity", "passed"));
            }
          };

          document.addEventListener('touchstart', onFirstTouch);

          var _hidden = document.createElement('div');

          _hidden.id = 'hidden';
          var form_hidden_field = $(wpcf7Form)[0].querySelector('form > div');
          form_hidden_field.append(_hidden); // credits //bot.sannysoft.com
          // tools

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
          }; // WebGL Tests


          var wglv = document.createElement('div');
          wglv.id = 'webgl-vendor';

          _hidden.append(wglv);

          var webGLVendorElement = document.getElementById('webgl-vendor');
          var wgle = document.createElement('div');
          wgle.id = 'webgl-renderer';

          _hidden.append(wgle);

          var webGLRendererElement = document.getElementById('webgl-renderer');
          var canvas = document.createElement('canvas');
          var gl = canvas.getContext('webgl') || canvas.getContext('webgl-experimental');

          if (gl) {
            var debugInfo = gl.getExtension('WEBGL_debug_renderer_info');

            try {
              // WebGL Vendor Test
              var vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
              webGLVendorElement.innerHTML = vendor;

              if (vendor === 'Brian Paul' || vendor === "Google Inc.") {
                $(wpcf7Form).find('form > div').append(createCF7Afield("webgl", "failed"));
              } else {
                $(wpcf7Form).find('form > div').append(createCF7Afield("webgl", "passed"));
              }
            } catch (e) {
              webGLVendorElement.innerHTML = "Error: " + e;
            }

            try {
              // WebGL Renderer Test
              var renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
              webGLRendererElement.innerHTML = renderer;

              if (renderer === 'Mesa OffScreen' || renderer.indexOf("Swift") !== -1) {
                $(wpcf7Form).find('form > div').append(createCF7Afield("webgl_render", "failed"));
              } else $(wpcf7Form).find('form > div').append(createCF7Afield("webgl_render", "passed"));
            } catch (e) {
              webGLRendererElement.innerHTML = "Error: " + e;
            }
          } else {
            $(wpcf7Form).find('form > div').append(createCF7Afield("webgl", "failed"));
            $(wpcf7Form).find('form > div').append(createCF7Afield("webgl_render", "failed"));
          }

          var testCanvas = [];
          var testCanvasIframe = [];
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
            _hidden.appendChild(e);
          });

          var drawCanvas2 = function drawCanvas2(num) {
            var useIframe = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
            var canvas2d;
            /** @type {boolean} */

            var isOkCanvas = true;
            /** @type {string} */

            var canvasText = "Bot test <canvas> 1.1";
            var canvasContainer = document.getElementById("canvas" + num);
            var iframe = document.getElementById("canvas" + num + "-iframe");
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
              var newDiv = document.createElement("div");
              newDiv.innerHTML = "Hash: " + datUrl.hashCode();
              canvasContainer.appendChild(canvasElement);
              canvasContainer.appendChild(newDiv);
            } else {
              var _newDiv = document.createElement("div");

              _newDiv.innerHTML = "Canvas failed";
              canvasContainer.appendChild(_newDiv);
            }
          };

          window.canvasCount = 0;
          drawCanvas2("1");
          drawCanvas2("2");
          drawCanvas2("3", true);
          drawCanvas2("4", true);
          drawCanvas2("5", true);
        } // then remove the useless div


        hidden.remove();
      };

      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        _loop();
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  }
})(jQuery);

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayLikeToArray.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

module.exports = _arrayLikeToArray;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayWithHoles.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js ***!
  \*********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _iterableToArrayLimit(arr, i) {
  var _i = arr && (typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]);

  if (_i == null) return;
  var _arr = [];
  var _n = true;
  var _d = false;

  var _s, _e;

  try {
    for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

module.exports = _iterableToArrayLimit;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/nonIterableRest.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/nonIterableRest.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/slicedToArray.js":
/*!**************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/slicedToArray.js ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(/*! ./arrayWithHoles.js */ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js");

var iterableToArrayLimit = __webpack_require__(/*! ./iterableToArrayLimit.js */ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js");

var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray.js */ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");

var nonIterableRest = __webpack_require__(/*! ./nonIterableRest.js */ "./node_modules/@babel/runtime/helpers/nonIterableRest.js");

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray.js */ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return arrayLikeToArray(o, minLen);
}

module.exports = _unsupportedIterableToArray;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ })

/******/ });