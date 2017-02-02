/**
 * @file
 * Provides an interface for the FWRecorder library.
 */

(function ($) {
  'use strict';

  Drupal.kRecord = {};
  Drupal.MediaRecorderFlash = function (id, conf) {
    var settings = conf;
    var origin = window.location.origin || window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');

    var $element = $('#' + id);
    var $inputFid = $('#' + id + '-fid');
    var $statusWrapper = $element.find('.media-recorder-status');
    var $previewWrapper = $element.find('.media-recorder-preview');
    var $progressWrapper = $element.find('.media-recorder-progress');
    var $video = $element.find('.media-recorder-video');
    var $audio = $element.find('.media-recorder-audio');
    var $meter = $element.find('.media-recorder-meter');
    var $startButton = $element.find('.media-recorder-enable');
    var $recordButton = $element.find('.media-recorder-record');
    var $stopButton = $element.find('.media-recorder-stop');
    var $playButton = $element.find('.media-recorder-play');
    var $settingsButton = $element.find('.media-recorder-settings');
    var $videoButton = $element.find('.media-recorder-enable-video');
    var $audioButton = $element.find('.media-recorder-enable-audio');

    var recording = false;
    var statusInterval = null;
    var meterInterval = null;
    var constraints = {};

    // Initial state.
    $recordButton[0].disabled = false;
    $recordButton.hide();
    $stopButton.hide();
    $playButton.hide();
    $settingsButton.hide();
    $video.hide();
    $audio.hide();
    $meter.height(20);
    $meter.hide();
    $videoButton.hide();
    $audioButton.hide();
    $previewWrapper.hide();
    $progressWrapper.hide();

    // Set constraints. Video/Audio is forced for now.
    // TODO: Add audio only support if this gets resolved: https://github.com/kaltura/krecord/issues/2
    settings.constraints.audio = false;
    settings.constraints.video = true;
    constraints.audio = true;
    constraints.video = {};
    if (settings.constraints.video) {
      constraints.video = {
        width: {
          min: settings.constraints.video_width.min,
          ideal: settings.constraints.video_width.ideal,
          max: settings.constraints.video_width.max
        },
        height: {
          min: settings.constraints.video_height.min,
          ideal: settings.constraints.video_height.ideal,
          max: settings.constraints.video_height.max
        }
      };
    }

    // Show file preview if file exists.
    if (conf.file) {
      var file = conf.file;
      switch (file.type) {
        case 'video':
          $previewWrapper.show();
          $video.show();
          $audio.hide();
          $video[0].src = file.url;
          $video[0].muted = '';
          $video[0].controls = 'controls';
          $video[0].load();
          break;
        case 'audio':
          $previewWrapper.show();
          $audio.show();
          $video.hide();
          $audio[0].src = file.url;
          $audio[0].muted = '';
          $audio[0].controls = 'controls';
          $audio[0].load();
          break;
      }
    }

    /**
     * Set status message.
     */
    function setStatus(message) {
      $element.trigger('status', message);
    }

    /**
     * Updates the volume meter.
     */
    function updateVolumeMeter() {
      var level = kRecorder.getMicrophoneActivityLevel();
      $meter.width(level + '%');
      if (level <= 70) {
        $meter.css({
          'background': 'green'
        });
      }
      else if (level > 70 && level < 85) {
        $meter.css({
          'background': 'yellow'
        });
      }
      else if (level >= 85) {
        $meter.css({
          'background': 'red'
        });
      }
    }

    /**
     * Toggle to recording preview.
     */
    function playbackPreview(uploadedEntryId, preview) {
      var kapi = new kWidget.api({'wid': '_' + settings.kaltura.partnerID});
      function checkEntryStatus() {
        setStatus('Recording is being processed, but you may save anytime.');
        kapi.doRequest({
            'service': 'media',
            'action': 'get',
            'entryId': uploadedEntryId,
            'cache_st': Math.floor(Math.random() * 10000)
          },
          function (data) {
            if (data.status === 2) {
              $previewWrapper.append(preview);
              setStatus('Entry has finished processing.');
            }
            else {
              setTimeout(checkEntryStatus, 5000);
            }
          });
      }
      checkEntryStatus();
    }

    /**
     * Start user media stream.
     */
    function startStream() {
      $previewWrapper.prepend('<div id="flash-wrapper"><div id="krecorder"><p>Your browser must have JavaScript enabled and the Adobe Flash Player installed.</p></div></div>');
      $previewWrapper.show();

      swfobject.embedSWF(
        'https://' + conf.kaltura.flashVars.host + '/krecord/ui_conf_id/' + conf.kaltura.recorderUI + '/',
        'krecorder',
        $element.width(),
        $element.width() * (constraints.video.height.ideal / constraints.video.width.ideal),
        '9.0.0',
        'expressInstall.swf',
        conf.kaltura.flashVars,
        {
          allowScriptAccess: 'always',
          allowNetworking: 'all',
          wmode: 'window',
          bgcolor: '000000'
        },
        {
          id: "krecorder",
          name: "kRecorder"
        }
      );

      setStatus('Please allow access to your camera and mic.');
    }

    /**
     * Enable mic or camera.
     */
    function start() {
      if (settings.constraints.audio && !settings.constraints.video) {
        constraints = {
          audio: true,
          video: false
        };
        startStream();
      }
      else if (!settings.constraints.audio && settings.constraints.video) {
        startStream();
      }
      else {
        $startButton.hide();
        $videoButton.show();
        $audioButton.show();
        setStatus('Record with audio or video?');
      }
    }

    /**
     * Start recording and trigger recording event.
     */
    function showSettings() {
      var microphones = kRecorder.getMicrophones().split(',');
      var cameras = kRecorder.getCameras().split(',');
      var activeCamera = kRecorder.getActiveCamera();
      var activeMicrophone = kRecorder.getActiveMicrophone();
      var msg = $statusWrapper.text();

      setStatus('');

      var $settings = $('<div class="media-recorder-settings-wrapper"></div>');

      // Add the camera dropdown.
      var $camera = $('<div></div>');
      var $cameraLabel = $('<label style="display: inline;">Camera: </label>');
      var $cameraSelect = $('<select></select>');
      cameras.forEach(function (camera) {
        var selected = (activeCamera === camera) ? ' selected="selected"' : '';
        $cameraSelect.append('<option' + selected + ' value="' + camera + '">' + camera + '</option>');
      });
      $camera.append($cameraLabel).append($cameraSelect);

      // Add the microphone dropdown.
      var $microphone = $('<div></div>');
      var $microphoneLabel = $('<label style="display: inline;">Microphone: </label>');
      var $microphoneSelect = $('<select></select>');
      microphones.forEach(function (microphone) {
        var selected = (activeMicrophone === microphone) ? ' selected="selected"' : '';
        $microphoneSelect.append('<option' + selected + ' value="' + microphone + '">' + microphone + '</option>');
      });
      $microphone.append($microphoneLabel).append($microphoneSelect);

      var $doneButton = $('<button class="media-recorder-settings-finished" title="Close settings.">Done</button>');

      $cameraSelect.change(function () {
        var camera = $(this).val();
        kRecorder.setActiveCamera(camera);
      });

      $microphoneSelect.change(function () {
        var microphone = $(this).val();
        kRecorder.setActiveMicrophone(microphone);
      });

      $doneButton.bind('click', function (event) {
        event.preventDefault();
        $settingsButton[0].disabled = false;
        $statusWrapper.html('');
        setStatus(msg);
      });

      $settings.append($camera).append($microphone).append($doneButton);
      $statusWrapper.append($settings);
    }

    /**
     * Initialize all control buttons.
     */
    function initializeButtons() {

      // Click handler for enable audio button.
      $startButton.bind('click', function (event) {
        event.preventDefault();
        $startButton.hide();
        start();
      });

      // Click handler for settings button.
      $settingsButton.bind('click', function (event) {
        event.preventDefault();
        $settingsButton[0].disabled = true;
        showSettings();
      });

      // Click handler for to change to video.
      $videoButton.bind('click', function (event) {
        event.preventDefault();
        $videoButton.hide();
        $audioButton.hide();
        startStream();
      });

      // Click handler for to change to audio.
      $audioButton.bind('click', function (event) {
        event.preventDefault();
        $videoButton.hide();
        $audioButton.hide();
        constraints = {
          audio: true,
          video: false
        };
        startStream();
      });
    }

    /**
     * Initialize recorder.
     */
    function initializeEvents() {

      // Stop page unload if there is a recording in process.
      window.onbeforeunload = function () {
        if (recording) {
          return 'You are still in the process of recording, are you sure you want to leave this page?';
        }
        else {
          return null;
        }
      };

      // Listen for the record event.
      $element.bind('recordStart', function (event, data) {
        var currentSeconds = 0;
        var timeLimitFormatted = millisecondsToTime(new Date(parseInt(settings.time_limit, 10) * 1000));

        recording = true;
        setStatus('Recording 00:00 (Time Limit: ' + timeLimitFormatted + ')');

        $progressWrapper.show();
        var $progress = $progressWrapper.children('.progress-bar');
        $progress.css({
          width: '0%'
        });

        currentSeconds = 0;
        statusInterval = setInterval(function () {
          currentSeconds = currentSeconds + 1;
          var currentMilliSeconds = new Date(currentSeconds * 1000);
          var time = millisecondsToTime(currentMilliSeconds);
          var timePercentage = currentSeconds / settings.time_limit * 100;

          $progress.css({
            width: timePercentage + '%'
          });

          setStatus('Recording ' + time + ' (Time Limit: ' + timeLimitFormatted + ')');

          if (currentSeconds >= settings.time_limit) {
            stop();
          }
        }, 1000);

        $meter.show();
        meterInterval = setInterval(updateVolumeMeter, 100);
      });

      // Listen for the stop event.
      $element.bind('recordStop', function (event) {
        clearInterval(statusInterval);
        $progressWrapper.hide();
        clearInterval(meterInterval);
        $meter.hide();
        setStatus('Press save to upload recording.');
      });

      // Append file object data.
      $element.bind('refreshData', function (event, data) {
        recording = false;
        $inputFid.val(data.file.fid);
        playbackPreview(data.entry.id, data.preview);
        swfobject.removeSWF('krecorder');
        setStatus('Recording saved successfully.');
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

    Drupal.kRecord.microphoneDenied = function () {
      alert('Unable to access microphone, please make sure you have allowed access in the dialog..');
    };

    Drupal.kRecord.noMicsFound = function () {
      alert('No microphones found, please make sure you have a microphone attached.');
    };

    Drupal.kRecord.cameraDenied = function () {
      alert('Unable to access camera, please make sure you have allowed access in the dialog..');
    };

    Drupal.kRecord.noCamerasFound = function () {
      alert('No cameras found, please make sure you have a camera attached.');
    };

    Drupal.kRecord.recordStart = function () {
      $element.trigger('recordStart');
    };

    Drupal.kRecord.recordComplete = function () {
      $element.trigger('recordStop');
    };

    Drupal.kRecord.previewStarted = function () {
      $element.trigger('previewStarted');
    };

    Drupal.kRecord.previewEnd = function () {
      $element.trigger('previewEnd');
    };

    Drupal.kRecord.netconnectionConnectFailed = function () {
      alert('Unable to connect to the Kaltura server.');
    };

    Drupal.kRecord.netconnectionConnectRejected = function () {
      alert('Connection to the Kaltura server refused.');
    };

    Drupal.kRecord.connected = function () {
      kRecorder.setQuality(0, 0, constraints.video.width.ideal, constraints.video.height.ideal, 25, 25, 70);
      $settingsButton.show();
      setStatus('Press record to start recording.');
    };

    Drupal.kRecord.addEntryFailed = function (errorObj) {
      console.log(errorObj);
    };

    Drupal.kRecord.addEntryComplete = function (addedEntries) {
      var xhr = new XMLHttpRequest();
      var formData = new FormData();
      formData.append('entries', JSON.stringify(addedEntries));
      xhr.open('POST', origin + Drupal.settings.basePath + 'media_recorder/record/kaltura/entry', true);
      xhr.onload = function (evt) {
        $element.trigger('refreshData', JSON.parse(xhr.response));
      };
      xhr.onerror = function (evt) {
        alert('There was an issue saving your recording, please try again.');
      };
      xhr.send(formData);
    };

    initializeButtons();
    initializeEvents();
    setStatus('Click \'Start\' to enable your mic & camera.');
  };
})(jQuery);
