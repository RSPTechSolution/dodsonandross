<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * Tests the subscription lifecycle.
 *
 * @group commerce_recurring
 */
class SubscriptionLifecycleTest extends RecurringKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $order_type = OrderType::load('default');
    $order_type->setWorkflowId('order_default_validation');
    $order_type->save();
  }

  /**
   * Tests the subscription lifecycle, without a free trial.
   *
   * Placing an initial order should create an active subscription.
   * Canceling the initial order should cancel the subscription.
   */
  public function testLifecycle() {
    $configuration = $this->billingSchedule->getPluginConfiguration();
    unset($configuration['trial_interval']);
    $this->billingSchedule->setPluginConfiguration($configuration);
    $this->billingSchedule->save();

    $first_order_item = OrderItem::create([
      'type' => 'test',
      'title' => 'I promise not to start a subscription',
      'unit_price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
      'quantity' => 1,
    ]);
    $first_order_item->save();
    $second_order_item = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->variation,
      'unit_price' => [
        'number' => '2.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '3',
    ]);
    $second_order_item->save();
    $initial_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$first_order_item, $second_order_item],
      'state' => 'draft',
    ]);
    $initial_order->save();

    // Confirm that placing the initial order with no payment method doesn't
    // create the subscription.
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();
    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(0, $subscriptions);

    // Confirm that placing an order with a payment method creates an
    // active subscription.
    $initial_order->set('state', 'draft');
    $initial_order->set('payment_method', $this->paymentMethod);
    $initial_order->save();
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();
    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(1, $subscriptions);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    $this->assertEquals('active', $subscription->getState()->getId());
    $this->assertEquals($this->store->id(), $subscription->getStoreId());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertEquals($this->user->id(), $subscription->getCustomerId());
    $this->assertEquals($this->paymentMethod->id(), $subscription->getPaymentMethod()->id());
    $this->assertEquals($this->variation->id(), $subscription->getPurchasedEntityId());
    $this->assertEquals($this->variation->getOrderItemTitle(), $subscription->getTitle());
    $this->assertEquals('3', $subscription->getQuantity());
    $this->assertEquals($this->variation->getPrice(), $subscription->getUnitPrice());
    $this->assertEquals($initial_order->id(), $subscription->getInitialOrderId());
    $orders = $subscription->getOrders();
    $this->assertCount(1, $orders);
    $order = reset($orders);
    $this->assertFalse($order->getTotalPrice()->isZero());
    $this->assertEquals('recurring', $order->bundle());
    // Confirm that the recurring order has an order item for the subscription.
    $order_items = $order->getItems();
    $this->assertCount(1, $order_items);
    $order_item = reset($order_items);
    $this->assertEquals($subscription->id(), $order_item->get('subscription')->target_id);

    // Test initial order cancellation.
    $initial_order->getState()->applyTransitionById('cancel');
    $initial_order->save();
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals('canceled', $subscription->getState()->getId());
  }

  /**
   * Tests the subscription lifecycle, with a free trial.
   *
   * Placing an initial order should create a trial subscription.
   * Canceling the initial order should cancel the trial.
   */
  public function testLifecycleWithTrial() {
    $first_order_item = OrderItem::create([
      'type' => 'test',
      'title' => 'I promise not to start a subscription',
      'unit_price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
      'quantity' => 1,
    ]);
    $first_order_item->save();
    $second_order_item = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->variation,
      'unit_price' => [
        'number' => '2.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '3',
    ]);
    $second_order_item->save();
    $initial_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$first_order_item, $second_order_item],
      'state' => 'draft',
    ]);
    $initial_order->save();

    // Confirm that placing the initial order creates a trial subscription,
    // even without a payment method.
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();
    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(1, $subscriptions);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    $this->assertEquals('trial', $subscription->getState()->getId());
    $this->assertEquals($this->store->id(), $subscription->getStoreId());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertEquals($this->user->id(), $subscription->getCustomerId());
    $this->assertNull($subscription->getPaymentMethod());
    $this->assertEquals($this->variation->id(), $subscription->getPurchasedEntityId());
    $this->assertEquals($this->variation->getOrderItemTitle(), $subscription->getTitle());
    $this->assertEquals('3', $subscription->getQuantity());
    $this->assertEquals($this->variation->getPrice(), $subscription->getUnitPrice());
    $this->assertEquals($initial_order->id(), $subscription->getInitialOrderId());
    $this->assertNotEmpty($subscription->getTrialStartTime());
    $this->assertNotEmpty($subscription->getTrialEndTime());
    $this->assertEquals(864000, $subscription->getTrialEndTime() - $subscription->getTrialStartTime());
    $orders = $subscription->getOrders();
    $this->assertCount(1, $orders);
    $order = reset($orders);
    $this->assertEquals('recurring', $order->bundle());
    $this->assertTrue($order->getTotalPrice()->isZero());
    // Confirm that the recurring order has an order item for the subscription.
    $order_items = $order->getItems();
    $this->assertCount(1, $order_items);
    $order_item = reset($order_items);
    $this->assertEquals($subscription->id(), $order_item->get('subscription')->target_id);

    // Test initial order cancellation.
    $initial_order->getState()->applyTransitionById('cancel');
    $initial_order->save();
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals('canceled', $subscription->getState()->getId());
  }

}
