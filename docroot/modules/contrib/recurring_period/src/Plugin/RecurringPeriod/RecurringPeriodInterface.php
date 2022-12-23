<?php

namespace Drupal\recurring_period\Plugin\RecurringPeriod;

use Drupal\recurring_period\Datetime\Period;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for recurring period plugins.
 *
 * A recurring period plugin generates an infinite sequence of dates. These can
 * be used to define repeating events which fall on the dates, or periods of
 * time that run between pairs of dates.
 *
 * So for example, a recurring period could determine when to send out
 * newsletter emails, or expire a published node, or a user's access to the
 * site, and so on.
 *
 * Periods can be either rolling or fixed. A rolling period is anchored to the
 * given date, and so the same plugin will produce different periods for
 * different initial dates. A fixed period is anchored to the calendar, so
 * different initial dates will produce the same sequence.
 *
 * For fixed period plugins, the initial period can be considered in two ways:
 * it can either begin on the given date, or it can be backdated to run from a
 * date in the past, in which case the given date falls within the period.
 *
 * A recurring period plugin can return either date objects or Period objects:
 *
 * - the calculateDate() method returns the next date after the given date. Pass
 *   the returned date back to iterate through the dates. If a fixed period is
 *   to start in the past, use calculateStart() to get the start date.
 * - the getPeriodFromDate() and getPeriodContainingDate() give a period for the
 *   current date, either starting from the current date or backdated. Use the
 *   getNextPeriod() method to advance through the period successively.
 *
 * Furthermore, a Period object can be converted to an entity: see the Period
 * class for details.
 */
interface RecurringPeriodInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Represents an unlimited end time.
   *
   * @var integer
   */
  const UNLIMITED = 0;

  /**
   * Gets the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel();

  /**
   * Gets the plugin description.
   *
   * @return string
   *   The plugin description.
   */
  public function getDescription();

  /**
   * Calculates the end of the previous period.
   *
   * @param \DateTimeImmutable $date
   *   The date and time to begin the period from.
   *
   * @return \DateTimeImmutable|int
   *   The expiry date and time, or RecurringPeriodInterface::UNLIMITED.
   */
  public function calculateStart(\DateTimeImmutable $date);

  /**
   * Calculates the end date and time for the period.
   *
   * @param \DateTimeImmutable $start
   *   The date and time to begin the period from.
   *
   * @return \DateTimeImmutable|int
   *   The expiry date and time, or RecurringPeriodInterface::UNLIMITED.
   */
  public function calculateEnd(\DateTimeImmutable $start);

  /**
   * Calculates the end date and time for the period.
   *
   * @deprecated
   *   Use calculateEnd() instead.
   */
  public function calculateDate(\DateTimeImmutable $start);

  /**
   * Gets a label for the period starting from the given date.
   *
   * This produces a generic label. It may be desirable to override this
   * method in a replacement plugin class.
   *
   * @param \DateTimeImmutable $start
   *   The date and time to begin the period from.
   * @param \DateTimeImmutable $end
   *   The date and time on which the period ends.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function getPeriodLabel(\DateTimeImmutable $start, \DateTimeImmutable $end);

  /**
   * Gets a period object that begins on a given date.
   *
   * @param \DateTimeImmutable $start
   *   The date and time to begin the period from.
   *
   * @return \Drupal\recurring_period\Datetime\Period
   *   The period value object.
   */
  public function getPeriodFromDate(\DateTimeImmutable $start);

  /**
   * Gets a period object that contains the given date.
   *
   * For some period plugins, such as rolling periods, this will return a period
   * identical to that returned by getPeriodFromDate().
   *
   * @param \DateTimeImmutable $date
   *   A date and time that should be contained in the period.
   *
   * @return \Drupal\recurring_period\Datetime\Period
   *   The period value object.
   */
  public function getPeriodContainingDate(\DateTimeImmutable $date);

  /**
   * Calculates the period after the given period.
   *
   * @param \Drupal\recurring_period\Datetime\Period $period
   *   The date and time to begin the period from.
   *
   * @return \Drupal\recurring_period\Datetime\Period
   *   The period value object.
   */
  public function getNextPeriod(Period $period);

}
