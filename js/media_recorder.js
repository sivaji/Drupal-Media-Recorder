/**
 * @file
 * Adds an interface between the media recorder jQuery plugin and the drupal media module.
 */

(function($) {
  // Add setup to drupal behaviors.
  Drupal.behaviors.mediaRecorder = {
    attach: function(context, settings) {

      // Iterate over each media recorder field widget.
      $('.media-recorder-wrapper').each(function(){

        // Validation.
        if ($(this).hasClass('media-recorder-processed')) {
          return;
        }

        // Hide original field.
        $(this).parent().children('.form-managed-file input').hide();
        $(this).parent().children('.description').hide();

        var mediaRecorderWrapper = $(this);

        // Add processed class.
        $(this).addClass('media-recorder-processed');

        // Instantiate media recorder.
        $(this).mediaRecorder({
          'timeLimit': Drupal.settings.mediaRecorder.timeLimit,
          'recordingPath': Drupal.settings.mediaRecorder.recordingPath,
          'swfURL': Drupal.settings.basePath + 'sites/all/libraries/wami/Wami.swf',
          'drupalURL': Drupal.settings.mediaRecorder.url,
          'drupalFilePath': Drupal.settings.mediaRecorder.filePath,
          'drupalFileName': Drupal.settings.mediaRecorder.fileName,
          'drupalFieldName': Drupal.settings.mediaRecorder.fieldName,
          'drupalLanguage': Drupal.settings.mediaRecorder.language,
          'drupalDelta': Drupal.settings.mediaRecorder.delta,
        });
      });

    }
  };
})(jQuery);
