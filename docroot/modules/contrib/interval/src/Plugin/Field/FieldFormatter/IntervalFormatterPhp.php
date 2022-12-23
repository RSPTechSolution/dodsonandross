<?php

namespace Drupal\interval\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a default formatter class for interval fields.
 *
 * @FieldFormatter(
 *   id = "interval_php",
 *   module = "interval",
 *   label = @Translation("PHP date/time"),
 *   field_types = {
 *     "interval"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class IntervalFormatterPhp extends IntervalFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\interval\IntervalItemInterface $item */
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => new HtmlEscapedText($item->buildPHPString()),
      ];
    }
    return $element;
  }

}
