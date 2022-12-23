<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Create a content and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneContentTest extends NodeTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'block', 'node', 'datetime', 'taxonomy', 'content_translation', 'language'];

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
    'bypass node access',
    'administer nodes',
    'clone node entity',
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

    ConfigurableLanguage::createFromLangcode('fr')->save();
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
  }

  /**
   * Test content entity clone.
   */
  public function testContentEntityClone() {
    $node_title = $this->randomMachineName(8);
    $node = Node::create([
      'type' => 'page',
      'title' => $node_title,
    ]);
    $node->save();

    $this->drupalPostForm('entity_clone/node/' . $node->id(), [], t('Clone'));

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => $node_title . ' - Cloned',
      ]);
    $node = reset($nodes);
    $this->assertInstanceOf(Node::class, $node, 'Test node cloned found in database.');
  }

  /**
   * Test content reference config entity.
   */
  public function testContentReferenceConfigEntity() {
    $this->createEntityReferenceField('node', 'page', 'config_field_reference', 'Config field reference', 'taxonomy_vocabulary');

    $node_title = $this->randomMachineName(8);
    $node = Node::create([
      'type' => 'page',
      'title' => $node_title,
      'config_field_reference' => 'tags'
    ]);
    $node->save();

    $this->drupalGet('entity_clone/node/' . $node->id());
    $this->assertSession()->elementNotExists('css', '#edit-recursive-nodepageconfig-field-reference');
  }

  /**
   * Test the cloned entity's created and changed dates.
   *
   * For entities that support these kinds of dates, both are reset to the
   * current time.
   */
   public function testCreatedAndChangedDate() {
     // Create the original node.
     $original_node_creation_date = new \DateTimeImmutable('1 year 1 month 1 day ago');
     $translation_creation_date = new \DateTimeImmutable('1 month 1 day ago');
     $original_node = Node::create([
       'type' => 'page',
       'title' => 'Test',
       'created' => $original_node_creation_date->getTimestamp(),
       'changed' => $original_node_creation_date->getTimestamp(),
     ]);
     $original_node->addTranslation('fr', $original_node->toArray());
     // The translation was created and updated later.
     $translation = $original_node->getTranslation('fr');
     $translation->setCreatedTime($translation_creation_date->getTimestamp());
     $translation->setChangedTime($translation_creation_date->getTimestamp());
     $original_node->save();

     $original_node = Node::load($original_node->id());
     $this->assertEquals($original_node_creation_date->getTimestamp(), $original_node->getCreatedTime());
     $this->assertEquals($original_node_creation_date->getTimestamp(), $original_node->getChangedTime());
     $this->assertEquals($translation_creation_date->getTimestamp(), $original_node->getTranslation('fr')->getCreatedTime());
     $this->assertEquals($translation_creation_date->getTimestamp(), $original_node->getTranslation('fr')->getChangedTime());

     // Clone the node.
     $this->drupalPostForm('entity_clone/node/' . $original_node->id(), [], t('Clone'));

     // Find the cloned node.
     $nodes = \Drupal::entityTypeManager()
       ->getStorage('node')
       ->loadByProperties([
         'title' => sprintf('%s - Cloned', $original_node->label()),
       ]);
     $this->assertGreaterThanOrEqual(1, count($nodes));
     /** @var \Drupal\node\NodeInterface $cloned_node */
     $cloned_node = reset($nodes);

     // Validate the cloned node's created time is more recent than the original
     // node.
     $this->assertNotEquals($original_node->getCreatedTime(), $cloned_node->getCreatedTime());
     $this->assertGreaterThanOrEqual($original_node->getCreatedTime(), $cloned_node->getCreatedTime());

     // Assert the changed time is equal to the newly created time since we
     // cannot have a changed date before it.
     $this->assertEquals($cloned_node->getCreatedTime(), $cloned_node->getChangedTime());

     // Validate the translation created and updated dates.
     $this->assertTrue($cloned_node->hasTranslation('fr'));
     $translation = $cloned_node->getTranslation('fr');
     // The created and updated times should be the same between the original
     // and the translation as both should be reset.
     $this->assertEquals($cloned_node->getCreatedTime(), $translation->getCreatedTime());
     $this->assertEquals($cloned_node->getChangedTime(), $translation->getChangedTime());
   }

}
