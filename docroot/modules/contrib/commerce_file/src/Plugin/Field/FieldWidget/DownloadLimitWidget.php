<?php

namespace Drupal\commerce_file\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of 'commerce_file_download_limit'.
 *
 * @FieldWidget(
 *   id = "commerce_file_download_limit",
 *   label = @Translation("Download limit"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class DownloadLimitWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = $items[$delta]->value ?? NULL;

    $checkbox_parents = array_merge($form['#parents'], [$this->fieldDefinition->getName(), 0, 'limit']);
    $checkbox_path = array_shift($checkbox_parents);
    $checkbox_path .= '[' . implode('][', $checkbox_parents) . ']';
    $element['limit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit the number of times a user can download a licensed file'),
      '#description' => $this->t('The value specified here overrides the globally configured download limit (Enter 0 for no limit).'),
      '#default_value' => (bool) $value,
    ];
    $element['value'] = [
      '#type' => 'number',
      '#title' => $element['#title'],
      '#title_display' => 'invisible',
      '#default_value' => $value ?: 100,
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="' . $checkbox_path . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $key => $value) {
      if (empty($value['limit'])) {
        continue;
      }
      $new_values['value'] = $value['value'];
    }
    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'commerce_license' && $field_definition->getName() === 'file_download_limit';
  }

}
