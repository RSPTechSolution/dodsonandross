<?php

namespace Drupal\commerce_license_test\Plugin\Commerce\LicenseType;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * License type for testing a field can be set when granting and revoking.
 *
 * @CommerceLicenseType(
 *   id = "with_field",
 *   label = @Translation("License with field"),
 * )
 */
class LicenseWithField extends TestLicenseBase {

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    // Set the value on our field.
    $license->set('test_field', 'granted');
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
    // Set the value on our field.
    $license->set('test_field', 'revoked');
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['test_field'] = BundleFieldDefinition::create('text')
      ->setLabel(t('Test field'))
      ->setDescription(t('A test field that this plugin will set values on.'))
      ->setCardinality(1);

    return $fields;
  }

}
