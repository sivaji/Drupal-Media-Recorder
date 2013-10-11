<?php

/**
 * @file
 * Drush Integration.
 */

/**
 * Implementation of hook_drush_command().
 */
function media_recorder_drush_command() {
  $items['media-recorder-download-libraries'] = array(
    'description' => dt('Download and install all libraries associated with Media Recorder.'),
    'aliases' => array('mrdl'),
    'callback' => 'drush_media_recorder_download_libraries',
  );
  return $items;
}

/**
 * Drush command callback.
 * @see media_recorder_drush_command().
 */
function drush_media_recorder_download_libraries() {
  $base_path = drush_get_context('DRUSH_DRUPAL_CORE');

  // Create the libraries path if it doesn't exist.
  drush_mkdir($base_path . '/sites/all/libraries');

  // Check that all libraries exist.
  $required_libraries = array('swfobject', 'wami', 'Recorderjs');
  foreach ($required_libraries as $name) {

    // Get library info for each library.
    $library = libraries_detect($name);

    // Don't process if it's already installed.
    if ($library['installed']) {
      drush_log(dt('@name is already installed.', array('@name' => $name)), 'ok');
      continue;
    }

    // Creates a temp directory an change directory.
    drush_op('chdir', drush_tempdir());

    // Get library path and download link from library info.
    $library_path = $library['library path'] ? $base_path . '/' . $library['library path'] : $base_path . '/sites/all/libraries/' . $name;
    $download_url = '';
    if (!empty($library['download url'])) {
      $download_url = $library['download url'];
    }

    // Download and unzip into libraries.
    if (!empty($download_url)) {
      // Download the zip archive
      $filename = drush_download_file($download_url);

      if (!file_exists($filename)) {
        return drush_set_error(dt('Unable to download @url', array('@url' => $download_url)));
      }
      // Decompress the zip archive
      $extract = drush_tarball_extract($filename, FALSE, TRUE);
      // Move directory.
      if (is_dir($extract[0])) {
        drush_move_dir($extract[0], $library_path);
      }
      elseif (is_dir($extract[1])) {
        drush_move_dir($extract[1], $library_path);
      }
      drush_log(dt('The @name library has been downloaded to @path', array('@name' => $name, '@path' => $library_path)), 'success');
    }

    // Wami doesn't have a tarball, so we download individually.
    elseif (empty($download_url) && $name == 'wami') {
      drush_mkdir($library_path);
      drush_op('chdir', $library_path);
      drush_shell_exec('wget %s', 'https://wami-recorder.googlecode.com/hg/example/client/Wami.swf');
      drush_shell_exec('wget %s', 'https://wami-recorder.googlecode.com/hg/example/client/recorder.js');
      drush_log(dt('The @name library has been downloaded to @path', array('@name' => $name, '@path' => $library_path)), 'success');
    }

    // Return to base path.
    drush_op('chdir', $base_path);
  }

  // Make sure Modernizr is installed.
  drush_op('_modernizr_drush_download_dev');
}
