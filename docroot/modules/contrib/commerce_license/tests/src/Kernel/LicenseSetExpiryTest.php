<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests that a license gets its expiry date set when activated.
 *
 * @group commerce_license
 */
class LicenseSetExpiryTest extends OrderKernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_license',
    'commerce_license_test',
    'commerce_license_set_expiry_test',
    'interval',
    'recurring_period',
  ];

  /**
   * The license storage.
   *
   * @var \Drupal\commerce_license\LicenseStorageInterface
   */
  protected $licenseStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_license');

    $this->licenseStorage = \Drupal::service('entity_type.manager')->getStorage('commerce_license');
  }

  /**
   * Tests a license has its expiry date set from the expiry plugin.
   */
  public function testLicenseSetExpiry() {
    $owner = $this->createUser();

    // Create a license in the 'new' state, without an expiration timestamp.
    $license = $this->licenseStorage->create([
      'type' => 'simple',
      'state' => 'new',
      'product_variation' => 1,
      'uid' => $owner->id(),
      // Use our test expiration plugin.
      'expiration_type' => [
        'target_plugin_id' => 'commerce_license_set_expiry_test',
        'target_plugin_configuration' => [],
      ],
    ]);

    $license->save();

    // Activate the license: this puts it into the 'pending' state.
    $transition = $license->getState()->getWorkflow()->getTransition('activate');
    $license->getState()->applyTransition($transition);
    $license->save();

    // Check the expiration timestamp is not yet set.
    $this->assertEqual($license->expires->value, 0);

    // Confirm the license: this puts it into the 'active' state.
    $transition = $license->getState()->getWorkflow()->getTransition('confirm');
    $license->getState()->applyTransition($transition);
    $license->save();

    // Check the expiration timestamp is now set.
    $this->assertEqual($license->expires->value, 12345);
  }

}
