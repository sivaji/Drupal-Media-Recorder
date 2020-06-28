<?php

/**
 * @file
 * Contains \Drupal\media_recorder\Form\MediaRecorderAdd.
 */

namespace Drupal\media_recorder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class MediaRecorderAdd extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_recorder_add';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $types = NULL, $multiselect = NULL, $media_browser = NULL) {

    // Add media recorder element.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#required' => TRUE,
    ];

    // Add media recorder element.
    $form['media_recorder'] = element_info('media_recorder');
    $form['media_recorder'] += [
      '#title' => t('Record'),
      '#required' => TRUE,
    ];

    // Use ajax submit handler if this is the media browser.
    if ($media_browser) {
      $form['#prefix'] = '<div id="media-recorder-ajax-wrapper">';
      $form['#suffix'] = '</div>';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save Recording'),
        '#ajax' => [
          'callback' => 'media_recorder_add_submit_ajax_callback'
          ],
        '#submit' => ['media_recorder_add_submit'],
      ];
    }

      // Otherwise use regular submit handler.
    else {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save Recording'),
        '#submit' => [
          'media_recorder_add_submit'
          ],
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Process file.
    if (is_numeric($form_state->getValue(['media_recorder', 'fid'])) && $file = file_load($form_state->getValue(['media_recorder', 'fid']))) {
      if (file_prepare_directory($form['media_recorder']['#upload_location'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        $file->filename = trim($form_state->getValue(['title']));
        $file->status = FILE_STATUS_PERMANENT;
        file_save($file);
        // @FIXME
        // l() expects a Url object, created from a route name or external URI.
        // drupal_set_message(t('The file <em>!filename</em> was successfully saved.', array('!filename' => l(\Drupal\Component\Utility\Html::escape($file->filename), 'file/' . $file->fid))), 'status');

      }
    }
    // Otherwise return an error.
    else {
      \Drupal::messenger()->addError(t('An unrecoverable error occurred. Try reloading the page and submitting again.'));
    }
    return $form;
  }

}
?>
