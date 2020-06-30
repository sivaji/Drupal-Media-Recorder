/**
 * @file
 * Provides JavaScript additions to the media recorder element.
 *
 * This file loads the correct media recorder javascript based on detected
 * features, such as the MediaRecorder API and Web Audio API. It has a flash
 * fallback, and uses the file widget for devices that supports nothing.
 */

(function ($, Drupal, drupalSettings) {
// (function ($) {
  'use strict';

  // Normalize features.
  navigator.getUserMedia = (
    navigator.getUserMedia ||
    navigator.mediaDevices.getUserMedia ||
    navigator.webkitGetUserMedia ||
    navigator.mozGetUserMedia ||
    navigator.msGetUserMedia
  );

  window.AudioContext = window.AudioContext || window.webkitAudioContext || window.mozAudioContext;
  window.URL = window.URL || window.webkitURL;

  // Feature detection.
  var getUserMediaCheck = typeof (navigator.getUserMedia) === 'function';
  var mediaRecorderCheck = typeof (window.MediaRecorder) === 'function';
  var webAudioCheck = typeof (window.AudioContext) === 'function';
  var swfobjectCheck = typeof (window.swfobject) === 'object';
  var flashVersionCheck = swfobjectCheck ? (swfobject.getFlashPlayerVersion().major >= 10) : false;

  var settings = drupalSettings;

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
    // attach: function (context, settings) {
    attach: function (context) {

      if (settings.mediaRecorder && settings.mediaRecorder.elements) {
        $.each(settings.mediaRecorder.elements, function (key, info) {
          $('#' + info.id).once('media-recorder').each(function () {
            console.log(info.id);
            var $mediaRecorder = $('#' + info.id);
            // var $mediaRecorderFallback = $('#' + info.id + '-fallback-ajax-wrapper');
            var $mediaRecorderFallback = $('#' + info.id + '-fallback').parent();

            switch (recorderType) {
              case 'MediaRecorder':
                $mediaRecorder.show();
                $mediaRecorderFallback.remove();
                new Drupal.MediaRecorder(info.id, info.conf);
                break;
              case 'MediaRecorderHTML5':
                $mediaRecorder.show();
                $mediaRecorderFallback.remove();
                // Use the kaltura video recorder if kaltura is enabled, rather
                // than the HTML5 audio only Recorder.js library.
                if (info.conf.kaltura) {
                  new Drupal.MediaRecorderFlash(info.id, info.conf);
                }
                else {
                  new Drupal.MediaRecorderHTML5(info.id, info.conf);
                }
                break;
              case 'MediaRecorderFlash':
                $mediaRecorder.show();
                $mediaRecorderFallback.remove();
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
// })(jQuery);
})(jQuery, Drupal, drupalSettings);
