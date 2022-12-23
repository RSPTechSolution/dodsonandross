<?php

namespace Drupal\commerce_order;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the order storage.
 */
class OrderStorage extends CommerceContentEntityStorage {

  /**
   * The order refresh.
   *
   * @var \Drupal\commerce_order\OrderRefreshInterface
   */
  protected $orderRefresh;

  /**
   * Whether the order refresh should be skipped.
   *
   * @var bool
   */
  protected $skipRefresh = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->orderRefresh = $container->get('commerce_order.order_refresh');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loadUnchanged($id) {
    // This method is used by the entity save process, triggering an order
    // refresh would cause a save-within-a-save.
    $this->skipRefresh = TRUE;
    $unchanged_order = parent::loadUnchanged($id);
    $this->skipRefresh = FALSE;
    return $unchanged_order;
  }

  /**
   * {@inheritdoc}
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    if ($hook == 'presave') {
      // Order::preSave() has completed, now run the storage-level pre-save
      // tasks. These tasks can modify the order, so they need to run
      // before the entity/field hooks are invoked.
      $this->doOrderPreSave($entity);
    }

    parent::invokeHook($hook, $entity);
  }

  /**
   * Performs order-specific pre-save tasks.
   *
   * This includes:
   * - Refreshing the order.
   * - Recalculating the total price.
   * - Dispatching the "order paid" event.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  protected function doOrderPreSave(OrderInterface $order) {
    // Ensure the order doesn't reference any removed order item by resetting
    // the "order_items" field with order items that were successfully loaded
    // from the database.
    $order->set('order_items', $order->getItems());
    if ($order->getRefreshState() == OrderInterface::REFRESH_ON_SAVE) {
      $this->orderRefresh->refresh($order);
    }
    // Only the REFRESH_ON_LOAD state needs to be persisted on the entity.
    if ($order->getRefreshState() != OrderInterface::REFRESH_ON_LOAD) {
      $order->setRefreshState(NULL);
    }
    $order->recalculateTotalPrice();

    // Notify other modules if the order has been fully paid.
    $original_paid = isset($order->original) ? $order->original->isPaid() : FALSE;
    if ($order->isPaid() && !$original_paid) {
      // Order::preSave() initializes the 'paid_event_dispatched' flag to FALSE.
      // Skip dispatch if it already happened once (flag is TRUE), or if the
      // order was completed before Commerce 8.x-2.10 (flag is NULL).
      if ($order->getData('paid_event_dispatched') === FALSE) {
        $event = new OrderEvent($order);
        $this->eventDispatcher->dispatch($event, OrderEvents::ORDER_PAID);
        $order->setData('paid_event_dispatched', TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function postLoad(array &$entities) {
    if (!$this->skipRefresh) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface[] $entities */
      foreach ($entities as $entity) {
        $explicitly_requested = $entity->getRefreshState() == OrderInterface::REFRESH_ON_LOAD;
        if ($explicitly_requested || $this->orderRefresh->shouldRefresh($entity)) {
          // Reuse the doPostLoad logic.
          $entity->setRefreshState(OrderInterface::REFRESH_ON_SAVE);
          $entity->save();
        }
      }
    }

    return parent::postLoad($entities);
  }

}
