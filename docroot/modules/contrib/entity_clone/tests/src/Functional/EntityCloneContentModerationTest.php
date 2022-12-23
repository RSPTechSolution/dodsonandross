<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\content_moderation\Entity\ContentModerationState as ContentModerationStateEntity;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Create a moderated content and test the clone of its moderation state.
 *
 * @group entity_clone
 */
class EntityCloneContentModerationTest extends NodeTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_clone',
    'content_moderation',
    'language',
    'content_translation',
    'block',
  ];

  /**
   * {@inheritdoc}
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
    'use editorial transition create_new_draft',
    'use editorial transition publish',
    'use editorial transition archive',
    'use editorial transition archived_draft',
    'use editorial transition archived_published',
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

    ConfigurableLanguage::createFromLangcode('fr')->save();
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'page');

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test content entity clone.
   */
  public function testContentModerationEntityClone() {
    $node = Node::create([
      'type' => 'page',
      'title' => 'My node',
    ]);

    $node->save();
    $translation = $node->addTranslation('fr', $node->toArray());
    // Unfortunately content moderation only creates translations to the
    // moderation state entities when the actual translation of the source
    // entity gets saved (as opposed to an original node with multiple
    // translations).
    $translation->save();

    // Assert that we have a moderation state translation for each language.
    $node = Node::load($node->id());
    $this->assertCount(2, $node->getTranslationLanguages());
    $moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($node);
    $this->assertFalse($moderation_state->isNew());
    $this->assertCount(2, $moderation_state->getTranslationLanguages());
    foreach ($moderation_state->getTranslationLanguages() as $language) {
      $this->assertEquals('draft', $moderation_state->getTranslation($language->getId())->get('moderation_state')->value);
    }
    $moderation_state_id = $moderation_state->id();

    // Clone the node and assert that the moderation state is cloned and has
    // a translation for each language.
    $this->drupalGet(Url::fromUserInput('/entity_clone/node/' . $node->id()));
    $this->submitForm([], t('Clone'));

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => 'My node - Cloned',
      ]);
    $clone = reset($nodes);
    $this->assertInstanceOf(Node::class, $clone, 'Test node cloned found in database.');

    $this->assertCount(2, $clone->getTranslationLanguages());
    $clone_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($clone);
    $this->assertNotEquals($moderation_state_id, $clone_moderation_state->id());
    $this->assertFalse($clone_moderation_state->isNew());
    $this->assertCount(2, $clone_moderation_state->getTranslationLanguages());
    foreach ($clone_moderation_state->getTranslationLanguages() as $language) {
      $this->assertEquals('draft', $clone_moderation_state->getTranslation($language->getId())->get('moderation_state')->value);
    }

    // Create another node, but this time, move the state to published.
    $node = Node::create([
      'type' => 'page',
      'title' => 'My second node',
    ]);
    $node->save();
    $node->set('moderation_state', 'published');
    $node->setNewRevision();
    $node->save();
    $translation = $node->addTranslation('fr', $node->toArray());
    $translation->save();
    $moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($node);
    $this->assertFalse($moderation_state->isNew());
    $this->assertCount(2, $moderation_state->getTranslationLanguages());
    foreach ($moderation_state->getTranslationLanguages() as $language) {
      $this->assertEquals('published', $moderation_state->getTranslation($language->getId())->get('moderation_state')->value);
    }

    // Clone the node and assert that the moderation state is cloned and has
    // a translation for each language.
    $this->drupalGet(Url::fromUserInput('/entity_clone/node/' . $node->id()));
    $this->submitForm([], t('Clone'));

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => 'My second node - Cloned',
      ]);
    $clone = reset($nodes);
    $this->assertInstanceOf(Node::class, $clone, 'Test node cloned found in database.');

    $this->assertCount(2, $clone->getTranslationLanguages());
    $clone_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($clone);
    $this->assertFalse($clone_moderation_state->isNew());
    $this->assertCount(2, $clone_moderation_state->getTranslationLanguages());
    foreach ($clone_moderation_state->getTranslationLanguages() as $language) {
      // When we clone, the default moderation state is set on the clone for
      // both languages (draft), even if the cloned content was published.
      $this->assertEquals('draft', $clone_moderation_state->getTranslation($language->getId())->get('moderation_state')->value);
    }

    // Create another node, but this time the original should be published but
    // the translation should be draft.
    $node = Node::create([
      'type' => 'page',
      'title' => 'My third node',
    ]);
    $node->save();
    $translation = $node->addTranslation('fr', $node->toArray());
    $translation->save();
    $node->set('moderation_state', 'published');
    $node->setNewRevision();
    $node->save();

    $moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($node);
    $this->assertFalse($moderation_state->isNew());
    $this->assertCount(2, $moderation_state->getTranslationLanguages());
    $expected_map = [
      'en' => 'published',
      'fr' => 'draft',
    ];
    foreach ($moderation_state->getTranslationLanguages() as $language) {
      $this->assertEquals($expected_map[$language->getId()], $moderation_state->getTranslation($language->getId())->get('moderation_state')->value);
    }
    $this->assertTrue($node->getTranslation('en')->isPublished());
    $this->assertFalse($node->getTranslation('fr')->isPublished());

    // Clone the node and assert that the moderation state is reset to draft
    // for both languages.
    $this->drupalGet(Url::fromUserInput('/entity_clone/node/' . $node->id()));
    $this->submitForm([], t('Clone'));

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => 'My third node - Cloned',
      ]);
    $clone = reset($nodes);
    $this->assertInstanceOf(Node::class, $clone, 'Test node cloned found in database.');

    $this->assertCount(2, $clone->getTranslationLanguages());
    $this->assertFalse($clone->getTranslation('en')->isPublished());
    $this->assertFalse($clone->getTranslation('fr')->isPublished());
    $clone_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($clone);
    $this->assertFalse($clone_moderation_state->isNew());
    $this->assertCount(2, $clone_moderation_state->getTranslationLanguages());
    foreach ($clone_moderation_state->getTranslationLanguages() as $language) {
      $this->assertEquals('draft', $clone_moderation_state->getTranslation($language->getId())->get('moderation_state')->value);
    }

    // Create another node but this time clone while on the French and assert
    // that the moderation state default language is the same as of the node.
    $node = Node::create([
      'type' => 'page',
      'title' => 'My fourth node',
    ]);

    $node->save();
    $translation = $node->addTranslation('fr', ['title' => 'My fourth node FR'] + $node->toArray());
    $translation->save();
    $node = Node::load($node->id());
    $this->assertCount(2, $node->getTranslationLanguages());
    $this->drupalGet(Url::fromUserInput('/fr/entity_clone/node/' . $node->id()));
    $this->submitForm([], t('Clone'));

    $clone = Node::load($node->id() + 1);
    $this->assertInstanceOf(Node::class, $clone, 'Test node cloned found in database.');

    $this->assertCount(2, $clone->getTranslationLanguages());
    $this->assertEquals('My fourth node FR - Cloned', $clone->getTranslation('fr')->label());
    $clone_moderation_state = ContentModerationStateEntity::loadFromModeratedEntity($clone);
    $this->assertFalse($clone_moderation_state->isNew());
    $this->assertCount(2, $clone_moderation_state->getTranslationLanguages());
    foreach ($clone_moderation_state->getTranslationLanguages() as $language) {
      $this->assertEquals('draft', $clone_moderation_state->getTranslation($language->getId())->get('moderation_state')->value);
    }
    $this->assertTrue($clone_moderation_state->isDefaultTranslation());
    $this->assertEquals('en', $clone_moderation_state->language()->getId());

    // Create another node, published, translated and assert that upon cloning
    // the node status is reset to 0 to match the fact that it's a draft.
    $node = Node::create([
      'type' => 'page',
      'title' => 'My fifth node',
      'moderation_state' => 'published',
    ]);
    $node->save();
    $translation = $node->addTranslation('fr', $node->toArray());
    $translation->save();
    $node = Node::load($node->id());
    $this->assertCount(2, $node->getTranslationLanguages());
    $this->assertTrue($node->getTranslation('en')->isPublished());
    $this->assertTrue($node->getTranslation('fr')->isPublished());
    $this->drupalGet(Url::fromUserInput('/entity_clone/node/' . $node->id()));
    $this->submitForm([], t('Clone'));

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'title' => 'My fifth node - Cloned',
      ]);
    $clone = reset($nodes);
    $this->assertInstanceOf(Node::class, $clone, 'Test node cloned found in database.');
    $this->assertCount(2, $clone->getTranslationLanguages());
    $this->assertFalse($clone->getTranslation('en')->isPublished());
    $this->assertFalse($clone->getTranslation('fr')->isPublished());

  }

}
