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
/******/ 	return __webpack_require__(__webpack_require__.s = "./admin/src/admin-script.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./admin/src/admin-script.js":
/*!***********************************!*\
  !*** ./admin/src/admin-script.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {

// save on ctrl-s keypress
if (document.body.classList.contains('cf7-antispam-admin')) {
  let jquery = function ($) {
    $(function () {
      var welcomePanel = $('#welcome-panel'); // welcome panel script needed to hide the div

      $('a.welcome-panel-close', welcomePanel).click(function (event) {
        event.preventDefault();
        welcomePanel.addClass('hidden');
      });
    });
  }; // saves on ctrl-s


  document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 's') {
      e.preventDefault();
      document.getElementById('submit').click();
    }
  }); // show the advanced section

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
  };

  showAdvanced();
  document.getElementById('enable_advanced_settings').addEventListener('click', showAdvanced);
}

/***/ })

/******/ });