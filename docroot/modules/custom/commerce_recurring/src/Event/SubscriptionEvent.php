<?php

namespace Drupal\commerce_recurring\Event;

use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the subscription event.
 *
 * @see \Drupal\commerce_recurring\Event\SubscriptionEvents
 */
class SubscriptionEvent extends Event {

  /**
   * The subscription.
   *
   * @var \Drupal\commerce_recurring\Entity\SubscriptionInterface
   */
  protected $subscription;

  /**
   * Constructs a new SubscriptionEvent.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   */
  public function __construct(SubscriptionInterface $subscription) {
    $this->subscription = $subscription;
  }

  /**
   * Gets the subscription.
   *
   * @return \Drupal\commerce_recurring\Entity\SubscriptionInterface
   *   The subscription.
   */
  public function getSubscription() {
    return $this->subscription;
  }

}
