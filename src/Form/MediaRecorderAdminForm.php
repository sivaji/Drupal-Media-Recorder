<?php

/**
 * @file
 * Contains \Drupal\media_recorder\Form\MediaRecorderAdminForm.
 */

namespace Drupal\media_recorder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class MediaRecorderAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_recorder_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('media_recorder.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['media_recorder.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $settings = media_recorder_get_settings();

    // Check that all libraries exist.
    $required_libraries = [
      'swfobject',
      'FlashWavRecorder',
      'Recorderjs',
    ];
    foreach ($required_libraries as $name) {
      $library = libraries_detect($name);
      if (!$library['installed']) {
        \Drupal::messenger()->addError($library['error message']);
      }
    }

    $form['media_recorder'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    // Recorder constraints.
    $form['media_recorder']['constraints'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => t('Media Constraints'),
      '#description' => t('Select which recording options will be available using the Media Browser.'),
    ];
    $form['media_recorder']['constraints']['audio'] = [
      '#type' => 'checkbox',
      '#title' => t('Audio'),
      '#default_value' => $settings['constraints']['audio'],
    ];
    $form['media_recorder']['constraints']['video'] = [
      '#type' => 'checkbox',
      '#title' => t('Video'),
      '#default_value' => $settings['constraints']['video'],
    ];
    $form['media_recorder']['constraints']['video_width'] = [
      '#type' => 'fieldset',
      '#title' => t('Width'),
      'min' => [
        '#type' => 'textfield',
        '#title' => t('Minimum Width'),
        '#default_value' => $settings['constraints']['video_width']['min'],
        '#element_validate' => [
          'element_validate_integer_positive'
          ],
      ],
      'ideal' => [
        '#type' => 'textfield',
        '#title' => t('Ideal Width'),
        '#default_value' => $settings['constraints']['video_width']['ideal'],
        '#element_validate' => [
          'element_validate_integer_positive'
          ],
      ],
      'max' => [
        '#type' => 'textfield',
        '#title' => t('Maximum Width'),
        '#default_value' => $settings['constraints']['video_width']['max'],
        '#element_validate' => [
          'element_validate_integer_positive'
          ],
      ],
    ];
    $form['media_recorder']['constraints']['video_height'] = [
      '#type' => 'fieldset',
      '#title' => t('Height'),
      'min' => [
        '#type' => 'textfield',
        '#title' => t('Minimum Height'),
        '#default_value' => $settings['constraints']['video_height']['min'],
        '#element_validate' => [
          'element_validate_integer_positive'
          ],
      ],
      'ideal' => [
        '#type' => 'textfield',
        '#title' => t('Ideal Height'),
        '#default_value' => $settings['constraints']['video_height']['ideal'],
        '#element_validate' => [
          'element_validate_integer_positive'
          ],
      ],
      'max' => [
        '#type' => 'textfield',
        '#title' => t('Maximum Height'),
        '#default_value' => $settings['constraints']['video_height']['max'],
        '#element_validate' => [
          'element_validate_integer_positive'
          ],
      ],
    ];

    // Recorder CSS enabled.
    $form['media_recorder']['css'] = [
      '#type' => 'checkbox',
      '#title' => t('Use default CSS stylesheet?'),
      '#description' => t('Disable if you want to use a different set of styles without having to override the default CSS.'),
      '#default_value' => $settings['css'],
    ];

    // Recorder time limit.
    $form['media_recorder']['time_limit'] = [
      '#type' => 'textfield',
      '#title' => t('Time Limit'),
      '#description' => t('Time limit in seconds. Defaults to 300 seconds (5 minutes).'),
      '#default_value' => $settings['time_limit'],
      '#element_validate' => [
        'element_validate_integer_positive'
        ],
      '#required' => TRUE,
    ];

    // Recorder allowed entensions.
    $form['media_recorder']['allowed_extensions'] = [
      '#type' => 'textfield',
      '#title' => t('Default allowed file extensions'),
      '#default_value' => $settings['allowed_extensions'],
      '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#maxlength' => NULL,
    ];

    // Recorder upload directory.
    $form['media_recorder']['upload_directory'] = [
      '#type' => 'textfield',
      '#title' => t("File directory for uploaded recordings"),
      '#default_value' => $settings['upload_directory'],
      '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
    ];

    // Recorder upload directory token support.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['media_recorder']['upload_directory']['#description'] .= t('This field also supports tokens.');
      $form['media_recorder']['upload_tokens'] = [
        '#theme' => 'token_tree',
        '#dialog' => TRUE,
      ];
    }

    // Media: Kaltura integration.
    if (\Drupal::moduleHandler()->moduleExists('media_kaltura')) {

      // Load existing servers and add to options array.
      $rows = [];
      $options = [];
      $servers = \Drupal::entityTypeManager()->getStorage('media_kaltura_server');
      foreach ($servers as $server) {
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $rows[$server->id] = array(
//         'domain' => $server->domain,
//         'force_ssl' => $server->force_ssl ? 'Yes' : 'No',
//         'api' => $server->api ? 'Enabled' : 'Disabled',
//         'partner_id' => $server->partner_id,
//         'subpartner_id' => $server->subpartner_id,
//         'user_id' => $server->user_id,
//         'secret' => $server->secret,
//         'uiconf_id' => $server->uiconf_id,
//         'operations' => l(t('edit'), 'admin/config/media/media_kaltura/server/' . $server->id),
//       );

        $options[$server->id] = $server->domain;
      }

      // Entry creation settings.
      $form['media_recorder']['kaltura'] = [
        '#type' => 'fieldset',
        '#title' => t('Media: Kaltura Integration'),
        'enable' => [
          '#type' => 'checkbox',
          '#title' => t('Enable Media: Kaltura integration?'),
          '#description' => t('Recordings will be sent directly to the specified Kaltura server rather than local storage.'),
          '#default_value' => $settings['kaltura']['enable'],
        ],
        'server' => [
          '#type' => 'select',
          '#title' => t('Default server to upload files to?'),
          '#description' => t('Files will be automatically uploaded to the selected Kaltura server.'),
          '#default_value' => $settings['kaltura']['server'],
          '#options' => $options,
          '#states' => [
            'disabled' => [
              ':input[name="media_recorder[kaltura][enable]"]' => [
                'checked' => FALSE
                ]
              ]
            ],
        ],
      ];

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
          $filter = new KalturaUiConfFilter([
            'objTypeEqual' => 7
            ]);
          $result = $kaltura['client']->uiConf->listAction($filter);
          $recorders = [];
          foreach ($result->objects as $object) {
            if ($object->swfUrlVersion >= '1.7') {
              $recorders[$object->id] = $object->name;
            }
          }

          // Recorder Kaltura recorder widget.
          if (count($recorders)) {
            $form['media_recorder']['kaltura']['recorder'] = [
              '#type' => 'select',
              '#title' => t('Select a Recorder Widget (Flash Fallback)'),
              '#description' => t('The kRecord widget must be version 1.7.2 or higher. Please note that these widgets are not normally available in the KMC and must be created through the Kaltura Client API.'),
              '#options' => $recorders,
              '#default_value' => isset($settings['kaltura']['recorder']) ? $settings['kaltura']['recorder'] : '',
              '#disabled' => !$settings['kaltura']['server'],
              '#states' => [
                'disabled' => [
                  ':input[name="media_recorder[kaltura][enable]"]' => [
                    'checked' => FALSE
                    ]
                  ]
                ],
            ];
          }
          else {
            $form['media_recorder']['kaltura']['markup'] = [
              '#markup' => '<div>No compatible recorders found, would you like to create one?</div>'
              ];
            $form['media_recorder']['kaltura']['create'] = [
              '#type' => 'submit',
              '#value' => 'Create a Recorder Widget',
              '#submit' => [
                'media_recorder_admin_form_create_recorder_widget_submit'
                ],
            ];
          }

          // Default transcoding profile.
          $filter = new KalturaConversionProfileFilter();
          $results = $kaltura['client']->conversionProfile->listAction($filter);
          $profiles = ['-- No Default --'];
          foreach ($results->objects as $result) {
            $profiles[$result->id] = $result->name;
          }
          $form['media_recorder']['kaltura']['profile'] = [
            '#type' => 'select',
            '#title' => t('Select a Default Transcoding Profile'),
            '#description' => t('All new media entries will automatically be transcoded using this transcoding profile.'),
            '#options' => $profiles,
            '#default_value' => isset($settings['kaltura']['profile']) ? $settings['kaltura']['profile'] : 0,
            '#states' => [
              'disabled' => [
                ':input[name="media_recorder[kaltura][enable]"]' => [
                  'checked' => FALSE
                  ]
                ]
              ],
          ];

          // Default category.
          $filter = new KalturaCategoryFilter();
          $results = $kaltura['client']->category->listAction($filter);
          $categories = ['-- No Default --'];
          foreach ($results->objects as $result) {
            $categories[$result->id] = $result->fullName;
          }
          $form['media_recorder']['kaltura']['category'] = [
            '#type' => 'select',
            '#title' => t('Select a Default Category'),
            '#description' => t('All new media entries will be placed within this default category.'),
            '#options' => $categories,
            '#default_value' => isset($settings['kaltura']['category']) ? $settings['kaltura']['category'] : 0,
            '#states' => [
              'disabled' => [
                ':input[name="media_recorder[kaltura][enable]"]' => [
                  'checked' => FALSE
                  ]
                ]
              ],
          ];
        }
        
          catch (Exception $e) {
          \Drupal::logger('media_kaltura')->error('There was a problem connecting to the kaltura server: @error', [
            '@error' => $e->getMessage()
            ]);
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['media_recorder', 'kaltura', 'enable'])) {
      $server = media_kaltura_server_load($form_state->getValue(['media_recorder', 'kaltura', 'server']));
      if (!$server) {
        $form_state->setErrorByName('media_recorder][kaltura][enable', t('Unable to load server. Please check that your server information is correct.'));
      }
      $kaltura = media_kaltura_start_session($server);
      if (!$kaltura) {
        $form_state->setErrorByName('media_recorder][kaltura][enable', t('Unable to connect to server. Please check that your server information is correct.'));
      }
    }
  }

}
?>
