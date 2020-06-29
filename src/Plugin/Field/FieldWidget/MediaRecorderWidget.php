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
    // dpm(__METHOD__);
    $settings = media_recorder_get_settings();
    $field_settings = $this->getFieldSettings();
    // print_r($field_settings);exit;
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $defaults = array(
      'fids' => 0,
      // 'display' => !empty($field['settings']['display_default']),
      'display' => 1,
      'description' => '',
    );
    $element_info = $this->elementInfo->getInfo('media_recorder');
    // print_r($element_info);exit;
    $element += [
      '#type' => 'media_recorder',
      // '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      // '#value_callback' => 'media_recorder_widget_value',
      // '#process' => array_merge($element['#process'], array('media_recorder_widget_process')),
      '#value_callback' => [get_class($this), 'media_recorder_widget_value'],
      '#process' => array_merge($element_info['#process'], [[get_class($this), 'media_recorder_widget_process']]),
      '#time_limit' => $this->getSetting('time_limit'),
      '#constraints' => $this->getSetting('time_limit'),
      '#extended' => TRUE,
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      // '#display_field' => (bool) $field_settings['display_field'],
      // '#display_default' => $field_settings['display_default'],
      '#description_field' => $field_settings['description_field'],
      // '#upload_location' => file_field_widget_uri($field, $instance),
      // '#upload_validators' => file_field_widget_upload_validators($field, $instance),
      '#upload_location' => $items[$delta]->getUploadLocation(),
      '#upload_validators' => $items[$delta]->getUploadValidators(),
    ];

    $element['#weight'] = $delta;

    // Field stores FID value in a single mode, so we need to transform it for
    // form element to recognize it correctly.
    // dpm($items[$delta]->getvalues());
    if (!isset($items[$delta]->fids) && isset($items[$delta]->target_id)) {
      $items[$delta]->fids = $items[$delta]->target_id;
    }
    // dpm($items[$delta]->getValue());
    // $element['#default_value'] = !empty($items) ? $items[$delta] : $defaults;
    $element['#default_value'] = $items[$delta]->getValue() + $defaults;

    $element['fids'] = $element['#default_value']['target_id'];
    // dpm($form_state->getValues());
    if (empty($element['#default_value']['fid'])) {
    //   $element['#description'] = theme('media_upload_help', array('description' => $element['#description']));
    }

    // FIXME
    // $element['fids'] = 64;
    // $element['#default_value']['fids'] = 64;
    // FIXME

    // $elements = array($element);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // die("good is well");
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
    // dpm($new_values);
    return $new_values;
  }


  /**
   * An element #process callback for the media_recorder field type.
   *
   * @see media_recorder_field_widget_form()
   */
  // function media_recorder_widget_process($element, &$form_state, $form) {
  public function media_recorder_widget_process($element, FormStateInterface $form_state, $form) {
    // dpm(__METHOD__);

    $item = $element['#value'];
    // Populate the hidden field with file id.
    $element['fids']['#value'] = $element['#value']['fids'];

    // $item['fids'] = $element['#value'];
    // dpm($element);
    // $field = field_widget_field($element, $form_state);
    // $instance = field_widget_instance($element, $form_state);

    // Add the display field if enabled.
    // if (!empty($field['settings']['display_field'])) {
    if ($element['#display_field']) {
      $element['display'] = array(
        '#type' => 'checkbox',
        '#title' => t('Include file in display'),
        '#value' => isset($item['display']) ? $item['display'] : $element['#default_value']['display'],
        '#attributes' => array('class' => array('file-display')),
      );
    }
    else {
      $element['display'] = array(
        '#type' => 'hidden',
        '#value' => '1',
      );
    }


    $element['#submit'][] = [['MediaRecorderWidget', 'submit']];
    // $element[$key]['#limit_validation_errors'] = [array_slice($element['#parents'], 0, -1)];



    // Add the description field if enabled.
    // if (!empty($instance['settings']['description_field'])) {
    if ($element['#description_field']) {
      // @FIXME
  // // @FIXME
  // // This looks like another module's variable. You'll need to rewrite this call
  // // to ensure that it uses the correct configuration object.
  // $element['description'] = array(
  //       '#type' => variable_get('file_description_type', 'textfield'),
  //       '#title' => t('Description'),
  //       '#value' => isset($item['description']) ? $item['description'] : '',
  //       '#maxlength' => variable_get('file_description_length', 128),
  //       '#description' => t('The description may be used as the label of the link to the file.'),
  //     );
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
  // function media_recorder_widget_value($element, $input = FALSE, $form_state) {
  function media_recorder_widget_value($element, $input, FormStateInterface $form_state) {
    // return $input;
    // dpm(__METHOD__);
    if ($input) {
      // Checkboxes lose their value when empty.
      // If the display field is present make sure its unchecked value is saved.
      // $field = field_widget_field($element, $form_state);
      if (empty($input['display'])) {
        // $input['display'] = $field['settings']['display_field'] ? 0 : 1;
        $input['display'] = $element['#display_field'] ? 0 : 1;
      }
    }

    // We depend on the media_recorder element to handle uploads.
    // $return = media_recorder_element_value($element, $input, $form_state);
    $return = MediaRecorder::media_recorder_element_value($element, $input, $form_state);
    // $return['fids'] = isset($return['target_id'])? $return['target_id'] : 0;
    // dpm($return);
    // Ensure that all the required properties are returned even if empty.
    $return += [
      'fids' => 0,
      'display' => 1,
      'description' => '',
    ];

    return $return;
  }

  /**
   * Form submission handler for upload/remove button of formElement().
   *
   * This runs in addition to and after file_managed_file_submit().
   *
   * @see file_managed_file_submit()
   */
  public static function submit($form, FormStateInterface $form_state) {
    // dpm(__METHOD__);
    // During the form rebuild, formElement() will create field item widget
    // elements using re-indexed deltas, so clear out FormState::$input to
    // avoid a mismatch between old and new deltas. The rebuilt elements will
    // have #default_value set appropriately for the current state of the field,
    // so nothing is lost in doing this.
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -2);
    NestedArray::setValue($form_state->getUserInput(), $parents, NULL);

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $submitted_values = NestedArray::getValue($form_state->getValues(), array_slice($button['#parents'], 0, -2));
    foreach ($submitted_values as $delta => $submitted_value) {
      if (empty($submitted_value['fids'])) {
        unset($submitted_values[$delta]);
      }
    }

    // If there are more files uploaded via the same widget, we have to separate
    // them, as we display each file in its own widget.
    $new_values = [];
    foreach ($submitted_values as $delta => $submitted_value) {
      if (is_array($submitted_value['fids'])) {
        foreach ($submitted_value['fids'] as $fid) {
          $new_value = $submitted_value;
          $new_value['fids'] = [$fid];
          $new_values[] = $new_value;
        }
      }
      else {
        $new_value = $submitted_value;
      }
    }

    // Re-index deltas after removing empty items.
    $submitted_values = array_values($new_values);

    // Update form_state values.
    NestedArray::setValue($form_state->getValues(), array_slice($button['#parents'], 0, -2), $submitted_values);

    // Update items.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items'] = $submitted_values;
    // dpm($field_state);
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
  }

}