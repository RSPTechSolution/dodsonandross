<?php

namespace Drupal\acl\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Drupal 8 ACL Table destination.
 *
 * @MigrateDestination(
 *   id = "acl_table",
 *   destination_module="acl",
 * )
 */
class AclTable extends DestinationBase implements MigrateDestinationInterface {

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->table_name = $this->configuration['table_name'];
    $this->fields_list = $this->configuration['fields_list'];

    if (!empty($this->configuration['ids'])) {
      $this->ids = $this->configuration['ids'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $destination = $row->getDestination();

    $keys = array();
    foreach(array_keys($this->ids) as $id) {
      $keys[$id] = $destination[$id];
      unset($destination[$id]);
    }

    \Drupal::database()->merge($this->table_name)
      ->keys($keys)
      ->fields($destination)
      ->execute();

    $return = array_map(function($key) use ($row) {
      return $row->getDestinationProperty($key);
    }, $keys);

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return $this->ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return array_combine($this->fields_list, $this->fields_list);
  }

}
