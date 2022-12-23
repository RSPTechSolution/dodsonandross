<?php

namespace Drupal\Tests\entity_clone\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests entity clone access.
 *
 * @group entity_clone
 */
class EntityCloneAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
    * {@inheritdoc}
    */
   public static $modules = [
     'node',
     'field',
     'text',
     'user',
     'entity_clone',
     'system',
   ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);

    $this->installConfig([
      'node',
      'user',
      'system',
      'entity_clone',
    ]);

    // Call the user module install process that creates the anonymous user
    // and user 1.
    $this->container->get('module_handler')->loadInclude('user', 'install');
    user_install();

    $node_type = NodeType::create([
      'type' => 'page',
      'id' => 'page',
    ]);
    $node_type->save();
  }

  /**
   * Tests that users need to have the correct permissions to clone an entity.
   */
  public function testCloneAccess() {
    $node = Node::create([
      'type' => 'page',
      'title' => 'My node to clone',
      'status' => TRUE,
    ]);

    $node->save();

    $user_no_permissions = $this->createUser(['access content']);
    $user_that_can_create = $this->createUser(['access content', 'create page content']);
    $user_that_can_clone = $this->createUser(['access content', 'clone node entity']);
    $user_that_can_do_both = $this->createUser(['access content', 'clone node entity', 'create page content']);

    $url = $node->toUrl('clone-form');

    $access_control_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('node');

    // The user without permissions can view the content but cannot clone.
    $this->assertTrue($access_control_handler->access($node, 'view', $user_no_permissions));
    $this->assertFalse($access_control_handler->access($node, 'clone', $user_no_permissions));
    $this->assertFalse($url->access($user_no_permissions));

    // The user that can create content, cannot clone.
    $this->assertTrue($access_control_handler->createAccess('page', $user_that_can_create));
    $this->assertFalse($access_control_handler->access($node, 'clone', $user_that_can_create));
    $this->assertFalse($url->access($user_that_can_create));

    // The user that has clone permissions, but cannot create content, cannot
    // clone.
    $this->assertFalse($access_control_handler->createAccess('page', $user_that_can_clone));
    $this->assertFalse($access_control_handler->access($node, 'clone', $user_that_can_clone));
    $this->assertFalse($url->access($user_that_can_clone));

    // The user that can do both, can clone.
    $this->assertTrue($access_control_handler->createAccess('page', $user_that_can_do_both));
    $this->assertTrue($access_control_handler->access($node, 'clone', $user_that_can_do_both));
    $this->assertTrue($url->access($user_that_can_do_both));
  }
}
