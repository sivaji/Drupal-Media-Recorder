<?php

/**
 * @file
 * Contains \Drupal\media_recorder\Element.
 */

namespace Drupal\media_recorder\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a media recorder render element.
 *
 * @see render_example_theme()
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
        [$class, 'media_recorder_element_process'],
      ],
      '#pre_render' => [
        [$class, 'preRenderText'],
      ],
      // '#value_callback' => [$class, 'media_recorder_element_value'],
      // '#element_validate' => [$class, 'media_recorder_element_validate'],
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

  public static function preRenderText($element) {

    $id = $element['#id'];
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
   * Process callback for the media_recorder form element.
   *
   * @see media_recorder_element_info()
   */
  function media_recorder_element_process(&$element, FormStateInterface $form_state, &$complete_form) {

    $settings = media_recorder_get_settings();
    $fid = isset($element['#value']['fid']) ? $element['#value']['fid'] : 0;
    $file = NULL;
    $id = $element['#id'];

    // If the element as a default file, add the absolute url to the file.
    if (!empty($fid) && $file = file_load($fid)) {
      $file->url = file_create_url($file->uri);
    }

    // Tokenize upload_location if token is enabled.
    $element['#upload_location'] = \Drupal::moduleHandler()->moduleExists('token') ? \Drupal::token()->replace($element['#upload_location']) : $element['#upload_location'];

    // Specify a #tree element since we will have children elements.
    $element['#tree'] = TRUE;

    // Add the javascript based recorder, which will be shown only if the browser
    // supports the required features.
    $element['recorder'] = array(
      '#prefix' => '<div id="' . $id . '" class="media-recorder-wrapper" style="display: none;">',
      '#suffix' => '</div>',
      '#theme' => 'media_recorder',
    );

    // Add a fallback upload element for devices unable to use the recorder.
    $element['fallback'] = array(
      '#type' => 'managed_file',
      '#id' => $id . '-fallback',
      '#description' => t('Allowed extensions: @extensions', array('@extensions' => $element['#upload_validators']['file_validate_extensions'][0])),
      '#default_value' => $fid,
      '#upload_location' => $element['#upload_location'],
      '#upload_validators' => $element['#upload_validators'],
    );

    // Add a hidden fid field for storing the returned recorded file using ajax.
    $element['fid'] = array(
      '#type' => 'hidden',
      '#value' => $fid,
      '#attributes' => array('id' => array($id . '-fid')),
    );

    // Add javascript libraries.
    $element['#attached'] = array(
      // 'libraries_load' => array(
      //   array('swfobject'),
      //   array('FlashWavRecorder'),
      //   array('Recorderjs'),
      // ),
      // 'js' => array(
      'drupalSettings' => array(
        // 'media-recorder-api' => array(
        //   'type' => 'file',
        //   'data' => drupal_get_path('module', 'media_recorder') . '/js/media-recorder-api.js',
        //   'scope' => 'footer',
        // ),
        // 'media-recorder-html5' => array(
        //   'type' => 'file',
        //   'data' => drupal_get_path('module', 'media_recorder') . '/js/media-recorder-html5.js',
        //   'scope' => 'footer',
        // ),
        // 'media-recorder-flash' => array(
        //   'type' => 'file',
        //   'data' => drupal_get_path('module', 'media_recorder') . '/js/media-recorder-flash.js',
        //   'scope' => 'footer',
        // ),
        // 'media-recorder' => array(
        //   'type' => 'file',
        //   'data' => drupal_get_path('module', 'media_recorder') . '/js/media-recorder.js',
        //   'scope' => 'footer',
        // ),
        // array(
        //   'type' => 'setting',
        //   'data' => array(
            'mediaRecorder' => array(
              'swfPath' => libraries_get_path('FlashWavRecorder'),
              'workerPath' => libraries_get_path('Recorderjs'),
              'elements' => array(
                array(
                  'id' => $id,
                  'conf' => array(
                    'time_limit' => $element['#time_limit'],
                    'constraints' => $element['#constraints'],
                    'file' => $file,
                    'upload_location' => $element['#upload_location'],
                  ),
                ),
              ),
            ),
          // ),
          // 'scope' => 'header',
        // ),
      ),
    );

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
        \Drupal::logger('media_kaltura')->error('There was a problem connecting to the kaltura server: @error', array('@error' => $e->getMessage()));
      }
    }

    return $element;
  }


  /**
   * Validate media_recorder form elements.
   *
   * @see media_recorder_element_info()
   */
  function media_recorder_element_validate(&$element) {
    $fid = isset($element['#value']['fid']) ? $element['#value']['fid'] : $element['#value']['fallback']['fid'];

    // Check required property based on the FID.
    if ($element['#required'] && empty($fid)) {
      form_error($element, t('%name is a required field.', array('%name' => $element['#title'])));
    }

    if (!empty($fid) && $file = file_load($fid)) {
      $scheme = \Drupal::service("file_system")->uriScheme($file->uri);

      // Validate local files.
      if ($scheme === 'public' || $scheme === 'private') {
        $file_validate_errors = file_validate($file, $element['#upload_validators']);
        if ($file_validate_errors) {
          form_error($element, implode('<br />', $file_validate_errors));
        }
      }
    }
  }


}
