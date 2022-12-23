<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Tests\BrowserTestBase;

/**
 * Test an entity form mode clone.
 *
 * @group entity_clone
 */
class EntityCloneEntityFormModeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'field_ui'];

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
    'clone entity_form_mode entity',
    'administer display modes',
  ];

  /**
   * An administrative user.
   *
   * With permission to configure entity form modes settings.
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
   * Test entity form mode entity clone.
   */
  public function testEntityFormModeEntityClone() {
    $entity_form_modes = \Drupal::entityTypeManager()
      ->getStorage('entity_form_mode')
      ->loadByProperties([
        'id' => 'user.register',
      ]);
    $entity_form_mode = reset($entity_form_modes);

    $edit = [
      'label' => 'User register cloned form mode',
      'id' => 'register_clone',
    ];
    $this->drupalPostForm('entity_clone/entity_form_mode/' . $entity_form_mode->id(), $edit, t('Clone'));

    $entity_form_modes = \Drupal::entityTypeManager()
      ->getStorage('entity_form_mode')
      ->loadByProperties([
        'id' => 'user.' . $edit['id'],
      ]);
    $entity_form_mode = reset($entity_form_modes);
    $this->assertInstanceOf(EntityFormMode::class, $entity_form_mode, 'Test entity form mode cloned found in database.');
  }

}
