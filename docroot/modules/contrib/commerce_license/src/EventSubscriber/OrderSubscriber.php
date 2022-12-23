<?php

namespace Drupal\commerce_license\EventSubscriber;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderAssignEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Changes a license's state in sync with an order's workflow.
 */
class OrderSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OrderSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_PAID => 'onPaid',
      'commerce_order.place.pre_transition' => ['onPlace', 100],
      // Handle assignment of license owner to the correct user after
      // anonymous checkout.
      OrderEvents::ORDER_ASSIGN => ['onAssign', 0],
      // Event for reaching the 'canceled' order state.
      'commerce_order.cancel.post_transition' => ['onCancel', -100],
    ];
  }

  /**
   * Activates the licenses on order paid.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function onPaid(OrderEvent $event) {
    $order = $event->getOrder();
    $licensable_order_items = $this->getLicensableOrderItems($order);

    foreach ($licensable_order_items as $order_item) {
      /** @var \Drupal\commerce_license\Entity\LicenseInterface $license */
      $license = $order_item->get('license')->entity;
      // We don't need to do anything if there is already an active license
      // referenced by this order item.
      if ($license && $license->getState()->getId() === 'active') {
        continue;
      }

      if (!$license) {
        $license = $this->createLicenseFromOrderItem($order_item);
      }
      $license->set('state', 'active');
      $license->save();
    }
  }

  /**
   * Reacts to an order being placed.
   *
   * Creates the licenses for licensable order items, and optionally activate
   * them if configured to do so at the product variation type level.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function onPlace(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $licensable_order_items = $this->getLicensableOrderItems($order);
    $product_variation_type_storage = $this->entityTypeManager->getStorage('commerce_product_variation_type');

    foreach ($licensable_order_items as $order_item) {
      $license = $order_item->get('license')->entity;
      // We don't need to do anything if there is already an active license
      // referenced by this order item.
      if ($license && $license->getState()->getId() === 'active') {
        continue;
      }

      if (!$license) {
        $license = $this->createLicenseFromOrderItem($order_item);
      }
      $purchased_entity = $order_item->getPurchasedEntity();
      $product_variation_type = $product_variation_type_storage->load($purchased_entity->bundle());
      $activate_on_place = $product_variation_type->getThirdPartySetting('commerce_license', 'activate_on_place');

      // License activation shouldn't happen when the order is placed, skip
      // to the next order item.
      if (!$activate_on_place) {
        continue;
      }
      $license->set('state', 'active');
      $license->save();
    }
  }

  /**
   * Reacts to assignment of order ownership to new user.
   *
   * Assign the licenses to the new order owner.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The event we subscribed to.
   */
  public function onAssign(OrderAssignEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();
    $new_owner = $event->getCustomer();

    // Look for licenses on order items and assign new owner.
    $licensable_order_items = $this->getLicensableOrderItems($order);
    foreach ($licensable_order_items as $order_item) {
      $license = $order_item->get('license')->entity;
      if ($license) {
        $license->setOwner($new_owner);
        $license->save();
      }
    }
  }

  /**
   * Reacts to an order being cancelled.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function onCancel(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $license_order_items = $this->getLicensableOrderItems($order);

    foreach ($license_order_items as $order_item) {
      // Get the license from the order item.
      /** @var \Drupal\commerce_license\Entity\LicenseInterface $license */
      $license = $order_item->get('license')->entity;
      if (!$license) {
        continue;
      }

      // Cancel the license.
      if ($license->getState()->isTransitionAllowed('cancel')) {
        $license->getState()->applyTransitionById('cancel');
        $license->save();
      }
    }
  }

  /**
   * Returns the order items from an order which are for licensed products.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface[]
   *   An array of the order items whose purchased products are for licenses.
   */
  protected function getLicensableOrderItems(OrderInterface $order) {
    $order_items = [];

    foreach ($order->getItems() as $order_item) {
      // Skip order items that do not have a license reference field.
      // We check order items rather than the purchased entity to allow products
      // with licenses to be purchased without the checkout flow triggering
      // our synchronization. This is for cases such as recurring orders, where
      // the license entity should not be put through the normal workflow.
      // Checking the order item's bundle for our entity trait is expensive, as
      // it requires loading the bundle entity to call hasTrait() on it.
      // For now, just check whether the order item has our trait's field on it.
      // @see https://www.drupal.org/node/2894805
      if (!$order_item->hasField('license')) {
        continue;
      }
      $purchased_entity = $order_item->getPurchasedEntity();

      // This order item isn't "licensable" if it doesn't have
      // (or no longer has) a reference to the purchased entity.
      if (is_null($purchased_entity)) {
        continue;
      }

      // This order item isn't "licensable" if the purchased entity it
      // references isn't properly configured.
      if (!$purchased_entity->hasField('license_type') || $purchased_entity->get('license_type')->isEmpty()) {
        continue;
      }

      $order_items[] = $order_item;
    }

    return $order_items;
  }

  /**
   * Creates the license using the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   A licensable order item.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   The created license.
   */
  protected function createLicenseFromOrderItem(OrderItemInterface $order_item) {
    /** @var \Drupal\commerce_license\LicenseStorageInterface $license_storage */
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    $license = $license_storage->createFromOrderItem($order_item);
    // The license is "pending" until it gets activated, either when the order
    // gets paid, or if the license should be activated on order place.
    $license->set('state', 'pending');
    $license->save();
    // Set the license field on the order item so we have a reference
    // and can get hold of it in later events.
    $order_item->license = $license->id();
    $order_item->save();

    return $license;
  }

}
