/**
 * @file
 * Loads correct javascript files based on browser features. We do this because of namespace conflicts with external
 * libraries.
 */

(function ($) {
  'use strict';

  // Normalize features.
  navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
  window.AudioContext = window.AudioContext || window.webkitAudioContext || window.mozAudioContext;
  window.URL = window.URL || window.webkitURL;

  // Feature detection.
  var getUserMediaCheck = typeof (navigator.getUserMedia) === 'function';
  var mediaRecorderCheck = typeof (window.MediaRecorder) === 'function';
  var webAudioCheck = typeof (window.AudioContext) === 'function';
  var swfobjectCheck = typeof (window.swfobject) === 'object';
  var flashVersionCheck = swfobjectCheck ? (swfobject.getFlashPlayerVersion().major >= 10) : false;

  Drupal.behaviors.mediaRecorder = {
    attach: function () {

      // Check to see that browser can use the recorder.
      if ((getUserMediaCheck && webAudioCheck) || (flashVersionCheck && swfobjectCheck)) {

        $('.field-widget-media-recorder').once().each(function (key, element) {

          // Add Drupal MediaRecorder module and initialize.
          if (getUserMediaCheck && webAudioCheck && mediaRecorderCheck) {
            element.recorder = Drupal.MediaRecorder;
          }
          else if (getUserMediaCheck && webAudioCheck && !mediaRecorderCheck) {
            element.recorder = Drupal.MediaRecorderHTML5;
          }
          else if (flashVersionCheck) {

            // Check for IE9+.
            if (!document.addEventListener) {
              alert("The media recorder is not available on versions of Internet Explorer earlier than IE9.");
              $('.field-widget-media-recorder').find('.media-recorder-wrapper').hide();
              return;
            }
            element.recorder = Drupal.MediaRecorderFlash;
          }

          // Hide the normal file input.
          $(element).find('span.file, span.file-size, .media-recorder-upload, .media-recorder-upload-button, .media-recorder-remove-button').hide();

          // Initialize the recorder.
          element.recorder.init(element);
        });
      }

      // Otherwise just use the basic file field.
      else {
        $('.field-widget-media-recorder').find('.media-recorder-wrapper').hide();
      }
    }
  };
})(jQuery);
