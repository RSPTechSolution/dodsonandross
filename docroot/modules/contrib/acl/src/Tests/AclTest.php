<?php

namespace Drupal\acl\Tests;

use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Test case for ACL module.
 *
 * @group Access
 */
class AclTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'acl', 'acl_node_test'];

  protected $defaultTheme = 'classy';

  /**
   * Implements getInfo().
   *
   * @return array
   */
  public static function getInfo() {
    return array(
      'name' => 'ACL access permissions',
      'description' => 'Test ACL permissions for reading a node.',
      'group' => 'ACL',
    );
  }

  /**
   * Implements setUp().
   */
  function setUp(): void {
    parent::setUp();

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    $web_user = $this->drupalCreateUser(array('create page content'));
    $this->drupalLogin($web_user);
  }

  /**
   * Includes acl_create_acl, acl_delete_acl, acl_get_id_by_name
   */
  function testNodeAclCreateDelete() {
    // Add a node.
    $node1 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0));
    $this->assertTrue((bool) Node::load($node1->id()), t('Page node created.'));

    acl_create_acl('test1', $node1->getTitle());
    $acl_id = acl_get_id_by_name('test1', $node1->getTitle());
    $this->assertNotNull($acl_id, t('ACL ID was successfully found.'));
    $records = \Drupal::database()->select('acl')
      ->fields('acl', ['acl_id', 'name'])
      ->condition('acl_id', $acl_id)
      ->execute()
      ->fetchAll();
    $this->assertEquals(count($records), 1, t('ACL was successfully created.'));
    acl_delete_acl($records[0]->acl_id);

    $records = \Drupal::database()->select('acl')
      ->fields('acl', ['acl_id', 'name'])
      ->condition('acl_id', $records[0]->acl_id)
      ->execute()
      ->fetchAll();
    $this->assertTrue(TRUE, var_export($records, TRUE));
    $this->assertEquals(count($records), 0, t('ACL was successfully removed.'));
  }

  /**
   * Includes acl_add_user, acl_remove_user, acl_has_users,
   * acl_get_id_by_name, acl_has_user, acl_get_uids
   */
  function testNodeAclSingleUserAddRemove() {
    // Add a node.
    $node1 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0));
    $this->assertTrue((bool) Node::load($node1->id()), t('Page node created.'));

    acl_create_acl('test2', $node1->getTitle());
    // Check to see if grants added by node_test_node_access_records()
    // made it in.
    $acl_id = acl_get_id_by_name('test2', $node1->getTitle());
    $this->assertNotNull($acl_id, t('ACL ID was successfully found.'), $group = 'ACL');
    $records = \Drupal::database()->select('acl')
      ->fields('acl', ['acl_id', 'name'])
      ->condition('acl_id', $acl_id)
      ->execute()
      ->fetchAll();
    $this->assertEqual(count($records), 1, t('ACL was succesfully created.'), $group = 'ACL');

    // Add user (can't we use the user created in setup?).
    $web_user_1 = $this->drupalCreateUser();
    //$this->drupalLogin($web_user);
    acl_add_user($acl_id, $web_user_1->id());
    $records = \Drupal::database()->select('acl_user')
      ->fields('acl_user', ['acl_id', 'uid'])
      ->condition('uid', $web_user_1->id())
      ->execute()
      ->fetchAll();
    // Verify user is added.
    $this->assertEqual(count($records), 1, t('User was successfully added.'), $group = 'ACL');

    // Remove user.
    acl_remove_user($acl_id, $web_user_1->id());
    $records = \Drupal::database()->select('acl_user')
      ->fields('acl_user', ['acl_id', 'uid'])
      ->condition('uid', $web_user_1->id())
      ->execute()
      ->fetchAll();
    // Verify user is removed.
    $this->assertEqual(count($records), 0, t('User was successfully removed.'), $group = 'ACL');
  }

  /**
   * Includes acl_node_add_acl, acl_node_remove_acl, acl_node_clear_acls
   */
  function testNodeAclAddRemoveFromNode() {
    // Add nodes.
    $node1 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0));
    $this->assertTrue((bool) Node::load($node1->id()), t('Page node created.'));
    $node2 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0));
    $this->assertTrue((bool) Node::load($node2->id()), t('Page node created.'));
    $node3 = $this->drupalCreateNode(array('type' => 'page', 'promote' => 0));
    $this->assertTrue((bool) Node::load($node3->id()), t('Page node created.'));

    // Create an ACL and add nodes.
    $acl_id1 = acl_create_acl('test3', 'test', 1);
    $this->assertNotNull($acl_id1, t('ACL ID was created.'), $group = 'ACL');
    // Add two nodes.
    $query = \Drupal::database()->select('node', 'n')
      ->fields('n', array('nid'))
      ->condition('nid', array($node1->id(), $node2->id()), 'IN');
    acl_add_nodes($query, $acl_id1, 1, 1, 1);
    $count = \Drupal::database()->select('acl_node')
      ->condition('acl_id', $acl_id1)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($count, 2, t("2 nodes under control ($count)."), $group = 'ACL');
    // Add a third node.
    acl_node_add_acl($node3->id(), $acl_id1, 1, 1, 1);
    $count = \Drupal::database()->select('acl_node')
      ->condition('acl_id', $acl_id1)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($count, 3, t('3 nodes under control.'), $group = 'ACL');
    // Add the second node again.
    acl_node_add_acl($node2->id(), $acl_id1, 1, 1, 1);
    $count = \Drupal::database()->select('acl_node')
      ->condition('acl_id', $acl_id1)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($count, 3, t('Still only 3 nodes under control.'), $group = 'ACL');

    // Remove the second node again.
    acl_node_remove_acl($node2->id(), $acl_id1);
    $count = \Drupal::database()->select('acl_node')
      ->condition('acl_id', $acl_id1)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($count, 2, t('2 nodes left under control.'), $group = 'ACL');
    // Remove the second node again.
    acl_node_remove_acl($node2->id(), $acl_id1);
    $count = \Drupal::database()->select('acl_node')
      ->condition('acl_id', $acl_id1)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($count, 2, t('Still 2 nodes left under control.'), $group = 'ACL');

    // Create another ACL and add nodes.
    $acl_id2 = acl_create_acl('test3', 'test', 2);
    $this->assertNotNull($acl_id2, t('ACL ID was created.'), $group = 'ACL');
    acl_node_add_acl($node1->id(), $acl_id2, 1, 1, 1);
    acl_node_add_acl($node2->id(), $acl_id2, 1, 1, 1);
    $count = \Drupal::database()->select('acl_node')
      ->condition('acl_id', $acl_id2)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($count, 2, t('2 nodes under control.'), $group = 'ACL');
    // Remove a node (which has two ACLs).
    acl_node_clear_acls($node1->id(), 'test3');
    $count = \Drupal::database()->select('acl_node')
      ->condition('acl_id', $acl_id1)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($count, 1, t('1 node left under control.'), $group = 'ACL');
    $count = \Drupal::database()->select('acl_node')
      ->condition('acl_id', $acl_id2)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEqual($count, 1, t('1 node left under control.'), $group = 'ACL');
  }

}

/**
 * Included acl_node_delete
 */
function testNodeAclAddAndRemoveNode() {
}

/**
 * Included acl_user_cancel
 */
function testNodeAclAddAndRemoveUser() {
}

/**
 * Includes independ check of the permissions by checking the grants
 * table AND by trying to view the node as a unauthorised user and an
 * authorized user for each of the 3 use cases (view, edit, delete)
 */
function testNodeAclPermissionCheck() {
}
