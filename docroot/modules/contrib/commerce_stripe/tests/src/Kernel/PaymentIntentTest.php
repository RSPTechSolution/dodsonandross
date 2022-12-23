<?php

namespace Drupal\Tests\commerce_stripe\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway\StripeInterface;
use Drupal\user\Entity\User;
use Prophecy\Argument;
use Stripe\PaymentIntent;

/**
 * Payment intent tests.
 *
 * @group commerce_stripe
 */
class PaymentIntentTest extends StripeIntegrationTestBase {

  /**
   * Tests creating payment intents.
   *
   * @param string $payment_method_token
   *   The test payment method token.
   * @param bool $capture
   *   The capture.
   * @param string $initial_status
   *   The initial payment intent status.
   * @param string $confirmed_status
   *   The confirmed payment intent status.
   *
   * @dataProvider dataProviderCreatePaymentIntent
   */
  public function testCreatePaymentIntent($payment_method_token, $capture, $initial_status, $confirmed_status) {
    $gateway = $this->generateGateway();
    $plugin = $gateway->getPlugin();
    assert($plugin instanceof StripeInterface);
    $payment_method = $this->prophesize(PaymentMethodInterface::class);
    $payment_method->getRemoteId()->willReturn($payment_method_token);

    $order = $this->prophesize(OrderInterface::class);
    $order->get('payment_method')->willReturn((object) [
      'entity' => $payment_method->reveal(),
    ]);
    $order->getTotalPrice()->willReturn(new Price('15.00', 'USD'));
    $order->getStoreId()->willReturn(1111);
    $order->id()->willReturn(9999);
    $order->getCustomer()->willReturn(User::getAnonymousUser());
    $order->setData('stripe_intent', Argument::containingString('pi_'))->willReturn($order->reveal());
    $order->save()->willReturn(NULL);

    $intent = $plugin->createPaymentIntent($order->reveal(), $capture);
    $this->assertEquals($capture ? 'automatic' : 'manual', $intent->capture_method);
    $this->assertEquals($initial_status, $intent->status);
    $this->assertEquals($intent->currency, 'usd');
    $this->assertEquals($intent->amount, 1500);

    $intent = $intent->confirm();
    $this->assertEquals($confirmed_status, $intent->status);
  }

  /**
   * Tests that the order total syncs the payment intent total.
   *
   * @param bool $deleted_gateway
   *   Boolean to determine if the test should delete the gateway.
   *
   * @dataProvider dataProviderOrderSync
   */
  public function testIntentOrderTotalSync($deleted_gateway) {
    $gateway = $this->generateGateway();
    $plugin = $gateway->getPlugin();
    assert($plugin instanceof StripeInterface);

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
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $gateway->id(),
      'payment_gateway_mode' => 'test',
      'remote_id' => 'pm_card_threeDSecure2Required|',
    ]);
    $payment_method->save();
    $order->set('payment_method', $payment_method);
    $order->set('payment_gateway', $gateway);
    $order->save();

    $intent = $plugin->createPaymentIntent($order);

    $this->assertEquals(1050, $intent->amount);

    if ($deleted_gateway) {
      $gateway->delete();
    }

    $order = $this->reloadEntity($order);
    $order->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => 'Back to school discount',
      'amount' => new Price('-5.00', 'USD'),
    ]));
    $order->save();
    // Flush pending updates.
    $this->container->get('commerce_stripe.order_events_subscriber')->destruct();

    $this->assertEquals('5.50', $order->getTotalPrice()->getNumber());
    $intent = PaymentIntent::retrieve($intent->id);
    // If the payment gateway was deleted, the payment intent could not
    // be updated.
    $this->assertEquals($deleted_gateway ? 1050 : 550, $intent->amount);
  }

  /**
   * Tests the intent sync does not fail if order was emptied.
   */
  public function testIntentEmptyOrderSync() {
    $gateway = $this->generateGateway();
    $plugin = $gateway->getPlugin();
    assert($plugin instanceof StripeInterface);

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
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $gateway->id(),
      'payment_gateway_mode' => 'test',
      'remote_id' => 'pm_card_threeDSecure2Required|',
    ]);
    $payment_method->save();
    $order->set('payment_method', $payment_method);
    $order->set('payment_gateway', $gateway);
    $order->save();

    $intent = $plugin->createPaymentIntent($order);

    $this->assertEquals(1050, $intent->amount);

    $order->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => 'Back to school discount',
      'amount' => new Price('-5.00', 'USD'),
    ]));
    $order->save();
    // Flush pending updates.
    $this->container->get('commerce_stripe.order_events_subscriber')->destruct();

    $this->assertEquals('5.50', $order->getTotalPrice()->getNumber());
    $intent = PaymentIntent::retrieve($intent->id);
    $this->assertEquals(550, $intent->amount);

    $order->setAdjustments([]);
    $order->removeItem($order_item);
    $order->save();
    // Flush pending updates.
    $this->container->get('commerce_stripe.order_events_subscriber')->destruct();

    $this->assertNull($order->getTotalPrice());
    $intent = PaymentIntent::retrieve($intent->id);
    $this->assertEquals(550, $intent->amount, 'Intent has the same previous value');

    $order->addItem($order_item);
    $order->save();
    // Flush pending updates.
    $this->container->get('commerce_stripe.order_events_subscriber')->destruct();

    $this->assertEquals('10.50', $order->getTotalPrice()->getNumber());
    $intent = PaymentIntent::retrieve($intent->id);
    $this->assertEquals(1050, $intent->amount);
  }

  /**
   * Data provider for createPaymentIntent.
   *
   * @return \Generator
   *   The test data.
   */
  public function dataProviderCreatePaymentIntent() {
    // 3DS 2 authentication must be completed for the payment to be successful.
    yield ['pm_card_threeDSecure2Required', TRUE, PaymentIntent::STATUS_REQUIRES_CONFIRMATION, PaymentIntent::STATUS_REQUIRES_ACTION];
    yield ['pm_card_threeDSecure2Required', FALSE, PaymentIntent::STATUS_REQUIRES_CONFIRMATION, PaymentIntent::STATUS_REQUIRES_ACTION];
    // 3DS authentication may still be performed, but is not required.
    yield ['pm_card_threeDSecureOptional', TRUE, PaymentIntent::STATUS_REQUIRES_CONFIRMATION, PaymentIntent::STATUS_SUCCEEDED];
    // 3DS is supported for this card, but this card is not enrolled in 3D Secure
    yield ['pm_card_visa', TRUE, PaymentIntent::STATUS_REQUIRES_CONFIRMATION, PaymentIntent::STATUS_SUCCEEDED];
    // 3DS is not supported on this card and cannot be invoked.
    yield ['pm_card_amex_threeDSecureNotSupported', TRUE, PaymentIntent::STATUS_REQUIRES_CONFIRMATION, PaymentIntent::STATUS_SUCCEEDED];
  }

  /**
   * Data provider for testing payment intent updates.
   *
   * @return \Generator
   *   The test data.
   */
  public function dataProviderOrderSync() {
    yield [FALSE];
    yield [TRUE];
  }

}
