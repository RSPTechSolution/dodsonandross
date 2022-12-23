<?php

namespace Drupal\commerce_license_test\Plugin\Commerce\LicenseType;

use Drupal\user\UserInterface;
use Drupal\commerce_license\ExistingRights\ExistingRightsResult;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;

/**
 * Reports whether it has been granted and has a rights check.
 *
 * @CommerceLicenseType(
 *   id = "state_change_with_rights",
 *   label = @Translation("State change with rights check test"),
 * )
 */
class StateChangeWithRightsCheck extends TestLicenseBase implements ExistingRightsFromConfigurationCheckingInterface {

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    $state = \Drupal::state();
    $state->set('commerce_license_state_change_test', 'grantLicense');
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
    $state = \Drupal::state();
    $state->set('commerce_license_state_change_test', 'revokeLicense');
  }

  /**
   * {@inheritdoc}
   */
  public function checkUserHasExistingRights(UserInterface $user) {
    $state = \Drupal::state();
    $license_status = $state->get('commerce_license_state_change_test');

    // If the license has been granted, report that the user has existing
    // rights.
    // This assumes that there is only one user involved in the test!
    return ExistingRightsResult::rightsExistIf(
      $license_status == 'grantLicense',
      $this->t("You already have the rights."),
      $this->t("The user already has the rights.")
    );
  }

}
