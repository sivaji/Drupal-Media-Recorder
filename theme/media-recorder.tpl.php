<?php
/**
 * @file
 * Media recorder template.
 *
 * @see template_preprocess()
 * @see template_process()
 *
 * Important: Do not change any classes for elements. Media Recorder uses
 * these to bind recorder functionality. Adding classes will not affect this.
 */
?>

<div class="media-recorder">
  <div class="media-recorder-preview">
    <video class="media-recorder-video"></video>
    <canvas class="media-recorder-meter"></canvas>
    <audio class="media-recorder-audio" controls></audio>
  </div>
  <div class="media-recorder-status"></div>
  <div class="media-recorder-progress progress">
    <div class="progress-bar" role="progressbar"></div>
  </div>
  <div class="media-recorder-controls">
    <button class="media-recorder-enable" title="Click to enable your mic & camera.">Start</button>
    <button class="media-recorder-record" title="Click to start recording.">Record</button>
    <button class="media-recorder-stop" title="Click to stop recording.">Stop</button>
    <button class="media-recorder-play" title="Click to play recording.">Play</button>
    <button class="media-recorder-settings" title="Click to access settings.">Settings</button>
    <button class="media-recorder-enable-audio" title="Click to enable your mic.">Audio</button>
    <button class="media-recorder-enable-video" title="Click to enable your camera.">Video</button>
  </div>
</div>
