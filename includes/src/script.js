(function ($) {

  'use strict';

  const cf7a_prefix = cf7a_settings.prefix;

  const browserFingerprint = () => {
		const ua = navigator.userAgent;

		return {
			"timezone": window.Intl.DateTimeFormat().resolvedOptions().timeZone ?? null,
			"platform": navigator.platform ?? null,
			"hardware_concurrency": navigator.hardwareConcurrency  ?? null,
			"screens": [window.screen.width, window.screen.height] ?? null,
			"memory": navigator.deviceMemory ?? null,
			"user_agent": ua ?? null,
			"app_version": navigator.appVersion ?? null,
			"webdriver": window.navigator.webdriver ?? null,
			"session_storage": sessionStorage ?? null,
			"isSafari": ua.toLowerCase().indexOf('safari') !== -1 && ua.toLowerCase().indexOf('chrome') === -1 ? true : null,
			"isIOS": typeof navigator.standalone === 'boolean' ? true : null
		};
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

      // I) Standard bot checks
      let bot_fingerprint_key = $(wpcf7Form)[0].querySelector('form > div input[name=' + cf7a_prefix + 'bot_fingerprint]')

      // II) Bot fingerprint extra checks
      let bot_fingerprint_extra = $(wpcf7Form)[0].querySelector('form > div input[name=' + cf7a_prefix + 'bot_fingerprint_extras]');

      // III) how append bot fingerprint into hidden fields
      let append_on_submit = $(wpcf7Form)[0].querySelector('form > div input[name=' + cf7a_prefix + 'append_on_submit]');

      let tests = {};

      if (bot_fingerprint_key) {
        let fingerprint_key = bot_fingerprint_key.getAttribute("value");
        // hijack the value of the bot_fingerprint
        bot_fingerprint_key.setAttribute("value", fingerprint_key.slice(0, 5));

        // bot_fingerprint checks enabled
        tests = browserFingerprint();

        // append the fields
        if (!append_on_submit) {

          for (const [key, value] of Object.entries(tests).sort(() => Math.random() - 0.5)) {
            $(wpcf7Form).find('form > div').append(createCF7Afield(key, value));
          }

        } else {

          const formElem = $(wpcf7Form)[0].querySelector('form');
          let formData = new FormData(formElem.formData);

          formElem.addEventListener('formdata', (e) => {
            for (const [key, value] of Object.entries(tests).sort(() => Math.random() - 0.5)) {
              e.formData.append(cf7a_prefix + key, value);
            }
            formData = e.formData;
          });

        }
      }


      // II) Bot fingerprint extra checks
      if (bot_fingerprint_extra) {

        // check for mouse clicks
        const activity = function (e) {
          $(wpcf7Form).find('form > div input[name=' + cf7a_prefix + 'activity]').remove();
          $(wpcf7Form).find('form > div').append(createCF7Afield("activity", mouseActivity_value++));

          if (mouseActivity_value > 3) {
            document.body.removeEventListener('mouseup', activity);
            document.body.removeEventListener('touchend', activity);
            $(wpcf7Form).find('form > div').append(createCF7Afield("mouseclick_activity", "passed"));
          }
        };
        document.body.addEventListener( 'mouseup', activity);
        document.body.addEventListener( 'touchend', activity);



        // detect the mouse/touch direction change OR touchscreen iterations
        const mouseMove = function (e) {
          if (e.pageY > oldy) {
            mouseMove_value += 1;
          }
          oldy = e.pageY;

          if (mouseMove_value > 3) {
            document.removeEventListener('mousemove', mouseMove);
            $(wpcf7Form).find('form > div').append(createCF7Afield("mousemove_activity", "passed"));
          }
        };
        document.addEventListener('mousemove', mouseMove);



        let wpcf7box = document.createElement('div');
        wpcf7box.id = 'hidden';
        let form_hidden_field = $(wpcf7Form)[0].querySelector('form > div');
        form_hidden_field.append(wpcf7box);

        // credits //bot.sannysoft.com
        // tools
        String.prototype.hashCode = function () {
          var hash = 0, i, chr;
          if (this.length === 0) return hash;
          for (i = 0; i < this.length; i++) {
            chr = this.charCodeAt(i);
            hash = ((hash << 5) - hash) + chr;
            hash |= 0; // Convert to 32bit integer
          }
          return hash;
        };

        // WebGL Tests
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
              $(wpcf7Form).find('form > div').append(createCF7Afield("webgl", "failed"));
            } else {
              $(wpcf7Form).find('form > div').append(createCF7Afield("webgl", "passed"));
            }
          } catch (e) {
            webGLVendorElement.innerHTML = "Error: " + e;
          }

          try {
            // WebGL Renderer Test
            const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
            webGLRendererElement.innerHTML = renderer;
            if (renderer === 'Mesa OffScreen' || renderer.indexOf("Swift") !== -1) {
              $(wpcf7Form).find('form > div').append(createCF7Afield("webgl_render", "failed"));
            } else
              $(wpcf7Form).find('form > div').append(createCF7Afield("webgl_render", "passed"));
          } catch (e) {
            webGLRendererElement.innerHTML = "Error: " + e;
          }
        } else {
          $(wpcf7Form).find('form > div').append(createCF7Afield("webgl", "failed"));
          $(wpcf7Form).find('form > div').append(createCF7Afield("webgl_render", "failed"));
        }

        // TODO: change the canvas name
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
                if ("boolean" == typeof (datUrl) || void 0 === datUrl) {
                  throw e;
                }
              } catch (a) {
                /** @type {string} */
                datUrl = "";
              }
              if (0 === datUrl.indexOf("data:image/png")) {

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
        drawCanvas2("5", true);

        // then remove the useless div
        wpcf7box.remove();
      }
    }
  }

})(jQuery);

