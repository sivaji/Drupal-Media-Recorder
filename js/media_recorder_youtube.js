/**
 * @file
 * Adds an interface between the Youtube upload widget and the Drupal media recorder module.
 */

(function($) {
  // Add setup to drupal behaviors.
  Drupal.behaviors.mediaRecorderYoutube = {
    attach: function(context, settings) {

      var widget;
      var player;
      var youtubeInput = fieldFormatter('youtube');
      var currentId = $('input[name="' + youtubeInput + '"]').val();

      // Format input field helper.
      function fieldFormatter(name) {
        var fieldName = Drupal.settings.mediaRecorder.fieldName;
        var language = '[' + Drupal.settings.mediaRecorder.language + ']';
        var delta = '[' + Drupal.settings.mediaRecorder.delta + ']';
        return fieldName + language + delta + '[' + name + ']';
      }

      // Attaches upload widget to upload div.
      function onYouTubeIframeAPIReady() {
        widget = new YT.UploadWidget('youtube-upload', {
          width: 500,
          events: {
            'onUploadSuccess': onUploadSuccess,
            'onProcessingComplete': onProcessingComplete
          }
        });
      }

      // Callback fired when video is successfully uploaded.
      function onUploadSuccess(event) {
        $('input[name="' + youtubeInput + '"]').val(event.data.videoId);
        $('#youtube-upload-wrapper').hide();
        $('#youtube-player-wrapper').prepend('<div class="messages warning">Video is currently processing and will not display properly until processing is finished. However, you may save this content at any time without loss of video.</div>');
        player = new YT.Player('youtube-player', {
          height: 390,
          width: 640,
          videoId: event.data.videoId,
          events: {}
        });
      }

      // Callback fired when video is finished processing.
      function onProcessingComplete(event) {
        $('#youtube-player-wrapper .messages').removeClass('warning').addClass('status').html('Video is finished processing.');
      }

      // Check if there is already a youtube video id.
      if (currentId) {
        // Display video as a video.
        player = new YT.Player('youtube-player', {
          height: 390,
          width: 640,
          videoId: currentId,
          events: {}
        });
      } else {
        // Create widget.
        onYouTubeIframeAPIReady();
      }
    }
  };
})(jQuery);
