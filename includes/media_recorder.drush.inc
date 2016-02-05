<?php
/**
 * @file
 * Drush Integration for the media_recorder module.
 */

/**
 * Implements hook_drush_command().
 */
function media_recorder_drush_command() {
  $items = array();

  $items['media-recorder-download-libraries'] = array(
    'description' => dt('Download and install all libraries associated with Media Recorder.'),
    'aliases' => array('mrdl'),
    'callback' => 'drush_media_recorder_download_libraries',
  );

  return $items;
}

/**
 * Drush command callback.
 * @see media_recorder_drush_command()
 */
function drush_media_recorder_download_libraries() {
  if (!module_exists('libraries')) {
    return FALSE;
  }

  // Get base path.
  $base_path = drush_get_context('DRUSH_DRUPAL_CORE');

  // Get sites path.
  $site_path = (conf_path() == 'sites/default') ? 'sites/all' : conf_path();

  // Check that all libraries exist.
  $required_libraries = array('swfobject', 'FlashWavRecorder', 'Recorderjs');
  foreach ($required_libraries as $name) {

    // Get library info for each library.
    $library = libraries_detect($name);

    // Creates a temp directory an change directory.
    drush_op('chdir', drush_tempdir());

    // Get library path and download link from library info.
    $library_path = $base_path . '/' . $site_path . '/libraries/' . $name;

    // Ask to overwrite if library already exists.
    if (file_prepare_directory($library_path)) {
      $confirm = drush_confirm(dt('A version of @name already exists. Do you want to overwrite it?', array('@name' => $name, '@path' => $library_path)));
      if ($confirm) {
        drush_delete_dir($library_path, TRUE);
      }
      else {
        drush_log(dt('Skip installation of @name to @path.', array('@name' => $name, '@path' => $library_path)), 'warning');
        continue;
      }
    }

    $download_url = '';
    if (!empty($library['download url'])) {
      $download_url = $library['download url'];
    }

    // Download and unzip into libraries.
    if (!empty($download_url)) {

      // Download the zip archive.
      $filename = drush_download_file($download_url);

      if (!file_exists($filename)) {
        return drush_set_error(dt('Unable to download @url', array('@url' => $download_url)));
      }

      // Decompress the zip archive.
      $extract = drush_tarball_extract($filename, FALSE, TRUE);

      // Move directory.
      if (is_dir($extract[0]) && drush_move_dir($extract[0], $library_path)) {
        drush_log(dt('The @name library has been downloaded to @path', array('@name' => $name, '@path' => $library_path)), 'success');
      }
      elseif (is_dir($extract[1]) && drush_move_dir($extract[1], $library_path)) {
        drush_log(dt('The @name library has been downloaded to @path', array('@name' => $name, '@path' => $library_path)), 'success');
      }
      else {
        drush_log(dt('Unable to download @name library to @path', array('@name' => $name, '@path' => $library_path)), 'success');
      }
    }

    // Return to base path.
    drush_op('chdir', $base_path);

  }

  return NULL;
}
