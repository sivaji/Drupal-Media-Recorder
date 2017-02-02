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
  $form['media_recorder']['constraints']['video_width'] = array(
    '#type' => 'fieldset',
    '#title' => t('Width'),
    'min' => array(
      '#type' => 'textfield',
      '#title' => t('Minimum Width'),
      '#default_value' => $settings['constraints']['video_width']['min'],
      '#element_validate' => array('element_validate_integer_positive'),
    ),
    'ideal' => array(
      '#type' => 'textfield',
      '#title' => t('Ideal Width'),
      '#default_value' => $settings['constraints']['video_width']['ideal'],
      '#element_validate' => array('element_validate_integer_positive'),
    ),
    'max' => array(
      '#type' => 'textfield',
      '#title' => t('Maximum Width'),
      '#default_value' => $settings['constraints']['video_width']['max'],
      '#element_validate' => array('element_validate_integer_positive'),
    ),
  );
  $form['media_recorder']['constraints']['video_height'] = array(
    '#type' => 'fieldset',
    '#title' => t('Height'),
    'min' => array(
      '#type' => 'textfield',
      '#title' => t('Minimum Height'),
      '#default_value' => $settings['constraints']['video_height']['min'],
      '#element_validate' => array('element_validate_integer_positive'),
    ),
    'ideal' => array(
      '#type' => 'textfield',
      '#title' => t('Ideal Height'),
      '#default_value' => $settings['constraints']['video_height']['ideal'],
      '#element_validate' => array('element_validate_integer_positive'),
    ),
    'max' => array(
      '#type' => 'textfield',
      '#title' => t('Maximum Height'),
      '#default_value' => $settings['constraints']['video_height']['max'],
      '#element_validate' => array('element_validate_integer_positive'),
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

  // Media: Kaltura integration.
  if (module_exists('media_kaltura')) {

    // Load existing servers and add to options array.
    $rows = array();
    $options = array();
    $servers = entity_load('media_kaltura_server');
    foreach ($servers as $server) {
      $rows[$server->id] = array(
        'domain' => $server->domain,
        'force_ssl' => $server->force_ssl ? 'Yes' : 'No',
        'api' => $server->api ? 'Enabled' : 'Disabled',
        'partner_id' => $server->partner_id,
        'subpartner_id' => $server->subpartner_id,
        'user_id' => $server->user_id,
        'secret' => $server->secret,
        'uiconf_id' => $server->uiconf_id,
        'operations' => l(t('edit'), 'admin/config/media/media_kaltura/server/' . $server->id),
      );
      $options[$server->id] = $server->domain;
    }

    // Entry creation settings.
    $form['media_recorder']['kaltura'] = array(
      '#type' => 'fieldset',
      '#title' => t('Media: Kaltura Integration'),
      'enable' => array(
        '#type' => 'checkbox',
        '#title' => t('Enable Media: Kaltura integration?'),
        '#description' => t('Recordings will be sent directly to the specified Kaltura server rather than local storage.'),
        '#default_value' => $settings['kaltura']['enable'],
      ),
      'server' => array(
        '#type' => 'select',
        '#title' => t('Default server to upload files to?'),
        '#description' => t('Files will be automatically uploaded to the selected Kaltura server.'),
        '#default_value' => $settings['kaltura']['server'],
        '#options' => $options,
        '#states' => array(
          'disabled' => array(
            ':input[name="media_recorder[kaltura][enable]"]' => array('checked' => FALSE),
          ),
        ),
      ),
    );

    // Server specific options.
    if ($settings['kaltura']['enable'] && isset($settings['kaltura']['server'])) {

      // Attempt to start a Kaltura session.
      try {
        $server = media_kaltura_server_load($settings['kaltura']['server']);
        if (!$server) {
          throw new Exception('Unable to load Kaltura server.');
        }
        $kaltura = media_kaltura_start_session($server);
        if (!$kaltura) {
          throw new Exception('Unable to start Kaltura session.');
        }

        // Get existing kRecord widgets for this server.
        $filter = new KalturaUiConfFilter(array(
          'objTypeEqual' => 7,
        ));
        $result = $kaltura['client']->uiConf->listAction($filter);
        $recorders = array();
        foreach ($result->objects as $object) {
          if ($object->swfUrlVersion >= '1.7') {
            $recorders[$object->id] = $object->name;
          }
        }

        // Recorder Kaltura recorder widget.
        if (count($recorders)) {
          $form['media_recorder']['kaltura']['recorder'] = array(
            '#type' => 'select',
            '#title' => t('Select a Recorder Widget (Flash Fallback)'),
            '#description' => t('The kRecord widget must be version 1.7.2 or higher. Please note that these widgets are not normally available in the KMC and must be created through the Kaltura Client API.'),
            '#options' => $recorders,
            '#default_value' => isset($settings['kaltura']['recorder']) ? $settings['kaltura']['recorder'] : '',
            '#disabled' => !$settings['kaltura']['server'],
            '#states' => array(
              'disabled' => array(
                ':input[name="media_recorder[kaltura][enable]"]' => array('checked' => FALSE),
              ),
            ),
          );
        }
        else {
          $form['media_recorder']['kaltura']['markup'] = array(
            '#markup' => '<div>No compatible recorders found, would you like to create one?</div>',
          );
          $form['media_recorder']['kaltura']['create'] = array(
            '#type' => 'submit',
            '#value' => 'Create a Recorder Widget',
            '#submit' => array('media_recorder_admin_form_create_recorder_widget_submit'),
          );
        }

        // Default transcoding profile.
        $filter = new KalturaConversionProfileFilter();
        $results = $kaltura['client']->conversionProfile->listAction($filter);
        $profiles = array('-- No Default --');
        foreach ($results->objects as $result) {
          $profiles[$result->id] = $result->name;
        }
        $form['media_recorder']['kaltura']['profile'] = array(
          '#type' => 'select',
          '#title' => t('Select a Default Transcoding Profile'),
          '#description' => t('All new media entries will automatically be transcoded using this transcoding profile.'),
          '#options' => $profiles,
          '#default_value' => isset($settings['kaltura']['profile']) ? $settings['kaltura']['profile'] : 0,
          '#states' => array(
            'disabled' => array(
              ':input[name="media_recorder[kaltura][enable]"]' => array('checked' => FALSE),
            ),
          ),
        );

        // Default category.
        $filter = new KalturaCategoryFilter();
        $results = $kaltura['client']->category->listAction($filter);
        $categories = array('-- No Default --');
        foreach ($results->objects as $result) {
          $categories[$result->id] = $result->fullName;
        }
        $form['media_recorder']['kaltura']['category'] = array(
          '#type' => 'select',
          '#title' => t('Select a Default Category'),
          '#description' => t('All new media entries will be placed within this default category.'),
          '#options' => $categories,
          '#default_value' => isset($settings['kaltura']['category']) ? $settings['kaltura']['category'] : 0,
          '#states' => array(
            'disabled' => array(
              ':input[name="media_recorder[kaltura][enable]"]' => array('checked' => FALSE),
            ),
          ),
        );
      }
      catch (Exception $e) {
        watchdog('media_kaltura', 'There was a problem connecting to the kaltura server: @error', array('@error' => $e->getMessage()), WATCHDOG_ERROR);
      }
    }
  }

  return system_settings_form($form);
}

/**
 * Validate handler for media_recorder_admin_form().
 */
function media_recorder_admin_form_validate($form, &$form_state) {
  if ($form_state['values']['media_recorder']['kaltura']['enable']) {
    $server = media_kaltura_server_load($form_state['values']['media_recorder']['kaltura']['server']);
    if (!$server) {
      form_set_error('media_recorder][kaltura][enable', t('Unable to load server. Please check that your server information is correct.'));
    }
    $kaltura = media_kaltura_start_session($server);
    if (!$kaltura) {
      form_set_error('media_recorder][kaltura][enable', t('Unable to connect to server. Please check that your server information is correct.'));
    }
  }
}

/**
 * Submit callback for creating a Kaltura recorder widget.
 *
 * @see media_recorder_admin_form()
 */
function media_recorder_admin_form_create_recorder_widget_submit($form, &$form_state) {
  $settings = media_recorder_get_settings();

  // Attempt to start a Kaltura session.
  try {
    $server = media_kaltura_server_load($settings['kaltura']['server']);
    if (!$server) {
      throw new Exception('Unable to load Kaltura server.');
    }
    $kaltura = media_kaltura_start_session($server);
    if (!$kaltura) {
      throw new Exception('Unable to start Kaltura session.');
    }
  }
  catch (Exception $e) {
    watchdog('media_kaltura', 'There was a problem connecting to the kaltura server: @error', array('@error' => $e->getMessage()), WATCHDOG_ERROR);
  }

  // Create a new kRecord widget.
  $uiConf = new KalturaUiConf();
  $uiConf->name = 'Media Recorder: kRecord Widget';
  $uiConf->objType = 7;
  $uiConf->width = 400;
  $uiConf->height = 300;
  $uiConf->swfUrl = '/flash/krecord/v1.7.2/KRecord.swf';
  $uiConf->swfUrlVersion = '1.7.2';
  $uiConf->confFile = '<!-- KRecord does not require XML -->';
  $uiConf->creationMode = 3;
  $result = $kaltura['client']->uiConf->add($uiConf);

  // Add widget to settings.
  if ($result && isset($result->id)) {
    $settings = media_recorder_get_settings();
    $settings['kaltura']['recorder'] = $result->id;
    variable_set('media_recorder', $settings);
    drupal_set_message(t('kRecord widget successfully created.'));
  }
}
