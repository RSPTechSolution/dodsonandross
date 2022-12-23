<?php

namespace Drupal\webp\Plugin\ImageAPIOptimizeProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\imageapi_optimize\ConfigurableImageAPIOptimizeProcessorBase;

/**
 * Saves a WebP copy of an image style derivative.
 *
 * @ImageAPIOptimizeProcessor(
 *   id = "webp_webp",
 *   label = @Translation("WebP"),
 *   description = @Translation("Saves a WebP copy of an image style derivative.")
 * )
 */
class Webp extends ConfigurableImageAPIOptimizeProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function applyToImage($image_uri) {
    /* @var \Drupal\webp\Webp $webp */
    $webp = \Drupal::service('webp.webp');
    $webp->createWebpCopy($image_uri, $this->configuration['quality']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'quality' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['quality'] = [
      '#type' => 'number',
      '#title' => $this->t('Image quality'),
      '#description' => $this->t('Specify the image quality.'),
      '#default_value' => $this->configuration['quality'],
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 100,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['quality'] = $form_state->getValue('quality');
  }

}
