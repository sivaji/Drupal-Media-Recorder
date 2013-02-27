<?php
/**
 * @file
 * Provides markup for the media recorder.
 *
 * Variables available:
 * - $cssid: The CSS id to use for the wrapper div.
 * - $width: The width of the wrapper div.
 * - $height: The height of the wrapper div.
 * - $timelimit: The recording time limit.
 * - $audio: The themed audio element.
 */
?>

<div id="<? print $cssid; ?>" class="media-recorder-wrapper" width="<? print $width; ?>" height="<? print $height; ?>">
  <div class="media-recorder">
    <div class="controls">
      <div class="media-recorder-record record-off">
        <span>Record</span>
      </div>
    </div>
    <canvas class="media-recorder-analyser"></canvas>
    <div class="volume"></div>
    <div class="media-recorder-status">00:00 / <? print $timelimit; ?></div>
  </div>
  <div class="media-recorder-audio">
    <?php print $audio; ?>
  </div>
</div>
