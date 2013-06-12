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

<div id="<?php print $cssid; ?>" class="media-recorder-wrapper js-hide" style="width:<?php print $width; ?>px; height:<?php print $height + 30; ?>px;">
  <div class="media-recorder-audio" style="width:<? print $width; ?>px; height: 30px;">
    <?php print $audio; ?>
  </div>
</div>
