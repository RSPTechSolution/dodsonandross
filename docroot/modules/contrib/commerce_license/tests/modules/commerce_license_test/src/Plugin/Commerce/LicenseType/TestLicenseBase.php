<?php

namespace Drupal\commerce_license_test\Plugin\Commerce\LicenseType;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeBase;

/**
 * Base class for test license types: implements the needed methods.
 */
class TestLicenseBase extends LicenseTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    return 'test license';
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
  }

}
