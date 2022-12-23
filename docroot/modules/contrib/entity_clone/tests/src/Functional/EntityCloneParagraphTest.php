<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Create a content with a paragraph and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneParagraphTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'paragraphs_demo'];

  /**
   * Theme to enable by default
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * Profile to install.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone node entity',
    'bypass node access',
  ];

  /**
   * A user with permission to bypass content access checks.
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
   * Tests cloning of paragraph entities.
   */
  public function testParagraphClone() {
    // Use node title from paragraphs_demo_install().
    $node_title = 'Welcome to the Paragraphs Demo module!';
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => $node_title,
      ]);
    $node = reset($nodes);

    // Clone all paragraphs except the shared library paragraph.
    $clone_options = [
      'recursive[node.paragraphed_content_demo.field_paragraphs_demo][references][1][clone]' => 1,
      'recursive[node.paragraphed_content_demo.field_paragraphs_demo][references][2][clone]' => 1,
      'recursive[node.paragraphed_content_demo.field_paragraphs_demo][references][3][clone]' => 1,
      'recursive[node.paragraphed_content_demo.field_paragraphs_demo][references][5][clone]' => 1,
      'recursive[node.paragraphed_content_demo.field_paragraphs_demo][references][5][children][recursive][paragraph.nested_paragraph.field_paragraphs_demo][references][4][clone]' => 1,
    ];

    $this->drupalPostForm('entity_clone/node/' . $node->id(), $clone_options, t('Clone'));

    $clones = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => $node_title . ' - Cloned',
      ]);
    $clone = reset($clones);

    $original_paragraph = $node->get('field_paragraphs_demo')
      ->first()
      ->get('entity')
      ->getTarget()
      ->getValue();

    $cloned_paragraph = $clone->get('field_paragraphs_demo')
      ->first()
      ->get('entity')
      ->getTarget()
      ->getValue();

    $this->assertNotEqual($original_paragraph->getParentEntity()->id(), $cloned_paragraph->getParentEntity()->id());
  }

}
