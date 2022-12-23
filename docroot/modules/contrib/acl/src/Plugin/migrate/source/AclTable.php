<?php

namespace Drupal\acl\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\State\StateInterface;

/**
 * Drupal 6/7 ACL Table source from database.
 *
 * @MigrateSource(
 *   id = "d6_d7_acl_table",
 *   source_module = "acl",
 * )
 */
class AclTable extends SqlBase {

  /**
   * Table name to fetch.
   *
   * @var array
   */
  protected $table_name;

  /**
   * Field names to fetch.
   *
   * @var array
   */
  protected $fields_list;

  /**
   * Default Ids for Migrate API.
   *
   * @var array
   */
  protected $ids = [
    'acl_id' => [
      'type' => 'integer',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->table_name = $this->configuration['table_name'];
    $this->fields_list = $this->configuration['fields_list'];

    if (!empty($this->configuration['ids'])) {
      $this->ids = $this->configuration['ids'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select($this->table_name, 'a')
      ->fields('a', $this->fields_list);
    $query->orderBy('acl_id');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array_combine($this->fields_list, $this->fields_list);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return $this->ids;
  }

}
