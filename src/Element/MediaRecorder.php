<?php

/**
 * @file
 * Contains \Drupal\media_recorder\Element.
 */

namespace Drupal\media_recorder\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;

/**
 * Provides a media recorder render element.
 *
 * @RenderElement("media_recorder")
 */
class MediaRecorder extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $settings = media_recorder_get_settings();
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'elementProcess'],
      ],
      '#pre_render' => [
        [$class, 'preRender'],
      ],
      '#value_callback' => [$class, 'elementValue'],
      '#element_validate' => [
        [$class, 'elementValidate'],
      ],
      '#default_value' => NULL,
      '#extended' => TRUE,
      '#theme_wrappers' => ['form_element'],
      '#time_limit' => $settings['time_limit'],
      '#constraints' => $settings['constraints'],
      '#upload_location' => 'public://' . $settings['upload_directory'],
      '#upload_validators' => [
        'file_validate_extensions' => [$settings['allowed_extensions']],
      ],
    ];
  }

  /**
   * Render API callback: Attaches the requires JS Libraries.
   */
  public static function preRender($element) {
    $element['#attached']['library'][] = 'media_recorder/swfobject';
    $element['#attached']['library'][] = 'media_recorder/FlashWavRecorder';
    $element['#attached']['library'][] = 'media_recorder/Recorderjs';
    $element['#attached']['library'][] = 'media_recorder/media-recorder-api';
    $element['#attached']['library'][] = 'media_recorder/media-recorder-html5';
    $element['#attached']['library'][] = 'media_recorder/media-recorder-flash';
    $element['#attached']['library'][] = 'media_recorder/media-recorder';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function elementValue(&$element, $input = FALSE, $form_state = NULL) {

    $fid = 0;

    // Find the current value of this field from the form state.
    $form_state_fid = $form_state->getValues();

    foreach ($element['#parents'] as $parent) {
      $form_state_fid = isset($form_state_fid[$parent]) ? $form_state_fid[$parent] : 0;
    }

    if (is_numeric($form_state_fid)) {
      $fid = $form_state_fid;
    }

    // Process any input and attach files.
    if ($input !== FALSE) {
      $return = $input;

      if ($files = file_managed_file_save_upload($element, $form_state)) {
        if ($element['#multiple']) {
          $fids = array_merge($fids, array_keys($files));
        }
        else {
          $fids = array_keys($files);
        }
      }
      // Check for #filefield_value_callback values.
      // Because FAPI does not allow multiple #value_callback values like it
      // does for #element_validate and #process, this fills the missing
      // functionality to allow File fields to be extended through FAPI.
      if (isset($element['#file_value_callbacks'])) {
        foreach ($element['#file_value_callbacks'] as $callback) {
          $callback($element, $input, $form_state);
        }
      }
      // Load file if the FID has changed to confirm it exists.
      if (isset($input['fallback'][0]['fid']) && $file = file_load($input['fallback'][0]['fid'])) {
        $fid = $file->id();
      }
      elseif (isset($input['fallback']['fid']) && $file = file_load($input['fallback']['fid'])) {
        $fid = $file->id();
      }
      elseif (isset($input['fid']) && $file = file_load($input['fid'])) {
        $fid = $file->id();
      }
    }

    // If there is no input, set the default value.
    else {
      if ($element['#extended']) {
        $default_fid = isset($element['#default_value']['fid']) ? $element['#default_value']['fid'] : 0;
        $return = isset($element['#default_value']) ? $element['#default_value'] : ['fid' => 0];
      }
      else {
        $default_fid = isset($element['#default_value']) ? $element['#default_value'] : 0;
        $return = ['fid' => 0];
      }

      // Confirm that the file exists when used as a default value.
      if ($default_fid && $file = file_load($default_fid)) {
        $fid = $file->id();
      }
    }
    $return['fids'] = $fid;
    return $return;
  }

  /**
   * Render API callback: Expands the media_recorder element type.
   *
   * Provides player for recording & playback.
   */
  function elementProcess(&$element, FormStateInterface $form_state, &$complete_form) {
    $settings = media_recorder_get_settings();
    $fid = isset($element['#value']['fids']) ? $element['#value']['fids'] : 0;

    $file = $file_obj = NULL;
    $id = $element['#id'];

    // If the element as a default file, add the absolute url to the file.
    if (!empty($fid) && !is_object($fid) && $file = file_load($fid)) {

      $file->url = file_create_url($file->get('uri')->value);

      $file_obj = new \stdClass();
      $file_obj->fid = $file->id();
      // FIXME
      $file_obj->type = 'audio'; //$file->get('bundle')->value;
      $file_obj->filemime = $file->getMimeType();
      $file_obj->url = $file->url;
    }

    // Tokenize upload_location if token is enabled.
    $element['#upload_location'] = \Drupal::moduleHandler()->moduleExists('token') ? \Drupal::token()->replace($element['#upload_location']) : $element['#upload_location'];

    // Specify a #tree element since we will have children elements.
    $element['#tree'] = TRUE;

    // Add the javascript based recorder, which will be shown only if the browser
    // supports the required features.
    $element['recorder'] = [
      '#prefix' => '<div id="' . $id . '" class="media-recorder-wrapper" style="display: none;">',
      '#suffix' => '</div>',
      '#theme' => 'media_recorder',
    ];

    // Add a fallback upload element for devices unable to use the recorder.
    $element['fallback'] = [
      '#type' => 'managed_file',
      '#id' => $id . '-fallback',
      '#description' => t('Allowed extensions: @extensions', ['@extensions' => $element['#upload_validators']['file_validate_extensions'][0]]),
      '#default_value' => $fid,
      '#upload_location' => $element['#upload_location'],
      '#upload_validators' => $element['#upload_validators'],
    ];

    $element['upload_button'] = [
       '#type' => 'submit',
       '#value' => t('Upload'),
       '#attributes' => ['class' => ['js-hide']],
       '#validate' => [],
       '#submit' => ['file_managed_file_submit'],
    // '#limit_validation_errors' => [$element['#parents']],
    // '#ajax' => $ajax_settings,
    // '#weight' => -5,
    ];

    // Add a hidden fid field for storing the returned recorded file using ajax.
    $element['fids'] = [
      '#type' => 'hidden',
      '#value' => $fid,
      '#attributes' => ['id' => [$id . '-fids']],
    ];

    // Add javascript libraries.
    $element['#attached'] = [
      'drupalSettings' => [
        'mediaRecorder' => [
          'swfPath' => libraries_get_path('FlashWavRecorder'),
          'workerPath' => libraries_get_path('Recorderjs'),
          'elements' => [
            [
              'id' => $id,
              'conf' => [
                'time_limit' => $element['#time_limit'],
                'constraints' => $element['#constraints'],
                'file' => $file_obj,
                'upload_location' => $element['#upload_location'],
              ],
            ],
          ],
        ],
      ],
    ];

    // Add custom css if enabled.
    if ($settings['css']) {
      // $element['#attached']['css'] = array(
      //   array(
      //     'type' => 'file',
      //     'data' => drupal_get_path('module', 'media_recorder') . '/css/media-recorder.css',
      //   ),
      // );
    }

    // Add Media: Kaltura support if enabled.
    if (\Drupal::moduleHandler()->moduleExists('media_kaltura') && $settings['kaltura']['enable'] && $settings['kaltura']['server'] && $settings['kaltura']['recorder']) {

      // Attempt to start a Kaltura session.
      try {
        // $server = media_kaltura_server_load($settings['kaltura']['server']);
        // $kaltura = media_kaltura_start_session($server);

        // // Override fallback with kaltura upload.
        // $element['fallback'] = array(
        //   '#type' => 'media_kaltura_upload',
        //   '#required' => TRUE,
        //   '#multiple' => FALSE,
        //   '#cardinality' => 1,
        //   '#extensions' => explode(' ', $element['#upload_validators']['file_validate_extensions'][0]),
        //   '#api_url' => $server->api_url,
        //   '#ks' => $kaltura['session'],
        //   '#default_value' => array(
        //     array(
        //       'fid' => $fid,
        //     ),
        //   ),
        //   '#attributes' => array(
        //     'class' => array(
        //       'media-recorder-fallback',
        //     ),
        //   ),
        // );

        // // Add Kaltura related javascript.
        // unset($element['#attached']['js']['media-recorder-html5']);
        // $element['#attached']['js']['media-recorder-api']['data'] = drupal_get_path('module', 'media_recorder') . '/js/media-recorder-api-kaltura.js';
        // $element['#attached']['js']['media-recorder-flash']['data'] = drupal_get_path('module', 'media_recorder') . '/js/media-recorder-flash-kaltura.js';
        // $element['#attached']['js']['kwidget'] = array(
        //   'type' => 'file',
        //   'data' => '//' . $server->domain . '/p/' . $server->partner_id . '/sp/' . $server->subpartner_id . '/embedIframeJs/uiconf_id/' . $server->uiconf_id . '/partner_id/' . $server->partner_id,
        //   'scope' => 'footer',
        //   'external' => TRUE,
        // );
        // $element['#attached']['js'][0]['data']['mediaRecorder']['elements'][0]['conf']['kaltura'] = array(
        //   'ks' => $kaltura['session'],
        //   'partnerID' => $server->partner_id,
        //   'apiUrl' => $server->api_url,
        //   'playerUI' => $server->uiconf_id,
        //   'recorderUI' => $settings['kaltura']['recorder'],
        //   'flashVars' => array(
        //     'pid' => $server->partner_id,
        //     'ks' => $kaltura['session'],
        //     'isH264' => TRUE,
        //     'h264profile' => 'main',
        //     'h264level' => 3,
        //     'showUI' => TRUE,
        //     'autoPreview' => FALSE,
        //     'showPreviewTimer' => TRUE,
        //     'removePlayer' => FALSE,
        //     'disableglobalclick' => FALSE,
        //     'limitRecord' => $element['#time_limit'],
        //     'showErrorMessage' => TRUE,
        //     'themeURL' => '//' . $server->domain . '/p/' . $server->partner_id . '/sp/0/flash/krecord/v1.7.2/skin.swf',
        //     'localeURL' => '//' . $server->domain . '/p/' . $server->partner_id . '/sp/0/flash/krecord/v1.7.2/locale.xml',
        //     'host' => $server->domain,
        //     'delegate' => 'Drupal.kRecord',
        //     'debugMode' => TRUE,
        //     'conversionQuality' => $settings['kaltura']['profile'],
        //   ),
        // );
      }
      catch (Exception $e) {
        \Drupal::logger('media_kaltura')->error('There was a problem connecting to the kaltura server: @error', ['@error' => $e->getMessage()]);
      }
    }
    return $element;
  }


  /**
   * Render API callback: Validates the media_recorder element.
   */
  function elementValidate($element, FormStateInterface $form_state) {

    $fid = isset($element['#value']['fid']) ? $element['#value']['fid'] : $element['#value']['fallback']['fid'];

    // Check required property based on the FID.
    if ($element['#required'] && empty($fid)) {
      $form_state->setError($element, t('%name is a required field.', ['%name' => $element['#title']]));
    }

    if (!empty($fid) && !is_object($fid) && $file = file_load($fid)) {
      $scheme = \Drupal::service("file_system")->uriScheme($file->get('uri')->value);

      // Validate local files.
      if ($scheme === 'public' || $scheme === 'private') {
        $file_validate_errors = file_validate($file, $element['#upload_validators']);
        if ($file_validate_errors) {
          $form_state->setError($element, implode('<br />', $file_validate_errors));
        }
      }
    }
  }
}
