<?php
/**
 * @file
 * ACL table dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('system')
  ->fields(array(
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
  ))
  ->values(array(
    'filename' => 'modules/acl/acl.module',
    'name' => 'acl',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '6000',
    'weight' => '0',
  ))
  ->execute();


$connection->schema()->createTable(
  'acl',
  [
    'fields' => [
      'acl_id' => [
        'type' => 'serial',
        'not null' => TRUE,
      ],
      'module' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
      'name' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'number' => [
        'type' => 'int',
      ],
    ],
    'primary key' => ['acl_id'],
    'indexes' => [
      'module_name_number' => [
        ['module', 64],
        ['name', 64],
        'number',
      ],
      'module_number' => [
        array('module', 64),
        'number',
      ],
    ],
  ]
);

$connection->insert('acl')
  ->fields([
    'module',
    'name',
    'number',
  ])
  ->values([
    'acl_id' => 1,
    'module' => 'acl_node_test',
    'name' => 'test_name',
    'number' => 123,
  ])
  ->values([
    'acl_id' => 2,
    'module' => 'acl_node_test',
    'name' => 'access_list',
    'number' => 5,
  ])
  ->execute();

$connection->schema()->createTable(
  'acl_user',
  [
    'fields' => [
      'acl_id' => [
        'type' => 'int',
        'not null' => TRUE,
        'unsigned' => TRUE,
      ],
      'uid' => [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ],
    ],
    'primary key' => ['acl_id', 'uid'],
    'indexes' => [
      'uid' => ['uid'],
    ],
  ]
);

$connection->insert('acl_user')
  ->fields([
    'acl_id',
    'uid',
  ])
  ->values([
    'acl_id' => 1,
    'uid' => 1,
  ])
  ->values([
    'acl_id' => 2,
    'uid' => 1,
  ])
  ->values([
    'acl_id' => 1,
    'uid' => 2,
  ])
  ->execute();

$connection->schema()->createTable(
  'acl_node',
  [
    'fields' => [
      'acl_id' => [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ],
      'nid' => [
        'description' => 'The {node}.nid to grant permissions for.',
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
      ],
      'grant_view' => [
        'description' => 'Whether to grant "view" permission.',
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
      ],
      'grant_update' => [
        'description' => 'Whether to grant "update" permission.',
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
      ],
      'grant_delete' => [
        'description' => 'Whether to grant "delete" permission.',
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
      ],
      'priority' => [
        'description' => 'The priority of this grant record (for hook_node_access_records()).',
        'type' => 'int',
        'size' => 'small',
        'not null' => TRUE,
        'default' => 0,
      ],
    ],
    'primary key' => ['acl_id', 'nid'],
    'indexes' => [
      'nid' => ['nid'],
    ],
  ]
);

$connection->insert('acl_node')
  ->fields([
    'acl_id',
    'nid',
    'grant_view',
    'grant_update',
    'grant_delete',
    'priority',
  ])
  ->values([
    'acl_id' => 1,
    'nid' => 1,
    'grant_view' => 1,
    'grant_update' => 1,
    'grant_delete' => 0,
    'priority' => 5,
  ])
  ->values([
    'acl_id' => 1,
    'nid' => 2,
    'grant_view' => 1,
    'grant_update' => 0,
    'grant_delete' => 1,
    'priority' => 10,
  ])
  ->values([
    'acl_id' => 2,
    'nid' => 2,
    'grant_view' => 1,
    'grant_update' => 1,
    'grant_delete' => 0,
    'priority' => 8,
  ])
  ->execute();
