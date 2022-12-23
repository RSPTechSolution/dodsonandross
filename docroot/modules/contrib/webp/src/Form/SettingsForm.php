<?php

namespace Drupal\webp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webp.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webp.settings');
    $form['quality'] = [
      '#type' => 'number',
      '#title' => $this->t('Image quality'),
      '#description' => $this->t('Specify the image quality. This setting
       will be in effect for all new image style derivatives. In order to apply
       this setting to existing image style derivatives, flush image styles
       through the interface, or by using Drush or Drupal Console.'),
      '#default_value' => $config->get('quality'),
      '#min' => 1,
      '#max' => 100,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('webp.settings')
      ->set('quality', (int) $form_state->getValue('quality'))
      ->save();
  }

}
