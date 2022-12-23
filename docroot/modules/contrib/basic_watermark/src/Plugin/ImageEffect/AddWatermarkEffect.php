<?php

namespace Drupal\basic_watermark\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;

/**
 * Converts an image resource.
 *
 * @ImageEffect(
 *   id = "add_watermark",
 *   label = @Translation("Add Watermark"),
 *   description = @Translation("Adds watermark to the image")
 * )
 */
class AddWatermarkEffect extends ConfigurableImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $result = $image->apply('add_watermark', $this->configuration);
    if (!$result) {
      return FALSE;
    };

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'watermark_path' => NULL,
      'apply_type' => 'once',
      'position' => 'center-center',
      'margins' => [
        'left' => 0,
        'top' => 0,
        'right' => 0,
        'bottom' => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary['watermark_path'] = [
      '#type' => 'item',
      '#markup' => $this->t("Watermark path: @path", [
        '@path' => $this->configuration['watermark_path'],
      ]),
    ];
    $summary['apply_type'] = [
      '#type' => 'item',
      '#markup' => $this->t("Apply type: @path", [
        '@path' => $this->getApplyTypeOptions()[$this->configuration['apply_type']],
      ]),
    ];
    $summary['position'] = [
      '#type' => 'item',
      '#markup' => $this->t("Position: @path", [
        '@path' => $this->getPositionOptions()[$this->configuration['position']],
      ]),
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * The watermark apply types.
   *
   * @return array
   *   An array with the options.
   */
  public function getApplyTypeOptions() {
    return [
      'once' => $this->t('Once'),
      'repeat' => $this->t('Repeat'),
    ];
  }

  /**
   * The watermark position options.
   *
   * @return array
   *   An array with the options.
   */
  public function getPositionOptions() {
    return [
      'left-top' => $this->t('Top left'),
      'center-top' => $this->t('Top center'),
      'right-top' => $this->t('Top right'),
      'left-center' => $this->t('Center left'),
      'center-center' => $this->t('Center'),
      'right-center' => $this->t('Center right'),
      'left-bottom' => $this->t('Bottom left'),
      'center-bottom' => $this->t('Bottom center'),
      'right-bottom' => $this->t('Bottom right'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['watermark_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Watermark path'),
      '#description' => $this->t('Example: /sites/default/files/watermark.png, The image must be in png format and the path must be insite drupal root.'),
      '#default_value' => $this->configuration['watermark_path'],
      '#required' => TRUE,
    ];

    $form['apply_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Apply type'),
      '#description' => $this->t('<ul>
        <li><label>Once:</label> Add the watermark once.</li>
        <li><label>Repeat:</label> Repeat the watermark from top left until it covers the the whole image.</li>
        </ul>
      '),
      '#options' => $this->getApplyTypeOptions(),
      '#default_value' => $this->configuration['apply_type'],
    ];

    $form['position_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          'select[name="data[apply_type]"' => ['value' => 'once'],
        ],
      ],
      'position' => [
        '#theme' => 'image_anchor',
        '#type' => 'radios',
        '#title' => $this->t('Position'),
        '#options' => $this->getPositionOptions(),
        '#default_value' => $this->configuration['position'],
        '#description' => $this->t('Watermark position'),
      ],
    ];

    $form['margins'] = [
      '#type' => 'details',
      '#title' => $this->t('Watermark margins'),
      '#description' => $this->t('Empty area to keep around the watermark in pixels.'),
      'left' => [
        '#title' => $this->t('Margin left'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['margins']['left'],
        '#required' => TRUE,
      ],
      'top' => [
        '#title' => $this->t('Margin top'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['margins']['top'],
        '#required' => TRUE,
      ],
      'right' => [
        '#title' => $this->t('Margin right'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['margins']['right'],
        '#required' => TRUE,
      ],
      'bottom' => [
        '#title' => $this->t('Margin bottom'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['margins']['bottom'],
        '#required' => TRUE,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $path = DRUPAL_ROOT . $form_state->getValue('watermark_path');

    if (!file_exists($path)) {
      $form_state->setError($form['watermark_path'], $this->t('File does not exist.'));
    }
    else {
      $image_details = getimagesize($path);
      if (!$image_details || $image_details['mime'] != 'image/png') {
        $form_state->setError($form['watermark_path'], $this->t('File not a png.'));
      }
    }

    $margins = $form_state->getValue('margins');
    foreach ($margins as $field => $margin) {
      if ($margin !== '' && (!is_numeric($margin) || intval($margin) != $margin || $margin < 0)) {
        $form_state->setError($form['margins'][$field], $this->t('%name must be a non negative integer.', [
          '%name' => $form['margins'][$field]['#title'],
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['watermark_path'] = $form_state->getValue('watermark_path');
    $this->configuration['apply_type'] = $form_state->getValue('apply_type');
    $this->configuration['position'] = $form_state->getValue('position_wrapper')['position'];

    $this->configuration['margins'] = $form_state->getValue('margins');

    if ($this->configuration['apply_type'] == 'repeat') {
      $this->configuration['position'] = 'left-top';
    }

  }

}
