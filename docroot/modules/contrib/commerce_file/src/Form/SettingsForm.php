<?php

namespace Drupal\commerce_file\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the Commerce file settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_file_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('commerce_file.settings');

    $config->set('enable_download_limit', $form_state->getValue('enable_download_limit'))
      ->set('download_limit', $form_state->getValue('download_limit'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_file.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $help = $this->t('These are the default download and IP address limits for file downloads.');
    $help .= $this->t('These limits can be overridden per product variation as needed.');
    $form['help'] = [
      '#markup' => $help,
    ];
    $form['download_limits'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Download limits'),
    ];

    // Download limit.
    $form['download_limits']['enable_download_limit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit the number of times a user can download a licensed file'),
      '#default_value' => $this->config('commerce_file.settings')->get('enable_download_limit'),
    ];
    $form['download_limits']['download_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter limit'),
      '#default_value' => $this->config('commerce_file.settings')->get('download_limit'),
      '#states' => [
        'invisible' => [
          ':input[name="enable_download_limit"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    return $form;
  }

}
