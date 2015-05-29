/**
 * @file
 * Adds an interface between the media recorder jQuery plugin and the drupal media module.
 */

(function ($) {
  'use strict';

  Drupal.MediaRecorderHTML5 = (function () {
    var settings = Drupal.settings.mediaRecorder.settings;
    var origin = window.location.origin || window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
    var audioContext;
    var canvasContext;
    var visualizerProcessor;
    var freqData;
    var volume;
    var barWidth;
    var level;
    var meterProcessor;
    var constraints;
    var localStream;
    var recorder;
    var recordURL;
    var playbackURL;
    var mimetype;
    var analyser;
    var microphone;
    var blobs;
    var statusInterval;
    var $element;
    var $statusWrapper;
    var $previewWrapper;
    var $video;
    var $audio;
    var $meter;
    var $startButton;
    var $recordButton;
    var $playButton;
    var $stopButton;
    var $settingsButton;
    var $videoButton;
    var $audioButton;

    /**
     * Set status message.
     */
    function setStatus(message) {
      $element.trigger('status', message);
    }

    /**
     * Create volume meter canvas element that uses getUserMedia stream.
     */
    function createVolumeMeter() {
      canvasContext = $meter[0].getContext("2d");
      meterProcessor = audioContext.createScriptProcessor(1024, 1, 1);
      microphone.connect(analyser);
      analyser.connect(meterProcessor);
      meterProcessor.connect(audioContext.destination);
      meterProcessor.onaudioprocess = function () {
        freqData = new Uint8Array(analyser.frequencyBinCount);
        analyser.getByteFrequencyData(freqData);
        level = Math.max.apply(Math, freqData);
        canvasContext.clearRect(0, 0, $meter[0].width, $meter[0].height);
        canvasContext.fillStyle = '#00ff00';
        canvasContext.fillRect(0, 0, $meter[0].width * (level / 255), $meter[0].height);
      };
    }

    /**
     * Create audio visualizer canvas element that uses getUserMedia stream.
     */
    function createAudioVisualizer() {

      // Private function for determining current volume.
      function getVolume() {
        var values = 0;
        var length = freqData.length;

        for (var i = 0; i < length; i++) {
          values += freqData[i];
        }

        return values / length;
      }

      canvasContext = $meter[0].getContext("2d");

      visualizerProcessor = audioContext.createScriptProcessor(1024, 1, 1);
      microphone.connect(analyser);
      analyser.connect(visualizerProcessor);
      visualizerProcessor.connect(audioContext.destination);

      visualizerProcessor.onaudioprocess = function (audioProcessingEvent) {
        freqData = new Uint8Array(analyser.frequencyBinCount);
        analyser.getByteFrequencyData(freqData);
        volume = getVolume();

        if (volume === 0) {
          $meter.addClass('muted');
        }
        else {
          $meter.removeClass('muted');
        }

        barWidth = Math.ceil($meter[0].width / (analyser.frequencyBinCount * 0.5));
        canvasContext.clearRect(0, 0, $meter[0].width, $meter[0].height);
        for (var i = 0; i < analyser.frequencyBinCount; i++) {
          canvasContext.fillStyle = 'hsl(' + i / analyser.frequencyBinCount * 360 + ', 100%, 50%)';
          if ((barWidth * i) + barWidth < $meter[0].width) {
            canvasContext.fillRect(barWidth * i, $meter[0].height, barWidth - 1, -(Math.floor((freqData[i] / 255) * $meter[0].height)));
          }
        }
      };
    }

    /**
     * Toggle to recording preview.
     */
    function recordingPreview() {
      if (constraints.video) {
        $video.show();
        $audio.hide();
        $video[0].src = recordURL;
        $video[0].muted = 'muted';
        $video[0].controls = '';
        $video[0].load();
        $video[0].play();
        $meter.height(10);
      }
      else {
        $video.hide();
        $audio.hide();
        $meter.height($meter.width() / 2);
      }
    }

    /**
     * Toggle to recording preview.
     */
    function playbackPreview() {
      if (blobs.length === 0) {
        return;
      }
      if (constraints.video) {
        playbackURL = URL.createObjectURL(new Blob(blobs, {type: mimetype}));
        $video.show();
        $audio.hide();
        $video[0].src = playbackURL;
        $video[0].muted = '';
        $video[0].controls = 'controls';
        $video[0].load();
      }
      else {
        playbackURL = URL.createObjectURL(new Blob(blobs, {type: mimetype}));
        $audio.show();
        $audio[0].src = playbackURL;
        $audio[0].load();
      }
    }

    /**
     * Send a blob as form data to the server. Requires jQuery 1.5+.
     */
    function sendBlob(blob, count) {

      // Create formData object.
      var formData = new FormData();
      var req = new XMLHttpRequest();
      formData.append("mediaRecorder", blob);
      blobs = [blob];

      // Send file.
      req.addEventListener("load", transferComplete, false);
      req.open('POST', origin + Drupal.settings.basePath + 'media_recorder/record/file', true);
      req.send(formData);
      function transferComplete(evt) {
        var file = JSON.parse(req.response);
        $element.trigger('uploadFinished', file);
      }
    }

    /**
     * Stop user media stream.
     */
    function stopStream() {
      analyser.disconnect();
      microphone.disconnect();
      localStream.stop();
      $previewWrapper.hide();
      $startButton.show();
      $recordButton.hide();
      $stopButton.hide();
    }

    /**
     * Start user media stream.
     */
    function startStream() {
      if (localStream) {
        stopStream();
      }
      navigator.getUserMedia(
        constraints,
        function (stream) {
          localStream = stream;
          recordURL = URL.createObjectURL(localStream);
          mimetype = settings.constraints.video ? 'video/webm' : 'audio/ogg';
          audioContext = new AudioContext();
          analyser = audioContext.createAnalyser();
          analyser.smoothingTimeConstant = 0.75;
          analyser.fftSize = 512;
          microphone = audioContext.createMediaStreamSource(stream);
          recorder = new Recorder(microphone, {workerPath: Drupal.settings.basePath + Drupal.settings.mediaRecorder.html5url + '/recorderWorker.js'});

          $previewWrapper.show();
          $meter.show();
          $startButton.hide();
          $recordButton.show();
          $stopButton.hide();
          recordingPreview();

          if (constraints.video) {
            createVolumeMeter();
          }
          else {
            createAudioVisualizer();
          }

          setStatus('Press record to start recording.');
        },
        function (error) {
          stopStream();
          alert("There was a problem accessing your camera or mic. Please click 'Allow' at the top of the page.");
        }
      );
    }

    /**
     * Stop recording and trigger stopped event.
     */
    function start() {
      constraints = {
        audio: true,
        video: false
      };

      startStream();
    }

    /**
     * Stop recording and trigger stopped event.
     */
    function stop() {
      recorder.stop();
      recorder.exportWAV(function (blob) {
        sendBlob(blob);
      });
      recorder.clear();
      $element.trigger('recordStop');
    }

    /**
     * Start recording and trigger recording event.
     */
    function record() {
      recorder.record();
      $element.trigger('recordStart');
    }

    /**
     * Initialize all control buttons.
     */
    function initializeButtons() {

      // Click handler for enable audio button.
      $startButton.bind('click', function (event) {
        event.preventDefault();
        $startButton[0].disabled = true;
        start();
        setStatus('Allow access at top of page.');
      });

      // Click handler for record button.
      $recordButton.bind('click', function (event) {
        event.preventDefault();
        $recordButton[0].disabled = true;
        $recordButton.hide();
        $stopButton.show();
        record();
      });

      // Click handler for stop button.
      $stopButton.bind('click', function (event) {
        event.preventDefault();
        $stopButton.hide();
        $recordButton.show();
        stop();
      });
    }

    /**
     * Initialize recorder.
     */
    function initializeEvents() {

      // Listen for the record event.
      $element.bind('recordStart', function (event, data) {
        var currentSeconds = 0;
        var timeLimitFormatted = millisecondsToTime(new Date(parseInt(settings.time_limit, 10) * 1000));

        recordingPreview();
        setStatus('Recording 00:00 (Time Limit: ' + timeLimitFormatted + ')');

        statusInterval = setInterval(function () {
          currentSeconds = currentSeconds + 1;
          var currentMilliSeconds = new Date(currentSeconds * 1000);
          var time = millisecondsToTime(currentMilliSeconds);
          setStatus('Recording ' + time + ' (Time Limit: ' + timeLimitFormatted + ')');

          if (currentSeconds >= settings.time_limit) {
            stop();
          }
        }, 1000);
      });

      // Listen for the stop event.
      $element.bind('recordStop', function (event) {
        clearInterval(statusInterval);
        setStatus('Uploading, please wait...');
      });

      $element.bind('uploadFinished', function (event, data) {
        $element.find('.media-recorder-fid').val(data.fid);
        $recordButton[0].disabled = false;
        playbackPreview();
        setStatus('Press record to start recording.');
      });

      $element.bind('status', function (event, msg) {
        $statusWrapper.text(msg);
      });
    }

    /**
     * Convert milliseconds to time format.
     */
    function millisecondsToTime(milliSeconds) {
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

    /**
     * Initialize recorder.
     */
    function init(element) {
      $element = $(element);
      $statusWrapper = $element.find('.media-recorder-status');
      $previewWrapper = $element.find('.media-recorder-preview');
      $video = $element.find('.media-recorder-video');
      $audio = $element.find('.media-recorder-audio');
      $meter = $element.find('.media-recorder-meter');
      $startButton = $element.find('.media-recorder-enable');
      $recordButton = $element.find('.media-recorder-record');
      $stopButton = $element.find('.media-recorder-stop');
      $playButton = $element.find('.media-recorder-play');
      $settingsButton = $element.find('.media-recorder-settings');
      $videoButton = $element.find('.media-recorder-enable-video');
      $audioButton = $element.find('.media-recorder-enable-audio');

      // Initial state.
      $recordButton.hide();
      $stopButton.hide();
      $playButton.hide();
      $settingsButton.hide();
      $video.hide();
      $audio.hide();
      $meter.hide();
      $videoButton.hide();
      $audioButton.hide();
      $previewWrapper.hide();

      // Show file preview if file exists.
      if (Drupal.settings.mediaRecorder.file) {
        var file = Drupal.settings.mediaRecorder.file;
        switch (file.type) {
          case 'video':
            $previewWrapper.show();
            $video.show();
            $audio.hide();
            $video[0].src = Drupal.settings.mediaRecorder.file.url;
            $video[0].muted = '';
            $video[0].controls = 'controls';
            $video[0].load();
            break;
          case 'audio':
            $previewWrapper.show();
            $audio.show();
            $video.hide();
            $audio[0].src = Drupal.settings.mediaRecorder.file.url;
            $audio[0].muted = '';
            $audio[0].controls = 'controls';
            $audio[0].load();
            break;
        }
      }

      initializeButtons();
      initializeEvents();
      setStatus('Click \'Start\' to enable your mic & camera.');
    }

    return {
      init: init,
      start: start,
      record: record,
      stop: stop
    };
  })();
})(jQuery);
