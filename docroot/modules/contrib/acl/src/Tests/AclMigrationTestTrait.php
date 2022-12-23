<?php

namespace Drupal\acl\Tests;

use Drupal\node\Entity\Node;

/**
 * Provides common functionality for the ACL Migration test classes.
 */
trait AclMigrationTestTrait {

  /**
   * Provides information about database dumps directory.
   */
  protected function getAclDumpDirectory() {
    return __DIR__ . '/Table';
  }

  /**
   * Tests migration of ACL List.
   */
  public function testMigration() {
    // Checking `number` to `figure` migration.
    $acl_id = acl_get_id_by_name('acl_node_test', 'test_name', 123);
    $this->assertNotEquals(FALSE, $acl_id);

    // One more check.
    $acl_id = acl_get_id_by_figure('acl_node_test', 5);
    $this->assertEquals(2, $acl_id);

    // Testing `acl_user` migration.
    $this->assertEquals(TRUE, acl_has_user(1, 1));
    $this->assertEquals(TRUE, acl_has_user(2, 1));
    $this->assertEquals(TRUE, acl_has_user(1, 2));
    $this->assertNotEquals(TRUE, acl_has_user(2, 2));

    // Testing first migrated node grants.
    $node = Node::load(1);
    $this->assertNotNull($node, "Node 1 can be loaded");
    $grants = \Drupal::entityTypeManager()
      ->getAccessControlHandler('node')
      ->acquireGrants($node);
    $acl_grant_exists = FALSE;
    foreach ($grants as $grant) {
      if ($grant['realm'] == 'acl' && $grant['grant_update'] == TRUE && $grant['priority'] == 5) {
        $acl_grant_exists = TRUE;
      }
    }
    $this->assertEquals(TRUE, $acl_grant_exists);

    // Testing second migrated node grants.
    $node = Node::load(2);
    $grants = \Drupal::entityTypeManager()
      ->getAccessControlHandler('node')
      ->acquireGrants($node);
    $acl_grant_count = 0;
    foreach ($grants as $grant) {
      if ($grant['realm'] == 'acl') {
        if ($grant['grant_view'] == TRUE && $grant['grant_delete'] == TRUE && $grant['priority'] == 10) {
          $acl_grant_count++;
        }
        if ($grant['grant_view'] == TRUE && $grant['grant_update'] == TRUE && $grant['priority'] == 8) {
          $acl_grant_count++;
        }
      }
    }
    $this->assertEquals(2, $acl_grant_count);
  }

}
