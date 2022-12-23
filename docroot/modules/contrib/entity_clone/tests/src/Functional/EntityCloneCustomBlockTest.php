<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\block_content\Functional\BlockContentTestBase;

/**
 * Creat ea block and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneCustomBlockTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * Enable dummy module that implements hook_block_insert() for exceptions and
   * field_ui to edit display settings.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'block', 'block_content'];

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
  protected $permissions = ['administer blocks', 'clone block_content entity'];

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test custom block entity clone.
   */
  public function testCustomBlockEntityClone() {

    $edit = [];
    $edit['info[0][value]'] = 'Test block ready to clone';
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('block/add/basic', $edit, t('Save'));

    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties([
        'info' => $edit['info[0][value]'],
      ]);
    $block = reset($blocks);
    $this->assertInstanceOf(BlockContent::class, $block, 'Test Block for clone found in database.');

    $this->drupalPostForm('entity_clone/block_content/' . $block->id(), [], t('Clone'));

    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties([
        'info' => $edit['info[0][value]'] . ' - Cloned',
        'body' => $edit['body[0][value]'],
      ]);
    $block = reset($blocks);
    $this->assertInstanceOf(BlockContent::class, $block, 'Test Block cloned found in database.');
  }

}
