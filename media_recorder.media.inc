<?php

/**
 * @file
 * Media module integration for the Media internet module.
 */

/**
 * Implements hook_media_browser_plugin_info().
 */
function media_recorder_media_browser_plugin_info() {
  $info['media_recorder'] = array(
    'title' => t('Voice Recorder'),
    'class' => 'MediaBrowserRecorder',
  );
  return $info;
}
