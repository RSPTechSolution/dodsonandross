<?php

namespace Drupal\commerce_file\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides an entity trait for Commerce Product Variation entities.
 *
 * Product variations that sell a file must use this trait. This adds a field
 * to the product variation type for storing the digital file(s) that can be
 * downloaded when the product is purchased.
 *
 * @CommerceEntityTrait(
 *  id = "commerce_file",
 *  label = @Translation("Provides a file for download"),
 *  entity_types = {"commerce_product_variation"}
 * )
 */
class ProductVariationCommerceFile extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    // Builds the field definitions.
    $fields = [];
    $fields['commerce_file'] = BundleFieldDefinition::create('file')
      ->setLabel($this->t('File(s)'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setSettings([
        'file_extensions' => 'mp4 m4v flv wmv mp3 wav jpg jpeg png pdf doc docx ppt pptx xls xlsx',
        'description_field' => 1,
        'uri_scheme' => 'private',
      ])
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
      ]);

    return $fields;
  }

}
