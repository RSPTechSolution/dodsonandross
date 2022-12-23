<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;

/**
 * Create a menu and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneMenuTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'menu_ui'];

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
    'clone menu entity',
    'administer menu',
  ];

  /**
   * An administrative user with permission to configure menus settings.
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
   * Test menu entity clone.
   */
  public function testMenuEntityClone() {

    $menus = \Drupal::entityTypeManager()
      ->getStorage('menu')
      ->loadByProperties([
        'id' => 'account',
      ]);
    $menu = reset($menus);

    $edit = [
      'label' => 'Test menu cloned',
      'id' => 'test-menu-cloned',
    ];
    $this->drupalPostForm('entity_clone/menu/' . $menu->id(), $edit, t('Clone'));

    $menus = \Drupal::entityTypeManager()
      ->getStorage('menu')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $menu = reset($menus);
    $this->assertInstanceOf(Menu::class, $menu, 'Test menu cloned found in database.');
  }

}
