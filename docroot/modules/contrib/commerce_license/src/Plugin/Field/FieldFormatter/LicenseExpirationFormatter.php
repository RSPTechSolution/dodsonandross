<?php

namespace Drupal\commerce_license\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;

/**
 * Plugin implementation of the 'commerce_license_expiration' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_license_expiration",
 *   label = @Translation("License expiration"),
 *   field_types = {
 *     "timestamp",
 *   }
 * )
 */
class LicenseExpirationFormatter extends TimestampFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      if ((int) $item->value === 0) {
        $elements[$delta] = ['#markup' => $this->t('Never')];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'commerce_license' && $field_definition->getName() === 'expires';
  }

}
