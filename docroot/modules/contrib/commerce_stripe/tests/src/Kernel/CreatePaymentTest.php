<?php

namespace Drupal\Tests\commerce_stripe\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_payment\Exception\SoftDeclineException;
use Drupal\commerce_price\Price;
use Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway\StripeInterface;
use Stripe\PaymentIntent;

/**
 * Tests creating a payment.
 *
 * @group commerce_stripe
 */
class CreatePaymentTest extends StripeIntegrationTestBase {

  /**
   * Tests createPayment.
   *
   * @param string $payment_method_token
   *   The payment method token.
   * @param bool $capture
   *   The capture.
   * @param string $confirmed_status
   *   The confirmed intent status.
   *
   * @dataProvider dataProviderCreatePayment
   */
  public function testCreatePayment($payment_method_token, $capture, $confirmed_status) {
    $gateway = $this->generateGateway();
    $plugin = $gateway->getPlugin();
    assert($plugin instanceof StripeInterface);

    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $gateway->id(),
      'payment_gateway_mode' => 'test',
      'remote_id' => $payment_method_token,
    ]);
    $payment_method->save();

    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'uid' => 0,
      'state' => 'draft',
    ]);
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('10.50', 'USD'),
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->set('payment_method', $payment_method);
    $order->set('payment_gateway', $gateway);
    $order->save();
    $this->container->get('commerce_stripe.order_events_subscriber')->destruct();

    $payment = Payment::create([
      'state' => 'new',
      'amount' => $order->getBalance(),
      'payment_gateway' => $gateway->id(),
      'payment_method' => $payment_method->id(),
      'order_id' => $order->id(),
    ]);

    $intent = $plugin->createPaymentIntent($order, $capture);
    // Programmatically confirm the intent, the customer would be performing
    // this action on the client side.
    $intent->confirm();
    if ($confirmed_status === PaymentIntent::STATUS_REQUIRES_ACTION) {
      $this->expectException(SoftDeclineException::class);
      $this->expectExceptionMessage('The payment intent requires action by the customer for authentication');
    }
    $plugin->createPayment($payment, $capture);
    $intent = PaymentIntent::retrieve($order->getData('stripe_intent'));
    $this->assertEquals($capture ? 'completed' : 'authorization', $payment->getState()->value);
    $this->assertEquals($intent->charges->data[0]->id, $payment->getRemoteId());
    // Tests metadata set by commerce_stripe_test.
    $this->assertEquals($intent->metadata['payment_uuid'], $payment->uuid());

    $order = $this->reloadEntity($order);
    $this->assertNull($order->getData('stripe_intent'));
    $order->getState()->applyTransitionById('place');
    $order->save();
  }

  /**
   * Data provider for the create payment test.
   *
   * @return \Generator
   *   The test data.
   */
  public function dataProviderCreatePayment() {
    // 3DS 2 authentication must be completed for the payment to be successful.
    yield ['pm_card_threeDSecure2Required', TRUE, PaymentIntent::STATUS_REQUIRES_ACTION];
    yield ['pm_card_threeDSecure2Required', FALSE, PaymentIntent::STATUS_REQUIRES_ACTION];
    // 3DS authentication may still be performed, but is not required.
    yield ['pm_card_threeDSecureOptional', TRUE, PaymentIntent::STATUS_SUCCEEDED];
    // 3DS is supported for this card, but this card is not enrolled in 3D Secure
    yield ['pm_card_visa', TRUE, PaymentIntent::STATUS_SUCCEEDED];
    // 3DS is not supported on this card and cannot be invoked.
    yield ['pm_card_amex_threeDSecureNotSupported', TRUE, PaymentIntent::STATUS_SUCCEEDED];
  }

}
