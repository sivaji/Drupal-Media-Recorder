<?php

/**
 * @file
 * Contains \Drupal\media_recorder\Plugin\Field\FieldWidget\MediaRecorderWidget.
 */

namespace Drupal\media_recorder\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\media_recorder\Element\MediaRecorder;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class MediaRecorderWidget extends WidgetBase  implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->elementInfo = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'time_limit' => 300,
      'constraints' => [
        'audio' => TRUE,
        'video' => TRUE,
        'video_width' => [
          'min' => 640,
          'ideal' => 1280,
          'max' => 1920,
        ],
        'video_height' => [
          'min' => 480,
          'ideal' => 720,
          'max' => 1080,
        ],
      ],
     ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['time_limit'] = [
      '#type' => 'textfield',
      '#title' => t('Time Limit'),
      '#description' => t('Time limit in seconds. Defaults to 300 seconds (5 minutes).'),
      '#default_value' => $this->getSetting('time_limit'),
      '#element_validate' => ['element_validate_integer_positive'],
      '#required' => TRUE,
    ];
    $settings['constraints'] = $this->getSetting('constraints');
    $element['constraints'] = [
      '#type' => 'fieldset',
      '#title' => t('Media Constraints'),
      '#description' => t('Select which recording options will be available.'),
    ];
    $element['constraints']['audio'] = [
      '#type' => 'checkbox',
      '#title' => t('Audio'),
      '#default_value' => $settings['constraints']['audio'],
    ];
    $element['constraints']['video'] = [
      '#type' => 'checkbox',
      '#title' => t('Video'),
      '#default_value' => $settings['constraints']['video'],
    ];
    $element['constraints']['video'] = [
      '#type' => 'checkbox',
      '#title' => t('Video'),
      '#default_value' => $settings['constraints']['video'],
    ];
    $element['constraints']['video_width'] = [
      '#type' => 'fieldset',
      '#title' => t('Width'),
      'min' => [
        '#type' => 'textfield',
        '#title' => t('Minimum Width'),
        '#default_value' => $settings['constraints']['video_width']['min'],
        '#element_validate' => ['element_validate_integer_positive'],
      ],
      'ideal' => [
        '#type' => 'textfield',
        '#title' => t('Ideal Width'),
        '#default_value' => $settings['constraints']['video_width']['ideal'],
        '#element_validate' => ['element_validate_integer_positive'],
      ],
      'max' => [
        '#type' => 'textfield',
        '#title' => t('Maximum Width'),
        '#default_value' => $settings['constraints']['video_width']['max'],
        '#element_validate' => ['element_validate_integer_positive'],
      ],
    ];
    $element['constraints']['video_height'] = [
      '#type' => 'fieldset',
      '#title' => t('Height'),
      'min' => [
        '#type' => 'textfield',
        '#title' => t('Minimum Height'),
        '#default_value' => $settings['constraints']['video_height']['min'],
        '#element_validate' => ['element_validate_integer_positive'],
      ],
      'ideal' => [
        '#type' => 'textfield',
        '#title' => t('Ideal Height'),
        '#default_value' => $settings['constraints']['video_height']['ideal'],
        '#element_validate' => ['element_validate_integer_positive'],
      ],
      'max' => [
        '#type' => 'textfield',
        '#title' => t('Maximum Height'),
        '#default_value' => $settings['constraints']['video_height']['max'],
        '#element_validate' => ['element_validate_integer_positive'],
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = media_recorder_get_settings();
    $field_settings = $this->getFieldSettings();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $defaults = [
      'fids' => 0,
      'display' => 1,
      'description' => '',
    ];
    $element_info = $this->elementInfo->getInfo('media_recorder');
    $element += [
      '#type' => 'media_recorder',
      '#value_callback' => [get_class($this), 'widgetValue'],
      '#process' => array_merge($element_info['#process'], [[get_class($this), 'widgetProcess']]),
      '#time_limit' => $this->getSetting('time_limit'),
      '#constraints' => $this->getSetting('time_limit'),
      '#extended' => TRUE,
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      '#description_field' => $field_settings['description_field'],
      '#upload_location' => $items[$delta]->getUploadLocation(),
      '#upload_validators' => $items[$delta]->getUploadValidators(),
    ];

    $element['#weight'] = $delta;

    // Field stores FID value in a single mode, so we need to transform it for
    // form element to recognize it correctly.
    if (!isset($items[$delta]->fids) && isset($items[$delta]->target_id)) {
      $items[$delta]->fids = $items[$delta]->target_id;
    }
    $element['#default_value'] = $items[$delta]->getValue() + $defaults;
    $element['fids'] = $element['#default_value']['target_id'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Since file upload widget now supports uploads of more than one file at a
    // time it always returns an array of fids. We have to translate this to a
    // single fid, as field expects single value.
    $new_values = [];
    foreach ($values as &$value) {
      $new_value = $value;
      $new_value['target_id'] = $value['fids'];
      unset($new_value['fids']);
      $new_values[] = $new_value;
    }
    return $new_values;
  }


  /**
   * An element #process callback for the media_recorder field type.
   *
   * @see media_recorder_field_widget_form()
   */
  public function widgetProcess($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];
    // Populate the hidden field with file id.
    $element['fids']['#value'] = $element['#value']['fids'];

    // Add the display field if enabled.
    if ($element['#display_field']) {
      $element['display'] = [
        '#type' => 'checkbox',
        '#title' => t('Include file in display'),
        '#value' => isset($item['display']) ? $item['display'] : $element['#default_value']['display'],
        '#attributes' => ['class' => ['file-display']],
      ];
    }
    else {
      $element['display'] = [
        '#type' => 'hidden',
        '#value' => '1',
      ];
    }

    // Add the description field if enabled.
    if ($element['#description_field']) {
      $config = \Drupal::config('file.settings');
      $element['description'] = [
        '#type' => $config->get('description.type'),
        '#title' => t('Description'),
        '#value' => isset($item['description']) ? $item['description'] : '',
        '#maxlength' => $config->get('description.length'),
        '#description' => t('The description may be used as the label of the link to the file.'),
      ];
    }
    return $element;
  }

  /**
   * The #value_callback for the media_recorder element.
   *
   * @see media_recorder_field_widget_form()
   */
  function widgetValue($element, $input, FormStateInterface $form_state) {
    if ($input) {
      // Checkboxes lose their value when empty.
      // If the display field is present make sure its unchecked value is saved.
      if (empty($input['display'])) {
        $input['display'] = $element['#display_field'] ? 0 : 1;
      }
    }

    // We depend on the media_recorder element to handle uploads.
    // $return = media_recorder_element_value($element, $input, $form_state);
    $return = MediaRecorder::media_recorder_element_value($element, $input, $form_state);

    // Ensure that all the required properties are returned even if empty.
    $return += [
      'fids' => 0,
      'display' => 1,
      'description' => '',
    ];
    return $return;
  }

}
