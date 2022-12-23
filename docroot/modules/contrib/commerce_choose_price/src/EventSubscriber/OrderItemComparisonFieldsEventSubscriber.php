<?php

namespace Drupal\commerce_choose_price\EventSubscriber;

use Drupal\commerce_cart\Event\CartEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_cart\Event\OrderItemComparisonFieldsEvent;

/**
 * Class OrderItemComparisonFieldsEventSubscriber.
 *
 * @package Drupal\interflora_product
 */
class OrderItemComparisonFieldsEventSubscriber implements EventSubscriberInterface {

  /**
   * Add ribbon text to the list of fields being compared.
   */
  public function onOrderItemComparison(OrderItemComparisonFieldsEvent $event) {
    $item = $event->getOrderItem();
    if($item->hasField('allow_override_price') && $item->get('allow_override_price')->getValue()) {
      $fields = $event->getComparisonFields();
      $fields[] = 'unit_price';
      $event->setComparisonFields($fields);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CartEvents::ORDER_ITEM_COMPARISON_FIELDS][] = ['onOrderItemComparison'];

    return $events;
  }

}
