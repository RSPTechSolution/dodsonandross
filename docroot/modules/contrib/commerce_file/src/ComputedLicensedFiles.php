<?php

namespace Drupal\commerce_file;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Provides a computed field returning the licensed files.
 */
final class ComputedLicensedFiles extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $license = $this->getEntity();
    assert($license instanceof LicenseInterface);
    if ($license->bundle() !== 'commerce_file') {
      return;
    }
    $purchased_entity = $license->getPurchasedEntity();
    if (!$purchased_entity || $purchased_entity->get('commerce_file')->isEmpty()) {
      return;
    }

    foreach ($purchased_entity->get('commerce_file') as $delta => $item) {
      $this->list[$delta] = $item;
    }
  }

}
