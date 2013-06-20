<?php

/**
 * @file
 * Admin only functionality for the media_recorder module.
 */

/**
 * Menu form callback; Display the media_recorder admin form.
 */
function media_recorder_admin_form() {
  $form = array();
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
    '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes. <br />NOTE: To save default audio in this directory, save this setting first before recording.'),
  );
  // Recorder upload directory token support.
  if (module_exists('token')) {
    $form['media_recorder_upload_directory']['#description'] .= t('This field also supports tokens.');
    $form['media_recorder_upload_tokens'] = array(
      '#theme' => 'token_tree',
      '#dialog' => TRUE,
    );
  }
  // Add default audio field based on media_recorder_add form.
  $media_recorder = variable_get('media_recorder', array());
  $media_recorder_title = isset($media_recorder['media_recorder_title']) ? $media_recorder['media_recorder_title'] : '';
  if (isset($media_recorder[LANGUAGE_NONE][0]['fid']) && !empty($media_recorder[LANGUAGE_NONE][0]['fid'])) {
    $fid = $media_recorder[LANGUAGE_NONE][0]['fid'];
  }
  elseif (isset($media_recorder[LANGUAGE_NONE][0]['media_recorder_filepath']) && !empty($media_recorder[LANGUAGE_NONE][0]['media_recorder_filepath'])) {
    $file = file_uri_to_object($filepath);
    $fid = $file->fid;
  }
  else {
    $fid = 0;
  }
  // Add class to form to designate as using media recorder widget.
  $form['#attributes']['class'][] = 'field-widget-media-recorder';
  // Add media recorder container.
  $form['media_recorder']['#type'] = 'fieldset';
  $form['media_recorder']['#title'] = t('Default Audio');
  $form['media_recorder']['#tree'] = TRUE;
  // Add title field.
  $form['media_recorder']['media_recorder_title']['#type'] = 'textfield';
  $form['media_recorder']['media_recorder_title']['#title'] = t('Title');
  $form['media_recorder']['media_recorder_title']['#default_value'] = $media_recorder_title;
  // Add hidden fid field.
  $form['media_recorder'][LANGUAGE_NONE][0]['fid']['#type'] = 'hidden';
  $form['media_recorder'][LANGUAGE_NONE][0]['fid']['#default_value'] = $fid;
  $form['media_recorder']['#process'] = array('media_recorder_add_form_process');
  // Add a hidden filepath field.
  $form['media_recorder'][LANGUAGE_NONE][0]['media_recorder_filepath']['#type'] = 'hidden';
  // Add custom validation handler for upload default audio.
  $form['#validate'][] = 'media_recorder_add_validate';
  return system_settings_form($form);
}
