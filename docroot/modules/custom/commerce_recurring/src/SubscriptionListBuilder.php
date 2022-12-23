<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for subscriptions.
 */
class SubscriptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['type'] = $this->t('Type');
    $header['billing_schedule'] = $this->t('Billing schedule');
    $header['customer'] = $this->t('Customer');
    $header['state'] = $this->t('State');
    $header['start_date'] = $this->t('Start date');
    $header['end_date'] = $this->t('End date');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_recurring\Entity\SubscriptionInterface */
    $row = [
      'id' => $entity->id(),
      'title' => $entity->getTitle(),
      'type' => $entity->getType()->getLabel(),
      'billing_schedule' => $entity->getBillingSchedule()->label(),
      'customer' => $entity->getCustomer()->getDisplayName(),
      'state' => $entity->getState()->getLabel(),
      'start_date' => $entity->getStartDate()->format('M jS Y H:i:s'),
      'end_date' => $entity->getEndDate() ? $entity->getEndDate()->format('M jS Y H:i:s') : '-',
    ];

    return $row + parent::buildRow($entity);
  }

}
