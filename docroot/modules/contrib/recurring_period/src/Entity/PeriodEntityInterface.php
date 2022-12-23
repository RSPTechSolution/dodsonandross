<?php

namespace Drupal\recurring_period\Entity;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Interface for entities that represent a time period.
 *
 * @see \Drupal\recurring_period\Entity\PeriodEntityTrait
 */
interface PeriodEntityInterface {

  /**
   * Gets the start date/time.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The start date/time.
   */
  public function getStartDate();
  /**
   * Gets the end date/time.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The end date/time.
   */
  public function getEndDate();

  /**
   * Gets the duration of the period, in seconds.
   *
   * @return int
   *   The duration.
   */
  public function getDuration();

  /**
   * Checks whether the given date/time is contained in the period.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date/time.
   *
   * @return bool
   *   TRUE if the date/time is contained in the period, FALSE otherwise.
   */
  public function contains(DrupalDateTime $date);

}
