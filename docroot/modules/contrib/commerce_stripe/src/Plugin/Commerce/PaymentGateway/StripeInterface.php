<?php

namespace Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Stripe payment gateway.
 */
interface StripeInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Get the Stripe API Publisable key set for the payment gateway.
   *
   * @return string
   *   The Stripe API publishable key.
   */
  public function getPublishableKey();

  /**
   * Create a payment intent for an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param bool $capture
   *   Whether the created payment intent capture is automatic or manual.
   *
   * @return \Stripe\PaymentIntent
   *   The payment intent.
   */
  public function createPaymentIntent(OrderInterface $order, $capture = TRUE);

}
