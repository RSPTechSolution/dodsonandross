<?php

namespace Drupal\acl\Tests\Migrate\d6;

use Drupal\acl\Tests\AclMigrationTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;


/**
 * Tests migration of ACL columns from Drupal 6 to Drupal 8.
 *
 * @group acl
 */
class MigrateAclList68Test extends MigrateDrupal6TestBase {

  use AclMigrationTestTrait;

  /**
   * Modules to load.
   */
  public static $modules = ['migrate_drupal', 'node', 'menu_ui', 'acl', 'acl_node_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loadFixture(__DIR__ . '/../../../../tests/fixtures/d6_d7_table.php');
    $this->installSchema('acl', ['acl', 'acl_user', 'acl_node']);
    $this->installSchema('node', ['node_access']);

    $this->executeMigration('d6_d7_acl');
    $this->executeMigration('d6_d7_acl_user');
    $this->executeMigration('d6_d7_acl_node');

    $this->migrateContent();
  }

}
