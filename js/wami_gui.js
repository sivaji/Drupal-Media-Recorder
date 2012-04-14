(function ($) {

  // Adds the media recorder DOM object.
  Drupal.mediaRecorder = Drupal.mediaRecorder || {};
  
  // Initialize the Wami recorder.
  Drupal.mediaRecorder.setup = function() {
	Wami.setup({
	  id: 'wami-swf',
	  swfUrl: Drupal.settings.basePath + Drupal.settings.mediaRecorder.modulePath + '/js/Wami.swf',
	});
  }
  
  // Record Callbacks.
  Drupal.mediaRecorder.record = function() {
	Wami.startRecording(
	  Drupal.settings.mediaRecorder.recordPath + Drupal.settings.mediaRecorder.fileName,
	  Wami.nameCallback(Drupal.mediaRecorder.onRecordStart),
	  Wami.nameCallback(Drupal.mediaRecorder.onRecordFinish),
	  Wami.nameCallback(Drupal.mediaRecorder.onError)
	);
  };
  
  Drupal.mediaRecorder.onRecordStart = function() {
	$('#meter').html('');
	Drupal.mediaRecorder.recordOn();
	recordInterval = setInterval(function() {
	  var level = Math.round( ( Wami.getRecordingLevel() / 4 ) + 1 );
	  var marginTop = Math.round(( 26 - level ) / 2);
	  $('#meter').append('<div class="meter-bar"><div class="inner record" style="height:' + level + 'px; margin-top: ' + marginTop + 'px"></div></div>');
	  var barsNumber = $('#meter').children().size();
	  if ( barsNumber >= 100 ) {
	    $('#meter').children().first().remove();
	  }
	}, 50);
  }
  
  Drupal.mediaRecorder.onRecordFinish = function() {
	Drupal.mediaRecorder.recordOff();
	clearInterval(recordInterval);
  }
  
  // Play Callbacks.
  Drupal.mediaRecorder.play = function() {  
	Wami.startPlaying(
	  Drupal.settings.mediaRecorder.playPath + Drupal.settings.mediaRecorder.fileName,
	  Wami.nameCallback(Drupal.mediaRecorder.onPlayStart),
	  Wami.nameCallback(Drupal.mediaRecorder.onPlayFinish),
	  Wami.nameCallback(Drupal.mediaRecorder.onError)
	);
  };
  
  Drupal.mediaRecorder.onPlayStart = function() {
	$('#meter').html('');
	Drupal.mediaRecorder.playOn();
	playInterval = setInterval(function() {
	  var level = Math.round( ( Wami.getPlayingLevel() / 4 ) + 1 );
	  var marginTop = Math.round(( 26 - level ) / 2);
	  $('#meter').append('<div class="meter-bar"><div class="inner play" style="height:' + level + 'px; margin-top: ' + marginTop + 'px"></div></div>');
	  var barsNumber = $('#meter').children().size();
	  if ( barsNumber >= 100 ) {
	    $('#meter').children().first().remove();
	  }
	}, 50);
  }
  
  Drupal.mediaRecorder.onPlayFinish = function() {
	Drupal.mediaRecorder.playOff();
	clearInterval(playInterval);
  }

  // Stop Callbacks.
  Drupal.mediaRecorder.stop = function() {
	Wami.stopRecording();
	Wami.stopPlaying();
	if ( typeof recordInterval != 'undefined' ) { clearInterval(recordInterval); }
	if ( typeof playInterval != 'undefined' ) { clearInterval(playInterval); }
  };
        
  // Theming manipulations when recording starts.
  Drupal.mediaRecorder.recordOn = function() {
	$('#media-recorder-record').unbind('click');
	$('#media-recorder-record').removeClass('record-off');
	$('#media-recorder-record').addClass('record-on');
	$('#media-recorder-play').unbind('click');
	$('#media-recorder-play').removeClass('enabled');
	$('#media-recorder-play').addClass('disabled');
  };
        
  // Theming manipulations when recording finishes.
  Drupal.mediaRecorder.recordOff = function() {
	$('#media-recorder-record').click(function() { Drupal.mediaRecorder.record(); });
	$('#media-recorder-record').removeClass('record-on');
	$('#media-recorder-record').addClass('record-off');
	$('#media-recorder-play').click(function() { Drupal.mediaRecorder.play(); });
	$('#media-recorder-play').removeClass('disabled');
	$('#media-recorder-play').addClass('enabled');
  };
  
  // Theming manipulations when playing starts.
  Drupal.mediaRecorder.playOn = function() {
	$('#media-recorder-play').unbind('click');
	$('#media-recorder-play').removeClass('play-off');
	$('#media-recorder-play').addClass('play-on');
	$('#media-recorder-record').unbind('click');
	$('#media-recorder-record').removeClass('enabled');
	$('#media-recorder-record').addClass('disabled');
  };
        
  // Theming manipulations when playing finishes.
  Drupal.mediaRecorder.playOff = function() {
	$('#media-recorder-play').click(function() { Drupal.mediaRecorder.play(); });
	$('#media-recorder-play').removeClass('play-on');
	$('#media-recorder-play').addClass('play-off');
	$('#media-recorder-record').click(function() { Drupal.mediaRecorder.record(); });
	$('#media-recorder-record').removeClass('disabled');
	$('#media-recorder-record').addClass('enabled');
  };
  
  // Error display callback.      
  Drupal.mediaRecorder.onError = function(msg) {
	alert(msg);
  }
  
  // Attaches click events to the recorder controls.
  Drupal.behaviors.mediaRecorder = {
	attach: function() {
	
	  $('#media-recorder-record').click(function() {
		Drupal.mediaRecorder.record();
	  });
	  
	  $('#media-recorder-stop').click(function() {
		Drupal.mediaRecorder.stop();
	  });
	  
	  $('#media-recorder-play').click(function() {
		Drupal.mediaRecorder.play();
	  });
	  
	}
  }

})(jQuery);


jQuery(document).ready(function($) {
  
  // Set up the Wami flash object.
  Drupal.mediaRecorder.setup();
  
  // After Wami is loaded, start listening for any recordings so we can add a small buffer.
  // People usually like to start talking immediately after hitting record.
  listeningInterval = setInterval(function() {
	if ( Wami.startListening ) {
	  Wami.startListening();
	  clearInterval(listeningInterval);
	}
  }, 50);
});