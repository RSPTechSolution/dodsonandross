<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Create a role and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneRoleTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'user'];

  /**
   * Theme to enable by default
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer permissions',
    'clone user_role entity',
  ];

  /**
   * An administrative user with permission to configure roles settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test role entity clone.
   */
  public function testRoleEntityClone() {
    $edit = [
      'label' => 'Test role for clone',
      'id' => 'test_role_for_clone',
    ];
    $this->drupalPostForm("/admin/people/roles/add", $edit, t('Save'));

    $roles = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $role = reset($roles);

    $edit = [
      'id' => 'test_role_cloned',
      'label' => 'Test role cloned',
    ];
    $this->drupalPostForm('entity_clone/user_role/' . $role->id(), $edit, t('Clone'));

    $roles = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $role = reset($roles);
    $this->assertInstanceOf(Role::class, $role, 'Test role cloned found in database.');
  }

}
