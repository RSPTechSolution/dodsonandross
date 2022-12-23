<?php

namespace Drupal\commerce_stripe\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the transaction data event.
 *
 * This enables other modules to add transaction data and metadata to the
 * transaction that will be sent to Stripe.
 *
 * @see \Drupal\commerce_stripe\Event\StripeEvents
 */
class TransactionDataEvent extends Event {

  /**
   * The payment.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * The transaction data.
   *
   * @var array
   */
  protected $transactionData = [];

  /**
   * The transaction metadata.
   *
   * @var array
   */
  protected $metadata = [];

  /**
   * Constructs a new TransactionDataEvent object.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   */
  public function __construct(PaymentInterface $payment) {
    $this->payment = $payment;
  }

  /**
   * Get the transaction data.
   *
   * @return array
   *   The transaction data.
   *
   * @deprecated Transaction data is no longer influenced by this event.
   */
  public function getTransactionData() {
    return $this->transactionData;
  }

  /**
   * Sets the transaction data data array.
   *
   * @param array $transaction_data
   *   The transaction data.
   *
   * @return $this
   *
   * @deprecated Transaction data is no longer influenced by this event.
   */
  public function setTransactionData(array $transaction_data) {
    $this->transactionData = $transaction_data;
    return $this;
  }

  /**
   * Get the transaction metadata.
   *
   * @return array
   *   The metadata.
   */
  public function getMetadata() {
    return $this->metadata;
  }

  /**
   * Sets the metadata array.
   *
   * @param array $metadata
   *   The metadata.
   *
   * @return $this
   */
  public function setMetadata(array $metadata) {
    $this->metadata = $metadata;
    return $this;
  }

  /**
   * Get the payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment.
   */
  public function getPayment() {
    return $this->payment;
  }

}
