INTRODUCTION
------------
The media recorder module provides a HTML5 audio recorder with flash fallback,
for use with the media module.

REQUIREMENTS
------------
 * File Entity module (2.x) - https://drupal.org/project/file_entity
 * Media module (2.x) - https://drupal.org/project/media
 * Libraries module - https://drupal.org/project/libraries
 * RecorderJS library - https://github.com/mattdiamond/Recorderjs
 * FlashWavRecorder library - https://github.com/michalstocki/FlashWavRecorder
 * SWFObject library - https://github.com/swfobject/swfobject

INSTALLATION
------------
** Use the drush command 'drush mrdl' to automatically download the libraries.

1. Install the RecorderJS library in sites/all/libraries. The recorder.js file
   should be located at sites/{site}/libraries/Recorderjs/dist/recorder.js.

2. Install the SWFObject & FlashWavRecorder libraries in sites/all/libraries. The
   files swfobject.js and recorder.js should be at:
   swfobject.js: sites/{site}/libraries/swfobject/swfobject/swfobject.js
   recorder.js: sites/{site}/libraries/FlashWavRecorder/html/js/recorder.js

3. Install dependencies and media recorder module as per:
   https://drupal.org/documentation/install/modules-themes/modules-7

4. Visit the media recorder configuration page: admin/config/media/mediarecorder.

CREDITS
-------
Current maintainers are:
 * Norman Kerr (kenianbei) - https://drupal.org/user/778980

This project has been sponsored by:
 * Yamada Language Center - https://babel.uoregon.edu
