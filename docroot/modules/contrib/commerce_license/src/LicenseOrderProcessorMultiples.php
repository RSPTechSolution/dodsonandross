<?php

namespace Drupal\commerce_license;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Order processor that ensures only 1 of each license may be added to the cart.
 *
 * This is an order processor rather than an availability checker, as
 * \Drupal\commerce_order\AvailabilityOrderProcessor::check() removes the
 * entire order item if availability fails, whereas we only want to keep the
 * quantity at 1.
 *
 * @todo Figure out if the cart event subscriber covers all cases already.
 *
 * @see \Drupal\commerce_license\EventSubscriber\LicenseMultiplesCartEventSubscriber
 */
class LicenseOrderProcessorMultiples implements OrderProcessorInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new LicenseOrderProcessorMultiples object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    foreach ($order->getItems() as $order_item) {
      // Skip order items that do not have a license reference field.
      if (!$order_item->hasField('license')) {
        continue;
      }

      // @todo allow license type plugins to respond here, as for types that
      // collect user data in the checkout form, the same product variation can
      // result in different licenses.
      $quantity = $order_item->getQuantity();
      if ($quantity > 1) {
        // Force the quantity back to 1.
        $order_item->setQuantity(1);

        $purchased_entity = $order_item->getPurchasedEntity();
        if ($purchased_entity) {
          // Note that this message shows both when attempting to increase the
          // quantity of a license product already in the cart, and when
          // attempting to put more than 1 of a license product into the cart.
          // In the latter case, the message isn't as clear as it could be, but
          // site builders should be hiding the quantity field from the add to
          // cart form for license products, so this is moot.
          $this->messenger()->addError($this->t("You may only have one of @product-label in your cart.", [
            '@product-label' => $purchased_entity->label(),
          ]));
        }
      }
    }
  }

}
