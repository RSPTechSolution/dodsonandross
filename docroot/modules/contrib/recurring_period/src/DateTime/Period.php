<?php

namespace Drupal\recurring_period\Datetime;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Represents a period of time with specific start and end dates.
 *
 * Examples:
 * - Oct 14th 14:56:20 - Nov 14th 14:56:20
 * - May 1st 00:00:00 - June 1st 00:00:00
 * - June 1st 00:00:00 - July 1st 00:00:00
 * Periods are contiguous and represent half-open ranges (the end date is not
 * included in the duration).
 *
 * @see http://wrschneider.github.io/2014/01/07/time-intervals-and-other-ranges-should.html
 * @see \Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\BillingScheduleInterface
 */
class Period {

  /**
   * The start date/time.
   *
   * @var \DateTimeImmutable
   */
  protected $startDate;

  /**
   * The end date/time.
   *
   * @var \DateTimeImmutable
   */
  protected $endDate;

  /**
   * The optional label for the period.
   */
  protected $label = '';

  /**
   * Constructs a new Period object.
   *
   * @param \DateTimeImmutable $start_date
   *   The start date/time.
   * @param \DateTimeImmutable $end_date
   *   The end date/time.
   * @param string|null $label
   *   (optional) The label.
   */
  public function __construct(\DateTimeImmutable $start_date, \DateTimeImmutable $end_date, $label = '') {
    $this->startDate = $start_date;
    $this->endDate = $end_date;
    $this->label = $label;
  }

  /**
   * Creates an entity from this period.
   *
   * This requires the entity class for the specified entity type to use
   * \Drupal\recurring_period\Entity\PeriodEntityTrait, or have compatible
   * entity keys.
   *
   * @param string $entity_type_id
   *   The entity type ID of the entity to create.
   * @param array $values
   *   An array of initial values for the new entity. If this contains a value
   *   for the label field, it will be taken over any label from the Period
   *   object.
   *
   * @return \Drupal\Core\Entity\EntityBase
   *   The unsaved entity.
   */
  public function toEntity($entity_type_id, $values = []) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    if ($entity_type->hasKey('date_range')) {
      $values += [
        $entity_type->getKey('date_range') => [
          'value' => $this->getStartDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
          'end_value' => $this->getEndDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        ],
      ];
    }
    else {
      $values += [
        $entity_type->getKey('start_date') => $this->getStartDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        $entity_type->getKey('end_date') => $this->getEndDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ];
    }

    if ($entity_type->hasKey('label')) {
      $values += [
        $entity_type->getKey('label') => $this->getLabel(),
      ];
    }

    return \Drupal::entityTypeManager()->getStorage($entity_type_id)->create($values);
  }

  /**
   * Gets the start date/time.
   *
   * @return \DateTimeImmutable
   *   The start date/time.
   */
  public function getStartDate() {
    return $this->startDate;
  }

  /**
   * Gets the end date/time.
   *
   * @return \DateTimeImmutable
   *   The end date/time.
   */
  public function getEndDate() {
    return $this->endDate;
  }

  /**
   * Gets the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets the duration of the billing period, in seconds.
   *
   * @return int
   *   The duration.
   */
  public function getDuration() {
    return $this->endDate->format('U') - $this->startDate->format('U');
  }

  /**
   * Checks whether the given date/time is contained in the period.
   *
   * @param \\DateTimeImmutable $date
   *   The date/time.
   *
   * @return bool
   *   TRUE if the date/time is contained in the period, FALSE otherwise.
   */
  public function contains(\DateTimeImmutable $date) {
    // Unlike DateTime, DrupalDateTime objects can't be compared directly.
    $timestamp = $date->format('U');
    $starts = $this->startDate->format('U');
    $ends = $this->endDate->format('U');

    return $timestamp >= $starts && $timestamp < $ends;
  }

}
