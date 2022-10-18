/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./admin/src/css/admin.scss":
/*!**********************************!*\
  !*** ./admin/src/css/admin.scss ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!***********************************!*\
  !*** ./admin/src/admin-script.js ***!
  \***********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _css_admin_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./css/admin.scss */ "./admin/src/css/admin.scss");


window.onload = function () {
  /* This is the code that adds the confirmation alert to the delete buttons on the settings page. */
  if (document.body.classList.contains('cf7-antispam-admin') || document.body.classList.contains('flamingo_page_flamingo_inbound')) {
    // eslint-disable-next-line
    const alertMessage = cf7a_admin_settings.alertMessage; // the confirmation alert script

    const alerts = document.querySelectorAll('.cf7a_alert');

    function confirmationAlert(e, message) {
      // eslint-disable-next-line no-alert,no-undef
      if (confirm(message || alertMessage)) window.location.href = e.dataset.href;
    }

    alerts.forEach(alert => {
      alert.addEventListener('click', () => {
        confirmationAlert(alert, alert.dataset.message || false);
      });
    });
  }
  /* This is the code that hides the welcome panel,
  adds the ctrl-s keypress to save the settings,
  and shows the advanced settings. */


  if (document.body.classList.contains('cf7-antispam-admin')) {
    // hide the welcome panel
    const welcomePanel = document.getElementById('welcome-panel');
    const welcomePanelCloseBtn = welcomePanel.querySelector('a.welcome-panel-close');
    welcomePanelCloseBtn.addEventListener('click', event => {
      event.preventDefault();
      welcomePanel.classList.add('hidden');
    }); // save on ctrl-s keypress

    document.addEventListener('keydown', e => {
      if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('submit').click();
      }
    }); // show the advanced section

    const showAdvanced = () => {
      const advancedCheckbox = document.getElementById('enable_advanced_settings');
      const AdvSettingsTitle = document.querySelectorAll('#cf7a_settings h2');
      const AdvSettingsForm = document.querySelectorAll('#cf7a_settings table');
      const AdvSettingsCard = document.getElementById('advanced-setting-card');

      if (advancedCheckbox.checked !== true) {
        if (AdvSettingsCard) {
          AdvSettingsCard.classList.add('hidden');
          AdvSettingsTitle[AdvSettingsTitle.length - 1].classList.add('hidden');
          AdvSettingsForm[AdvSettingsForm.length - 1].classList.add('hidden');
        }
      } else if (AdvSettingsCard) {
        AdvSettingsCard.classList.remove('hidden');
        AdvSettingsTitle[AdvSettingsTitle.length - 1].classList.remove('hidden');
        AdvSettingsForm[AdvSettingsForm.length - 1].classList.remove('hidden');
      }
    };

    document.getElementById('enable_advanced_settings').addEventListener('click', showAdvanced);
  }
};
}();
/******/ })()
;
//# sourceMappingURL=admin-script.js.map