<?php

/**
 * @file
 * Contains \Drupal\media_recorder\Plugin\Field\FieldWidget\MediaRecorder.
 */

namespace Drupal\media_recorder\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'media_recorder' widget.
 *
 * @FieldWidget(
 *   id = "media_recorder",
 *   module = "media_recorder",
 *   label = @Translation("Media Recorder"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class MediaRecorder extends WidgetBase {

  /**
   * @FIXME
   * Move all logic relating to the media_recorder widget into this class.
   * For more information, see:
   *
   * https://www.drupal.org/node/1796000
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetInterface.php/interface/WidgetInterface/8
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetBase.php/class/WidgetBase/8
   */

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'time_limit' => 300,
      'constraints' => array(
        'audio' => TRUE,
        'video' => TRUE,
        'video_width' => array(
          'min' => 640,
          'ideal' => 1280,
          'max' => 1920,
        ),
        'video_height' => array(
          'min' => 480,
          'ideal' => 720,
          'max' => 1080,
        ),
      ),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['time_limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Time Limit'),
      '#description' => t('Time limit in seconds. Defaults to 300 seconds (5 minutes).'),
      '#default_value' => $this->getSetting('time_limit'),
      '#element_validate' => array('element_validate_integer_positive'),
      '#required' => TRUE,
    );
    $settings['constraints'] = $this->getSetting('constraints');
    $element['constraints'] = array(
      '#type' => 'fieldset',
      '#title' => t('Media Constraints'),
      '#description' => t('Select which recording options will be available.'),
    );
    $element['constraints']['audio'] = array(
      '#type' => 'checkbox',
      '#title' => t('Audio'),
      '#default_value' => $settings['constraints']['audio'],
    );
    $element['constraints']['video'] = array(
      '#type' => 'checkbox',
      '#title' => t('Video'),
      '#default_value' => $settings['constraints']['video'],
    );
    $element['constraints']['video'] = array(
      '#type' => 'checkbox',
      '#title' => t('Video'),
      '#default_value' => $settings['constraints']['video'],
    );
    $element['constraints']['video_width'] = array(
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
    $element['constraints']['video_height'] = array(
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
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = media_recorder_get_settings();
    $element += array(
      '#type' => 'media_recorder',
      // FIXME
      '#value_callback' => 'media_recorder_widget_value',
      '#process' => array_merge($element['#process'], array('media_recorder_widget_process')),
      '#time_limit' => $this->getSetting('time_limit'),
      '#constraints' => $this->getSetting('time_limit'),
      '#extended' => TRUE,
      // '#upload_location' => file_field_widget_uri($field, $instance),
      // '#upload_validators' => file_field_widget_upload_validators($field, $instance),
      '#upload_location' => 'public://' . $settings['upload_directory'],
      '#upload_validators' => [
        'file_validate_extensions' => [$settings['allowed_extensions']],
      ],
    );
    return $element;
  }
}