<?php

namespace Drupal\commerce_license\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 Ubercart roles expiration source.
 *
 * This is for migrating roles granted with the uc_roles module to licenses.
 *
 * This class is just a starting point:
 * - The expiration settings are not handled. In particular, for licenses used
 *   with subscriptions, the expiry should be set to 'unlimited'.
 * - The case of more than one product selling the same role is not handled.
 * - Assumptions are made about renewals.
 *
 * @MigrateSource(
 *   id = "d6_ubercart_license_role",
 *   source_module = "uc_roles"
 * )
 */
class UcRolesLicense extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('uc_roles_expirations', 'ure')->fields('ure', [
      'reid',
      'uid',
      'rid',
      'expiration',
    ]);

    // Joining to {uc_roles_products} gets us the product node ID and the
    // duration configuration.
    // @todo this join assumes there is only one product per role.
    // If some roles are sold by multiple products, this will break!
    $query->innerJoin('uc_roles_products', 'urp', 'ure.rid = urp.rid');
    $query->fields('urp', [
      'nid',
      'duration',
      'granularity',
    ]);

    // Get the orders that purchased this product.
    // Join to {uc_orders} via {uc_order_products}, getting first the order
    // line items that hold the product, and the the corresponding order.
    $query->innerJoin('uc_order_products', 'uop', 'urp.nid = uop.nid');
    // This join also ensures that the orders are purchased by the users who
    // have a role granted.
    $query->innerJoin('uc_orders', 'uo', 'uop.order_id = uo.order_id AND ure.uid = uo.uid');

    $query->fields('uop', ['order_product_id']);
    $query->fields('uo', [
      'created',
      'modified',
      'order_id',
    ]);

    // Use a groupwise mininum selfjoin to get only the earliest order by each
    // user for a role.
    // @todo this assumes that later orders are renewals, and that there are no
    // gaps in a user's license ownership, e.g. user buys license, lets it
    // expire, buys another one.
    // @todo this join should also have a condition on the product nid.
    $query->leftJoin('uc_orders', 'uo_later', 'uo.uid = uo_later.uid AND uo.modified > uo_later.modified');
    $query->isNull('uo_later.order_id');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get the most recent order for this user and role product, if different
    // from the earliest order we retrieved in query().
    $query = $this->select('uc_order_products', 'uop');
    $query->innerJoin('uc_orders', 'uo', 'uop.order_id = uo.order_id');
    $query->condition('uop.nid', $row->getSourceProperty('nid'));
    $query->condition('uo.uid', $row->getSourceProperty('uid'));
    $query->condition('uo.order_id', $row->getSourceProperty('order_id'), '<>');
    $query->fields('uo', [
      'order_id',
      'created',
      'modified',
    ]);
    $query->orderBy('created', DESC);
    $query->range(0, 1);

    $latest_order_data = $query->execute()->fetchAssoc();

    if ($latest_order_data) {
      // Set the date of the last renewal to the creation date of the most
      // recent order for this role.
      // This is the closest thing we have?
      $row->setSourceProperty('renewed', $latest_order_data['created']);
    }
    else {
      $row->setSourceProperty('renewed', NULL);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return ([
      'reid' => $this->t('Record ID'),
      'uid' => $this->t('User ID'),
      'rid' => $this->t('The role ID'),
      'expiration' => $this->t('The expiration date'),
      'nid' => $this->t('Product node ID'),
      'duration' => $this->t('The interval multiplier'),
      'granularity' => $this->t('The interval'),
      'created' => $this->t('Earliest order created time'),
      'modified' => $this->t('Earliest order changed time'),
      'renewed' => $this->t('Latest order created time'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'reid' => [
        'type' => 'integer',
        'alias' => 'ure',
      ],
      // Add the order product ID as a key, so that order product migrations
      // can look up the license to reference it.
      'order_product_id' => [
        'type' => 'integer',
      ],
    ];
  }

}
