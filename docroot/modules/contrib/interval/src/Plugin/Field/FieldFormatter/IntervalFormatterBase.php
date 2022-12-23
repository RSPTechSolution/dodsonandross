<?php

namespace Drupal\interval\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\interval\IntervalItemInterface;

/**
 * Provides a base formatter class for interval field formatters.
 */
abstract class IntervalFormatterBase extends FormatterBase {

  /**
   * Formats an interval as a string.
   *
   * @param \Drupal\interval\IntervalItemInterface $item
   *   Interval item to format.
   *
   * @return string
   *   Formatted interval.
   */
  protected function formatInterval(IntervalItemInterface $item) {
    $interval = $item->getIntervalPlugin();
    return $this->formatPlural(
      $item->getInterval(), '1 @singular', '@count @plural',
      [
        '@singular' => $interval['singular'],
        '@plural' => $interval['plural'],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#type' => 'html_tag',
        '#attributes' => ['class' => ['interval-value']],
        '#tag' => 'div',
        '#value' => $this->formatInterval($item),
      ];
    }
    return $element;
  }

}
