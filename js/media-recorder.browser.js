/**
 * @file
 * Media browser JS integration for the Media Recorder module.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.mediaRecorderBrowser = {
    attach: function () {

      // Bind click handler to media recorder submit input.
      $('#media-recorder-add #edit-submit').bind('click', function (event) {

        // Prevent regular form submit.
        event.preventDefault();

        // Get the fid value.
        var fid = $('#media-recorder-add .media-recorder-fid').val();

        // Build file object.
        var file = {};
        file.fid = fid;
        file.preview = $('#media-recorder-add .media-recorder-preview').html();

        // Add to selected media.
        var files = [];
        files.push(file);
        Drupal.media.browser.selectMedia(files);

        // Submit media browser form.
        Drupal.media.browser.submit();
      });
    }
  };
})(jQuery);
