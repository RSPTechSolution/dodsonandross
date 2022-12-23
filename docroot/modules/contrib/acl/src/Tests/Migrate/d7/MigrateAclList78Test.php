<?php

namespace Drupal\acl\Tests\Migrate\d7;

use Drupal\acl\Tests\AclMigrationTestTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of ACL columns from Drupal 7 to Drupal 8.
 *
 * @group acl
 */
class MigrateAclList78Test extends MigrateDrupal7TestBase {

  use AclMigrationTestTrait;
  use DependencySerializationTrait;

  /**
   * Modules to load.
   *
   * @var array
   */
  public static $modules = [
    'migrate_drupal',
    'acl',
    'acl_node_test',
    'comment',
    'datetime',
    'filter',
    'image',
    'link',
    'menu_ui',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

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
