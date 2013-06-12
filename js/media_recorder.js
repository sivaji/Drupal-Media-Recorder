/**
 * @file
 * Adds an interface between the media recorder jQuery plugin and the drupal media module.
 */

(function($) {
  // Add setup to drupal behaviors.
  Drupal.behaviors.mediaRecorder = {
    attach: function() {

      // Iterate over each media recorder field widget.
      $('.field-widget-media-recorder').each(function(){
        var fieldID = $(this).attr('id');

        // Validation.
        if ($(this).find('.media-recorder-wrapper').hasClass('media-recorder-processed')) {
          return;
        }
        if ($(this).find('.file-widget .file').length) {
          $('#media-recorder-toggle-' + fieldID).remove();
          return;
        }
        
        // Hide original field.
        $(this).find('.form-managed-file input').hide();
        $(this).find('.description').hide();

        // Iterate through all media recorders.
        $(this).find('.media-recorder-wrapper').each(function() {
          var mediaRecorderWrapper = $(this);
          
          // Show media recorder wrapper.
          $(mediaRecorderWrapper).show();

          // Add processed class.
          $(this).addClass('media-recorder-processed');

          // Add voice/upload toggle button.
          if ($('#' + fieldID).find('input[type="file"]').length) {
            $('#' + fieldID).append('<div class="media-recorder-toggle-wrapper"><button id="media-recorder-toggle-' + fieldID + '" data-currentState="voice">Toggle Voice Recorder - File Upload</button></div>');
            $('#media-recorder-toggle-' + fieldID).click(function(event){
              event.preventDefault();
              $('#' + fieldID).find('.form-managed-file input').toggle();
              $('#' + fieldID).find('.description').toggle();
              $('#' + fieldID).find('.media-recorder-audio').toggle();
              $(mediaRecorderWrapper).toggle();
            });
          }
          
          // Instantiate media recorder.
          $(this).mediaRecorder({
            'timeLimit': 300000,
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
      });        
    }
  };
})(jQuery);