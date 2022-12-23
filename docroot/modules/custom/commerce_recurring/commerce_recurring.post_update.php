<?php

/**
 * @file
 * Post update functions for Commerce Recurring.
 */

/**
 * Sets the next_renewal field on existing active subscriptions.
 */
function commerce_recurring_post_update_1(&$sandbox = NULL) {
  $subscription_storage = \Drupal::entityTypeManager()->getStorage('commerce_subscription');
  if (!isset($sandbox['current_count'])) {
    $query = $subscription_storage->getQuery();
    $query
      ->condition('state', 'active')
      ->accessCheck(FALSE)
      ->notExists('next_renewal');
    $sandbox['total_count'] = $query->count()->execute();
    $sandbox['updated_subscriptions'] = [];
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }
  $query = $subscription_storage->getQuery();
  $query
    ->condition('state', 'active')
    ->accessCheck(FALSE)
    ->notExists('next_renewal')
    ->range(0, 20);

  // Make sure we don't query subscriptions that were already updated.
  if ($sandbox['updated_subscriptions']) {
    $query->condition('subscription_id', $sandbox['updated_subscriptions'], 'NOT IN');
  }

  $subscription_ids = $query->execute();
  if (empty($subscription_ids)) {
    $sandbox['#finished'] = 1;
    return;
  }
  /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface[] $subscriptions */
  $subscriptions = $subscription_storage->loadMultiple($subscription_ids);
  /** @var \Drupal\commerce_order\OrderStorage $order_storage */
  $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
  foreach ($subscriptions as $subscription) {
    $sandbox['updated_subscriptions'][] = $subscription->id();
    $order_ids = $subscription->getOrderIds();
    if (!$order_ids) {
      continue;
    }
    $current_order_id = end($order_ids);
    // We load the unchanged order to make sure it's not refreshed.
    if ($current_order = $order_storage->loadUnchanged($current_order_id)) {
      /** @var \Drupal\commerce_recurring\BillingPeriod $billing_period */
      $billing_period = $current_order->get('billing_period')->first()->toBillingPeriod();
      $subscription->setNextRenewalTime($billing_period->getEndDate()->getTimestamp());
      $subscription->save();
    }
  }
  $sandbox['current_count'] += count($subscriptions);
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}
