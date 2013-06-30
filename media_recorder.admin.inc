<?php

/**
 * @file
 * Admin only functionality for the media_recorder module.
 */

/**
 * Menu form callback; Display the media_recorder admin form.
 */
function media_recorder_admin_form($form, $form_state) {

  // Prepares upload directory for current user.
  $upload_directory = 'public://' . token_replace(variable_get('media_recorder_upload_directory', ''));
  file_prepare_directory($upload_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

  // Check that all libraries exist.
  $required_libraries = array('swfobject', 'wami', 'Recorderjs');
  foreach ($required_libraries as $name) {
    $library = drupal_get_library('media_recorder', $name);
    if (!file_exists(key($library['js']))) {
      $message = '';
      $link = l(t('README.txt'), drupal_get_path('module', 'media_recorder') . '/README.txt');
      $message = t('The %title library is not installed.', array('%title' => $library['title']));
      $message .= t('Please view the !link for download instructions.', array('!link' => $link));
      drupal_set_message($message, 'error');
    }
  }

  // Recorder width.
  $form['media_recorder_width'] = array(
    '#type' => 'textfield',
    '#title' => t('Media recorder width'),
    '#default_value' => variable_get('media_recorder_width', 300),
  );

  // Recorder height.
  $form['media_recorder_height'] = array(
    '#type' => 'textfield',
    '#title' => t('Media recorder height'),
    '#default_value' => variable_get('media_recorder_height', 100),
  );

  // Recorder time limit.
  $time_limit = gmdate('i:s', variable_get('media_recorder_timelimit', 300));
  $form['media_recorder_timelimit'] = array(
    '#type' => 'textfield',
    '#title' => t('Media recorder time limit'),
    '#description' => t('Enter a time limit for recordings in seconds. Currently set to @time minutes.', array('@time' => $time_limit)),
    '#default_value' => variable_get('media_recorder_timelimit', 300),
  );

  // Recorder upload directory.
  $form['media_recorder_upload_directory'] = array(
    '#type' => 'textfield',
    '#title' => t("File directory for uploaded recordings"),
    '#default_value' => variable_get('media_recorder_upload_directory', ''),
    '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
  );

  // Recorder upload directory token support.
  if (module_exists('token')) {
    $form['media_recorder_upload_directory']['#description'] .= t('This field also supports tokens.');
    $form['media_recorder_upload_tokens'] = array(
      '#theme' => 'token_tree',
      '#dialog' => TRUE,
    );
  }

  return system_settings_form($form);
}
