<?php

namespace Drupal\acl\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6/7 ACL List Table source from database.
 *
 * @MigrateSource(
 *   id = "d6_d7_acl_list",
 *   source_module = "acl",
 * )
 */
class AclList extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('acl', 'a')
      ->fields('a', [
          'acl_id',
          'module',
          'name',
          'number',
        ]
      );
    $query->orderBy('acl_id');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'acl_id' => $this->t('Primary key: unique ACL ID.'),
      'module' => $this->t('The name of the module that created this ACL entry.'),
      'name'   => $this->t('A name (or other identifying information) for this ACL entry, given by the module that created it.'),
      'number' => $this->t('A number for this ACL entry, given by the module that created it.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['acl_id']['type'] = 'integer';

    return $ids;
  }

}
