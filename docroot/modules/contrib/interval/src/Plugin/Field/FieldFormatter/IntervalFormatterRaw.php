<?php

namespace Drupal\interval\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a default formatter class for interval fields.
 *
 * @FieldFormatter(
 *   id = "interval_raw",
 *   module = "interval",
 *   label = @Translation("Raw value"),
 *   field_types = {
 *     "interval"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class IntervalFormatterRaw extends IntervalFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => new HtmlEscapedText($this->formatInterval($item)),
      ];
    }
    return $element;
  }

}
