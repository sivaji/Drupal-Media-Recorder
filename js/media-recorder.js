/**
 * @file
 * Provides JavaScript additions to the media recorder element.
 *
 * This file loads the correct media recorder javascript based on detected
 * features, such as the MediaRecorder API and Web Audio API. It has a flash
 * fallback, and uses the file widget for devices that supports nothing.
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

  // Set recorder type.
  var recorderType = false;
  if (getUserMediaCheck && webAudioCheck && mediaRecorderCheck) {
    recorderType = 'MediaRecorder';
  }
  else if (getUserMediaCheck && webAudioCheck && !mediaRecorderCheck) {
    recorderType = 'MediaRecorderHTML5';
  }
  else if (swfobjectCheck && flashVersionCheck) {
    recorderType = 'MediaRecorderFlash';
  }

  // Attach behaviors.
  Drupal.behaviors.mediaRecorder = {
    attach: function (context, settings) {
      if (settings.mediaRecorder && settings.mediaRecorder.elements) {
        $.each(settings.mediaRecorder.elements, function (key, info) {
          $('#' + info.id, context).once('media-recorder', function () {
            var $mediaRecorder = $('#' + info.id);
            var $mediaRecorderFallback = $('#' + info.id + '-fallback-ajax-wrapper');
            switch (recorderType) {
              case 'MediaRecorder':
                $mediaRecorder.show();
                $mediaRecorderFallback.hide();
                new Drupal.MediaRecorder(info.id, info.conf);
                break;
              case 'MediaRecorderHTML5':
                $mediaRecorder.show();
                $mediaRecorderFallback.hide();
                new Drupal.MediaRecorderHTML5(info.id, info.conf);
                break;
              case 'MediaRecorderFlash':
                $mediaRecorder.show();
                $mediaRecorderFallback.hide();
                new Drupal.MediaRecorderFlash(info.id, info.conf);
                break;
              default:
                $mediaRecorder.hide();
            }
          });
        });
      }
    }
  };
})(jQuery);
