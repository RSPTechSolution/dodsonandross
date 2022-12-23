<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests deleting a license revokes it.
 *
 * @group commerce_license
 */
class LicenseDeletionTest extends OrderKernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'interval',
    'recurring_period',
    'commerce_license',
    'commerce_license_test',
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
   * Tests that exceptions thrown by workers are handled properly.
   */
  public function testLicenseDeletion() {
    $owner = $this->createUser();

    // Create a license in the 'active' state.
    $license = $this->licenseStorage->create([
      'type' => 'state_change_test',
      'state' => 'active',
      'product_variation' => 1,
      'uid' => $owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);

    $license->save();

    // Ensure the test tracking state is clear.
    \Drupal::state()->set('commerce_license_state_change_test', NULL);

    $license->delete();

    // Deleting the license should cause the plugin to revoke the rights.
    $this->assertEqual(\Drupal::state()->get('commerce_license_state_change_test'), 'revokeLicense', "The plugin's revokeLicense() method was called.");
  }

}
