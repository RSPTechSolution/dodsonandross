<?php

namespace Drupal\Tests\commerce_license\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests that a role granted by a license can't be removed in the user form.
 *
 * @group commerce_license
 */
class RoleGrantedLockingTest extends CommerceBrowserTestBase {

  /**
   * A test license.
   *
   * @var \Drupal\commerce_license\Entity\LicenseInterface
   */
  protected $license;

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_license',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer users',
      'administer permissions',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $license_owner = $this->createUser();
    $role = $this->createEntity('user_role', [
      'id' => 'licensed_role',
      'label' => 'Licensed role',
    ]);

    // Create a license in the 'active' state.
    $license = $this->createEntity('commerce_license', [
      'type' => 'role',
      'state' => 'active',
      'product_variation' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'license_role' => $role,
    ]);
    $this->license = $this->reloadEntity($license);
  }

  /**
   * Tests a role granted by a license is locked on a user's account form.
   */
  public function testUserFormHasLock() {
    // Get the account for for the license owner user.
    $this->drupalGet("user/" . $this->license->getOwnerId() . "/edit");
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldDisabled("roles[licensed_role]");
    $this->assertSession()->pageTextContains("This role is granted by a license. It cannot be removed manually.");
    // Ensure that saving the form doesn't remove the granted role.
    $this->submitForm([], 'Save');
    $this->assertTrue($this->license->getOwner()->hasRole('licensed_role'));
  }

}
