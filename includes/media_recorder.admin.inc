<?php
/**
 * @file
 * Admin only functionality for the media_recorder module.
 */

/**
 * Menu form callback; Display the media_recorder admin form.
 */
function media_recorder_admin_form($form, $form_state) {

  // Check that all libraries exist.
  $required_libraries = array('swfobject', 'FlashWavRecorder', 'Recorderjs');
  foreach ($required_libraries as $name) {
    $library = libraries_detect($name);
    if (!$library['installed']) {
      drupal_set_message($library['error message'], 'error');
    }
  }

  // Recorder constraints.
  $constraints = variable_get('media_recorder_constraints', array(
    'audio' => TRUE,
    'video' => TRUE,
    'video_resolution' => 320,
  ));
  $form['media_recorder_constraints'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('Media Constraints'),
    '#description' => t('Select which recording options will be available using the Media Browser.'),
  );
  $form['media_recorder_constraints']['audio'] = array(
    '#type' => 'checkbox',
    '#title' => t('Audio'),
    '#default_value' => $constraints['audio'],
  );
  $form['media_recorder_constraints']['video'] = array(
    '#type' => 'checkbox',
    '#title' => t('Video'),
    '#default_value' => $constraints['video'],
  );
  $form['media_recorder_constraints']['video_resolution'] = array(
    '#type' => 'radios',
    '#title' => t('Video Resolution'),
    '#default_value' => $constraints['video_resolution'],
    '#options' => array(
      640 => t('640 x 480'),
      480 => t('480 x 360'),
      320 => t('320 x 240'),
      240 => t('240 x 180'),
      180 => t('180 x 135'),
    ),
  );

  // Recorder CSS enabled.
  $form['media_recorder_css'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use default CSS stylesheet?'),
    '#description' => t('Disable if you want to use a different set of styles without having to override the default CSS.'),
    '#default_value' => variable_get('media_recorder_css', TRUE),
  );

  // Recorder time limit.
  $form['media_recorder_time_limit'] = array(
    '#type' => 'textfield',
    '#title' => t('Time Limit'),
    '#description' => t('Time limit in seconds. Defaults to 300 seconds (5 minutes).'),
    '#default_value' => variable_get('media_recorder_time_limit', 300),
    '#element_validate' => array('element_validate_integer_positive'),
    '#required' => TRUE,
  );

  // Recorder allowed entensions.
  $form['media_recorder_default_allowed_extensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Default allowed file extensions'),
    '#default_value' => variable_get('media_recorder_default_allowed_extensions', 'wav mp3 m4a ogg oga weba wma mov mp4 wmv m4v mpeg avi ogv webp webm'),
    '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
    '#maxlength' => NULL,
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
