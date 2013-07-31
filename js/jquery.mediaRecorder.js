/********************************************************************
 * Project: Drupal Media Recorder jQuery Plugin
 * Description: Adds a media recorder to the drupal media module.
 * Author: Norman Kerr
 * License: GPL.
 *******************************************************************/

(function($, window, document, undefined) {

  // ********************************************************************
  // * Global variables.
  // ********************************************************************

  // jQuery plugin variables.
  var defaults = {
    'timeLimit': 300000
  };

  // Audio analyzer variables.
  var analyserNode = null;
  var analyserContext = null;

  // Normalize features.
  navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
  window.AudioContext = window.AudioContext || window.webkitAudioContext || window.mozAudioContext;
  window.URL = window.URL || window.webkitURL;

  // Feature detection.
  var browser = $.browser;
  var getUserMediaCheck = typeof(navigator.getUserMedia) === 'function';
  var webAudioCheck = typeof(window.AudioContext) === 'function';
  var wamiCheck = typeof(window.Wami) === 'object';
  var canvasCheck = Modernizr.canvas;
  var wavCheck = Modernizr.audio.wav;
  var touchCheck = Modernizr.touch;

  // Only enable for Chrome Canary at this point, due to Chrome showing
  // AudioContext as enabled, even though it's disabled.
  if (browser.webkit === true && browser.version >= 537.36) {
    getUserMediaCheck = true;
  } else {
    getUserMediaCheck = false;
  }

  // Feature Debugging.
  //console.log(browser);
  //console.log('getUserMedia: ' + getUserMediaCheck);
  //console.log('Web Audio: ' + webAudioCheck);
  //console.log('Wami: ' + wamiCheck);
  //console.log('Canvas: ' + canvasCheck);
  //console.log('WAV: ' + wavCheck);
  //console.log('Touch: ' + touchCheck);

  // ********************************************************************
  // * jQuery Plugin Functions.
  // ********************************************************************

  function mediaRecorder(element, options) {
    this.element = element;
    this.options = $.extend({}, defaults, options);
    this.defaults = defaults;

    // Check for existing recorder.
    if (typeof this.element.recorder != 'undefined') {
      return this.element.mediaRecorder;
    } else {
      // Attach recorder to DOM node for reference
      this.element.mediaRecorder = this;
    }

    if (getUserMediaCheck && webAudioCheck) {
      this.init();
    } else {
      this.flashInit();
    }

    return this;
  }

  $.fn.mediaRecorder = function(options) {
    return this.each(function() {
      if (!$.data(this, "plugin_" + mediaRecorder)) {
        $.data(this, "plugin_" + mediaRecorder, new mediaRecorder(this, options));
      }
    });
  };

  // ********************************************************************
  // * Media Recorder Prototype Functions.
  // ********************************************************************

  mediaRecorder.prototype = {

    // ********************************************************************
    // * Initialize Recorder.
    // ********************************************************************
    init: function() {

      // Generate general recorder markup.
      var element = $(this.element);
      var options = this.options;
      element.recorder = $('<div class="media-recorder" style="width: 300px; height:100px;"></div>');
      element.recorder.controls = $('<div class="controls"></div>');
      element.recorder.canvas = $('<canvas class="media-recorder-analyser"></canvas>');
      element.recorder.status = $('<div class="media-recorder-status">00:00 / ' + millisecondsToTime(options.timeLimit) + '</div>');
      element.recorder.statusInterval = 0;
      element.recorder.meterInterval = 0;
      element.recorder.progressInterval = 0;
      element.recorder.HTML5Recorder = null;
      element.recorder.audioContext = null;

      // Add button handlers.
      element.recorder.controls.record = $('<div class="media-recorder-record record-off" title="Click the mic to record and to stop recording."><span>Record</span></div>')
        .click(function(){
          mediaRecorder.prototype.record(element, options);
        });

      // Set HTML5 variables.
      element.recorder.volume = $('<div class="volume" title="Adjust the mic volume."></div>')
        .slider({
          orientation: "vertical",
          range: "min",
          step: 0.05,
          min: 0,
          max: 1,
          value: 0.8,
          slide: function(event, ui) {
            inputPoint.gain.value = ui.value;
          }
        });

      // Add markup.
      element.prepend(element.recorder);
      element.recorder.addClass('HTML5');
      element.recorder.append(element.recorder.controls);
      element.recorder.controls.append(element.recorder.controls.record);
      element.recorder.append(element.recorder.volume);
      element.recorder.append(element.recorder.canvas);
      element.recorder.append(element.recorder.status);

      // Initiate getUserMedia.
      navigator.getUserMedia(
        {audio: true},
        function(stream) {startUserMedia(element, options, stream);},
        function(error) {onError(error);}
      );
    },

    // ********************************************************************
    // * Record Callback.
    // ********************************************************************
    record: function(element, options) {
      element.recorder.HTML5Recorder.record();
      mediaRecorder.prototype.recordStart(element, options);
    },

    // ********************************************************************
    // * Start Recording Callback.
    // ********************************************************************
    recordStart: function(element, options) {
      mediaRecorder.prototype.recordDuring(element, options);
      $(element).find('.media-recorder-analyser').html('');
      $(element).find('.media-recorder-record')
        .removeClass('record-off')
        .addClass('record-on')
        .unbind('click').click(function() {
          mediaRecorder.prototype.stop(element, options);
        });
    },

    // ********************************************************************
    // * During Recording Callback.
    // ********************************************************************
    recordDuring: function(element, options) {
      var currentSeconds = 0;
      element.recorder.statusInterval = window.setInterval(function() {
        // Set time limit and convert to date obj.
        currentSeconds = currentSeconds + 1;
        var currentMilliSeconds = new Date(currentSeconds * 1000);
        // Stop recording if time limit is reached.
        if ((options.timeLimit - currentMilliSeconds) < 0) {
          mediaRecorder.prototype.stop(element, options);
        }
        time = millisecondsToTime(currentMilliSeconds);
        // Refresh time display with current time.
        $(element).find('.media-recorder-status').html(time + ' / 05:00');
      }, 1000);
    },

    // ********************************************************************
    // * Finished Recording Callback.
    // ********************************************************************
    recordFinish: function(element, options) {
      $(element).find('.media-recorder-status').html('00:00 / 05:00');
      $(element).find('.media-recorder-record')
        .removeClass('record-on')
        .addClass('record-off')
        .unbind('click').click(function() {
          mediaRecorder.prototype.record(element, options);
        });
    },

    // ********************************************************************
    // * Stop Recording Callback.
    // ********************************************************************
    stop: function(element, options) {
      clearInterval(element.recorder.statusInterval);
      element.recorder.HTML5Recorder.stop();
      element.recorder.HTML5Recorder.exportWAV(function(blob) {
        var url = URL.createObjectURL(blob);
        var audio = $(element).find('.media-recorder-audio audio');
        $(audio).attr('src', url);
        $(element).find('.media-recorder-status').html('<div class="progressbar"></div>');
        sendBlob(element, options, blob);
      });
      element.recorder.HTML5Recorder.clear();
      mediaRecorder.prototype.recordFinish(element, options);
    },

    // ********************************************************************
    // * Initialize Flash Recorder.
    // ********************************************************************
    flashInit: function() {

      // Generate general recorder markup.
      var element = $(this.element);
      var options = this.options;

      // Check flash version.
      var flashVersion = swfobject.getFlashPlayerVersion();
      if (flashVersion.major < 10) {
        element.prepend($('<div class="messages error"><span class="message-text"><a target="_blank" href="https://get.adobe.com/flashplayer">Flash 10</a> or higher must be installed in order to record.</span></div>'));
        return;
      }

      // Build recorder.
      element.recorder = $('<div class="media-recorder" style="width: 300px; height:100px;"></div>');
      element.recorder.controls = $('<div class="controls"></div>');
      element.recorder.canvas = $('<div class="media-recorder-analyser"></div>');
      element.recorder.status = $('<div class="media-recorder-status">00:00 / ' + millisecondsToTime(options.timeLimit) + '</div>');
      element.recorder.statusInterval = 0;
      element.recorder.meterInterval = 0;
      element.recorder.progressInterval = 0;

      // Add button handlers.
      element.recorder.controls.record = $('<div class="media-recorder-record record-off" title="Click the mic to record and to stop recording.">Record</div>')
        .click(function(){
          mediaRecorder.prototype.flashRecord(element, options);
        });

      // Add Wami related markup.
      element.wami = $('<div id="wami-' + $(element).attr('id') + '" class="wami"></div>');
      element.recorder.micSettings = $('<div class="media-recorder-mic-settings" title="Adjust microphone settings.">Settings</div>')
        .click(function() {
          Wami.showSecurity("microphone");
        });

      // Add markup.
      element.prepend(element.recorder);
      element.prepend(element.wami);
      element.recorder.addClass('WAMI');
      element.recorder.append(element.recorder.controls);
      element.recorder.controls.append(element.recorder.controls.record);
      element.recorder.append(element.recorder.canvas);
      element.recorder.append(element.recorder.micSettings);
      element.recorder.append(element.recorder.status);

      // Initiate Wami.
      Wami.setup({
        id: 'wami-' + $(element).attr('id'),
        swfUrl: options.swfURL
      });

      // Test that html5 audio tags can play wav files (not possible in IE9-).
      if (!wavCheck && browser.msie) {
        var audio = element.find('.media-recorder-audio');
        var url = '';
        if (audio.children('audio').length) {
          url = audio.children('audio').attr('src');
        } else if (audio.children('embed').length) {
          url = audio.children('embed').attr('src');
        }
        audio.css('position', 'static');
        audio.detach().insertBefore(element);
      }
    },

    // ********************************************************************
    // * Flash Record Callback.
    // ********************************************************************
    flashRecord: function(element, options) {
      Wami.startRecording(
        options.recordingPath + '/' + options.drupalFileName,
        Wami.nameCallback(function() {
          mediaRecorder.prototype.flashRecordStart(element, options);
        }),
        Wami.nameCallback(function() {
          mediaRecorder.prototype.flashRecordFinish(element, options);
        }),
        Wami.nameCallback(onError)
      );
    },

    // ********************************************************************
    // * Flash Start Recording Callback.
    // ********************************************************************
    flashRecordStart: function(element, options) {
      $(element).find('.media-recorder-record').removeClass('record-off').addClass('record-on')
      .unbind('click').click(function() {
        mediaRecorder.prototype.flashStop(element, options);
      });
      $(element).find('.media-recorder-analyser').html('');
      mediaRecorder.prototype.flashRecordDuring(element, options);
    },

    // ********************************************************************
    // * Flash During Recording Callback.
    // ********************************************************************
    flashRecordDuring: function(element, options) {
      // Update status interval.
      var currentSeconds = 0;
      element.recorder.statusInterval = window.setInterval(function() {
        // Set time limit and convert to date obj.
        currentSeconds = currentSeconds + 1;
        var currentMilliSeconds = new Date(currentSeconds * 1000);
        // Stop recording if time limit is reached.
        if ((options.timeLimit - currentMilliSeconds) < 0) {
          mediaRecorder.prototype.flashStop(element, options);
        }
        time = millisecondsToTime(currentMilliSeconds);
        // Refresh time display with current time.
        $(element).find('.media-recorder-status').html(time + ' / 05:00');
      }, 1000);
      // Update meter interval.
      element.recorder.meterInterval = window.setInterval(function() {
        // Refresh meter bar.
        var level = Wami.getRecordingLevel();
        var marginTop = Math.round((100 - level) / 2.5);
        if (level === 0) { level = 2; }
        $(element).find('.media-recorder-analyser').append('<div class="meter-bar"><div class="inner record" style="height:' + level + '%; margin-top: ' + marginTop + 'px"></div></div>');
        var barsNumber = $(element).find('.media-recorder-analyser').children().size();
        if (barsNumber >= 100) {
          $(element).find('.media-recorder-analyser').children().first().remove();
        }
      }, 50);
    },

    // ********************************************************************
    // * Flash Finished Recording Callback.
    // ********************************************************************
    flashRecordFinish: function(element, options) {
      // Clear all progress intervals.
      clearInterval(element.recorder.progressInterval);
      // Update audio player.
      updateAudio(element, options);
      // Set audio and file input values.
      var elementName = fieldFormatter(element, options, 'filepath');
      $('input[name="' + elementName + '"]').val(options.drupalFilePath + '/' + options.drupalFileName);
      $(element).find('.media-recorder-status').html('00:00 / 05:00');
      $(element).find('.media-recorder-record').removeClass('record-on').addClass('record-off')
      .unbind('click').click(function() {
        mediaRecorder.prototype.flashRecord(element, options);
      });
    },

    // ********************************************************************
    // * Flash Stop Recording Callback.
    // ********************************************************************
    flashStop: function(element, options) {
      clearInterval(element.recorder.statusInterval);
      clearInterval(element.recorder.meterInterval);
      Wami.stopRecording();
      var progressCount = 0;
      var progressIndicator = '';
      element.recorder.progressInterval = setInterval(function() {
        progressCount = progressCount + 1;
        progressIndicator = progressIndicator + '.';
        $(element).find('.media-recorder-status').html('Uploading' + progressIndicator);
        if (progressCount === 3) { progressCount = 0; progressIndicator = ''; }
      }, 500);
    }
  };

  // ********************************************************************
  // * Private Functions.
  // ********************************************************************

  // ********************************************************************
  // * Start getUserMedia Audio Stream.
  // ********************************************************************
  function startUserMedia(element, options, stream) {
    if (webAudioCheck) {
      element.recorder.audioContext = new AudioContext();
      inputPoint = element.recorder.audioContext.createGainNode();
      realAudioInput = element.recorder.audioContext.createMediaStreamSource(stream);
      audioInput = realAudioInput;
      audioInput.connect(inputPoint);
      inputPoint.gain.value = 0.8;
      analyserNode = element.recorder.audioContext.createAnalyser();
      analyserNode.fftSize = 2048;
      inputPoint.connect(analyserNode);
      element.recorder.HTML5Recorder = new Recorder(inputPoint, {workerPath:Drupal.settings.basePath + 'sites/all/libraries/Recorderjs/recorderWorker.js'});
      zeroGain = element.recorder.audioContext.createGainNode();
      zeroGain.gain.value = 0.0;
      inputPoint.connect(zeroGain);
      zeroGain.connect(element.recorder.audioContext.destination);
      updateAudioCanvas(element, options);
    }
  }

  // ********************************************************************
  // * Send Blob for getUserMedia recordings.
  // ********************************************************************
  function sendBlob(element, options, blob) {
    var formData = new FormData();
    var fileObj = {};
    formData.append("mediaRecorder", blob);
    var req = new XMLHttpRequest();
    req.upload.onprogress = updateProgress;
    req.addEventListener("progress", updateProgress, false);
    req.addEventListener("load", transferComplete, false);
    req.addEventListener("error", transferFailed, false);
    req.addEventListener("abort", transferCanceled, false);
    req.open("POST", options.recordingPath + '/' + options.drupalFileName, true);
    req.send(formData);

    function updateProgress(evt) {
      if (evt.lengthComputable) {
        var percentComplete = (evt.loaded / evt.total) * 100;
        $(element).find('.progressbar').progressbar({
          value: percentComplete
        });
      } else {
        $(element).find('.progressbar').progressbar({
          value: 100
        });
      }
    }

    function transferComplete(evt) {
      var file = JSON.parse(req.response);
      var fidInput = fieldFormatter(element, options, 'fid');
      var filepathInput = fieldFormatter(element, options, 'filepath');
      $('input[name="' + fidInput + '"]').val(file.fid);
      $('input[name="' + filepathInput + '"]').val(options.drupalFilePath + '/' + options.drupalFileName);
      $(element).find('.media-recorder-status').html('00:00 / 05:00');
    }

    function transferFailed(evt) {
      onError("An error occurred while transferring the file.");
    }

    function transferCanceled(evt) {
      onError("The transfer has been canceled by the user.");
    }
  }

  // ********************************************************************
  // * Update Audio Canvas.
  // ********************************************************************
  function updateAudioCanvas(element, options) {
    if (!analyserContext) {
      canvasWidth = element.recorder.canvas[0].width;
      canvasHeight = element.recorder.canvas[0].height;
      analyserContext = element.recorder.canvas[0].getContext('2d');
    }
    var SPACING = 1;
    var BAR_WIDTH = 1;
    var numBars = Math.round(canvasWidth / SPACING);
    var freqByteData = new Uint8Array(analyserNode.frequencyBinCount);
    analyserNode.getByteFrequencyData(freqByteData);
    analyserContext.clearRect(0, 0, canvasWidth, canvasHeight);
    analyserContext.fillStyle = '#F6D565';
    analyserContext.lineCap = 'round';
    var multiplier = analyserNode.frequencyBinCount / numBars;
    // Draw rectangle for each frequency bin.
    for (var i = 0; i < numBars; ++i) {
      var magnitude = 0;
      var offset = Math.floor(i * multiplier);
      for (var j = 0; j < multiplier; j++) {
        magnitude += freqByteData[offset + j];
      }
      magnitude = magnitude / multiplier;
      var magnitude2 = freqByteData[i * multiplier];
      analyserContext.fillStyle = "hsl( " + Math.round((i * 360) / numBars) + ", 100%, 50%)";
      analyserContext.fillRect(i * SPACING, canvasHeight, BAR_WIDTH, -magnitude);
    }
    rafID = window.webkitRequestAnimationFrame(updateAudioCanvas);
  }

  // ********************************************************************
  // * Update Audio Element.
  // ********************************************************************
  function updateAudio(element, options) {
    // Adds time to audio url to disable caching.
    var time = new Date().getTime();
    var url = options.drupalURL + '/' + options.drupalFileName;
    // Using hack to support IE9 and below browsers.
    if (wavCheck) {
      $(element).find('.media-recorder-audio audio').attr('src', url + '?' + time);
    } else {
      var embed = '<embed width="300" height="30" autostart="false" src="' + url + '?' + time + '" />';
      $(element).parent().find('.media-recorder-audio').html(embed);
    }
  }

  // ********************************************************************
  // * Format Input Field Helper.
  // ********************************************************************
  function fieldFormatter(element, options, name) {
    var language = options.drupalLanguage ? '[' + options.drupalLanguage + ']' : '';
    var delta = (options.drupalDelta !== null) ? '[' + options.drupalDelta + ']' : '';
    return options.drupalFieldName + language + delta + '[' + name + ']';
  }

  function millisecondsToTime(milliSeconds) {
    // Format Current Time
    var milliSecondsDate = new Date(milliSeconds);
    var mm = milliSecondsDate.getMinutes();
    var ss = milliSecondsDate.getSeconds();
    if (mm < 10) {
      mm = "0" + mm;
    }
    if (ss < 10) {
      ss = "0" + ss;
    }
    return mm + ':' + ss;
  }

  // ********************************************************************
  // * Error Handler.
  // ********************************************************************
  function onError(msg) {
    alert(msg);
  }

})(jQuery, window, document);
