<?php

namespace Drupal\commerce_stripe_test\EventSubscriber;

use Drupal\commerce_stripe\EventSubscriber\OrderPaymentIntentSubscriber;
use Stripe\Exception\ApiErrorException as StripeError;
use Stripe\PaymentIntent;

class DecoratedOrderPaymentIntentSubscriber extends OrderPaymentIntentSubscriber {

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    foreach ($this->updateList as $intent_id => $amount) {
      try {
        PaymentIntent::update($intent_id, ['amount' => $amount]);
      }
      catch (StripeError $e) {
        // Ensure all API exceptions throw during testing.
        throw $e;
      }
    }
  }

}
