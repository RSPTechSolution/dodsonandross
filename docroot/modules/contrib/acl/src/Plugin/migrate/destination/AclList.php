<?php

namespace Drupal\acl\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Drupal 8 ACL List Table destination.
 *
 * @MigrateDestination(
 *   id = "acl_list",
 *   destination_module="acl",
 * )
 */
class AclList extends DestinationBase implements MigrateDestinationInterface {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $destination = $row->getDestination();
    \Drupal::database()->merge('acl')
      ->key(array('acl_id' => $destination['acl_id']))
      ->fields(array(
        'module' => $destination['module'],
        'name' => $destination['name'],
        'figure' => $destination['figure'],
      ))
      ->execute();
    return [$row->getDestinationProperty('acl_id')];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['acl_id']['type'] = 'integer';

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'acl_id' => $this->t('Primary key: unique ACL ID.'),
      'module' => $this->t('The name of the module that created this ACL entry.'),
      'name'   => $this->t('A name (or other identifying information) for this ACL entry, given by the module that created it.'),
      'figure' => $this->t('A number for this ACL entry, given by the module that created it.'),
    ];
  }

}
