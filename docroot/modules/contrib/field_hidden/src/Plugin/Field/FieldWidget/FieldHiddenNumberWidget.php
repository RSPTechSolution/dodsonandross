<?php
/**
 * @file
 * Contains \Drupal\field_hidden\Plugin\Field\FieldWidget\FieldHiddenNumberWidget.
 */

namespace Drupal\field_hidden\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_hidden_number' widget.
 *
 * Adds HTML input[type='hidden'] widget support for all number types.
 *
 * There currently exists no Number module, therefore we extend Field module's
 * number widget directly.
 *
 * @FieldWidget(
 *   id = "field_hidden_number",
 *   label = @Translation("Hidden field"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class FieldHiddenNumberWidget extends NumberWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Evading 'element' ambiguity:
    // The field 'element' returned by parent NumberWidget::formElement()
    // is effectively a 'row'. Whereas the 'element' returned by some other
    // formElement() implementations (like TextfieldWidget's) is a (the) column
    // (sic!) of a row.
    // For single-columned types it may seem obsolete to stress the actual
    // hierarchical nature of an 'element', whereas for multi-columned field
    // types (like File) it makes things far more transparent.
    $row = parent::formElement($items, $delta, $element, $form, $form_state);

    // Make the 'value' column's HTML element hidden, except when appearing as
    // default value in a field instance settings form.
    if ($form_state->getFormObject()->getFormId() != 'field_ui_field_edit_form') {
      $column_value =& $row['value'];

      $column_value['#type'] = 'hidden';

      // Add Field Hidden CSS selector to column - may well prove useful,
      // particularly for multi-row instances.
      $column_value['#attributes']['class'][] = 'field-hidden-' . $this->fieldDefinition->getType();

      // Add styles that hide multi-row instances.
      $column_value['#attached']['library'][] = 'field_hidden/drupal.field_hidden';
    }

    return $row;
  }

}
