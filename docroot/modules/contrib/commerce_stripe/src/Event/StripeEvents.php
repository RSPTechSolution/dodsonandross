<?php

namespace Drupal\commerce_stripe\Event;

/**
 * Defines events for the Commerce Stripe module.
 */
class StripeEvents {

  /**
   * Name of the event fired to add additional transaction data.
   *
   * This event is triggered when a Charge transaction is going
   * to be created. It allows subscribers to add additional
   * transaction data and metadata about the transaction.
   *
   * @Event
   *
   * @see https://stripe.com/blog/adding-context-with-metadata
   * @see https://stripe.com/docs/api#metadata
   * @see \Drupal\commerce_stripe\Event\TransactionDataEvent
   */
  const TRANSACTION_DATA = 'commerce_stripe.transaction_data';

}
