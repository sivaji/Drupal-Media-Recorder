<?php
/**
 * @file
 * Admin only functionality for the media_recorder module.
 */

/**
 * Menu form callback; Display the media_recorder admin form.
 */
function media_recorder_admin_form($form, $form_state) {
  $settings = media_recorder_get_settings();

  // Check that all libraries exist.
  $required_libraries = array('swfobject', 'FlashWavRecorder', 'Recorderjs');
  foreach ($required_libraries as $name) {
    $library = libraries_detect($name);
    if (!$library['installed']) {
      drupal_set_message($library['error message'], 'error');
    }
  }

  $form['media_recorder'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
  );

  // Recorder constraints.
  $form['media_recorder']['constraints'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('Media Constraints'),
    '#description' => t('Select which recording options will be available using the Media Browser.'),
  );
  $form['media_recorder']['constraints']['audio'] = array(
    '#type' => 'checkbox',
    '#title' => t('Audio'),
    '#default_value' => $settings['constraints']['audio'],
  );
  $form['media_recorder']['constraints']['video'] = array(
    '#type' => 'checkbox',
    '#title' => t('Video'),
    '#default_value' => $settings['constraints']['video'],
  );
  $form['media_recorder']['constraints']['video_resolution'] = array(
    '#type' => 'radios',
    '#title' => t('Video Resolution'),
    '#default_value' => $settings['constraints']['video_resolution'],
    '#options' => array(
      640 => t('640 x 480'),
      480 => t('480 x 360'),
      320 => t('320 x 240'),
      240 => t('240 x 180'),
      180 => t('180 x 135'),
    ),
  );

  // Recorder CSS enabled.
  $form['media_recorder']['css'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use default CSS stylesheet?'),
    '#description' => t('Disable if you want to use a different set of styles without having to override the default CSS.'),
    '#default_value' => $settings['css'],
  );

  // Recorder time limit.
  $form['media_recorder']['time_limit'] = array(
    '#type' => 'textfield',
    '#title' => t('Time Limit'),
    '#description' => t('Time limit in seconds. Defaults to 300 seconds (5 minutes).'),
    '#default_value' => $settings['time_limit'],
    '#element_validate' => array('element_validate_integer_positive'),
    '#required' => TRUE,
  );

  // Recorder allowed entensions.
  $form['media_recorder']['allowed_extensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Default allowed file extensions'),
    '#default_value' => $settings['allowed_extensions'],
    '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
    '#maxlength' => NULL,
  );

  // Recorder upload directory.
  $form['media_recorder']['upload_directory'] = array(
    '#type' => 'textfield',
    '#title' => t("File directory for uploaded recordings"),
    '#default_value' => $settings['upload_directory'],
    '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
  );

  // Recorder upload directory token support.
  if (module_exists('token')) {
    $form['media_recorder']['upload_directory']['#description'] .= t('This field also supports tokens.');
    $form['media_recorder']['upload_tokens'] = array(
      '#theme' => 'token_tree',
      '#dialog' => TRUE,
    );
  }

  return system_settings_form($form);
}
