<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\block\Entity\Block;
use Drupal\Tests\BrowserTestBase;

/**
 * Create an block and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneBlockTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'block'];

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
    'administer blocks',
    'clone block entity',
  ];

  /**
   * An administrative user with permission to configure blocks settings.
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
   * Test block entity clone.
   */
  public function testBlockEntityClone() {
    $config = \Drupal::configFactory();
    $block = Block::create([
      'plugin' => 'test_block',
      'region' => 'sidebar_first',
      'id' => 'test_block',
      'theme' => $config->get('system.theme')->get('default'),
      'label' => $this->randomMachineName(8),
      'visibility' => [],
      'weight' => 0,
    ]);
    $block->save();

    $edit = [
      'id' => 'test_block_cloned',
    ];
    $this->drupalPostForm('entity_clone/block/' . $block->id(), $edit, t('Clone'));

    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block')
      ->loadByProperties([
        'id' => $edit['id'],
      ]);
    $block = reset($blocks);
    $this->assertInstanceOf(Block::class, $block, 'Test block cloned found in database.');
  }

}
