<?php

namespace Drupal\commerce_stripe_test\EventSubscriber;

use Drupal\commerce_stripe\Event\StripeEvents;
use Drupal\commerce_stripe\Event\TransactionDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransactionDataSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      StripeEvents::TRANSACTION_DATA => 'addTransactionData',
    ];
  }

  /**
   * Adds additional metadata to a transaction.
   *
   * @param \Drupal\commerce_stripe\Event\TransactionDataEvent $event
   *   The transaction data event.
   */
  public function addTransactionData(TransactionDataEvent $event) {
    $payment = $event->getPayment();
    $metadata = $event->getMetadata();
    // Add the payment's UUID to the Stripe transaction metadata. For example,
    // another service may query Stripe payment transactions and also load the
    // payment from Drupal Commerce over JSON API.
    $metadata['payment_uuid'] = $payment->uuid();
    $event->setMetadata($metadata);
  }

}
