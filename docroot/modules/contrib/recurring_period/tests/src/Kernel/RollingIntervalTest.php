<?php

namespace Drupal\Tests\recurring_period\Kernel;

use Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface;

/**
 * Tests the rolling interval plugin.
 *
 * @group recurring_period
 */
class RollingIntervalTest extends RecurringPeriodTestBase {

  /**
   * Tests the TODO.
   *
   * @dataProvider rollingIntervalProvider
   */
  public function testRollingInterval($configuration, $start_date, $period_dates) {
    $rolling_interval_plugin = $this->recurringPeriodManager->createInstance('rolling_interval', $configuration);

    $timezone_london = new \DateTimeZone('Europe/London');

    $start_date_time = new \DateTimeImmutable($start_date, $timezone_london);

    $period = $rolling_interval_plugin->getPeriodFromDate($start_date_time);

    $first_period_dates = array_shift($period_dates);
    $this->assertEquals($first_period_dates[0], $period->getStartDate()->format(\DateTimeInterface::ATOM));
    $this->assertEquals($first_period_dates[1], $period->getEndDate()->format(\DateTimeInterface::ATOM));

    while ($next_period_dates = array_shift($period_dates)) {
      // TODO: also check the value of calculateDate().

      $period = $rolling_interval_plugin->getNextPeriod($period);

      $this->assertEquals($next_period_dates[0], $period->getStartDate()->format(\DateTimeInterface::ATOM));
      $this->assertEquals($next_period_dates[1], $period->getEndDate()->format(\DateTimeInterface::ATOM));
    }
  }

  public function rollingIntervalProvider() {
    return [
      '1 month' => [
        // Configuration.
        [
          'interval' => [
            'period' => 'month',
            'interval' => 1,
          ],
        ],
        // Start date.
        '2017-01-01T09:00:00',
        // Successive period dates.
        [
          ['2017-01-01T09:00:00+00:00', '2017-02-01T09:00:00+00:00'],
          ['2017-02-01T09:00:00+00:00', '2017-03-01T09:00:00+00:00'],
          // The end of this period is after the change to DST, so the offset
          // changes from 0 to +1.
          ['2017-03-01T09:00:00+00:00', '2017-04-01T09:00:00+01:00'],
          ['2017-04-01T09:00:00+01:00', '2017-05-01T09:00:00+01:00'],
          ['2017-05-01T09:00:00+01:00', '2017-06-01T09:00:00+01:00'],
          ['2017-06-01T09:00:00+01:00', '2017-07-01T09:00:00+01:00'],
          ['2017-07-01T09:00:00+01:00', '2017-08-01T09:00:00+01:00'],
          ['2017-08-01T09:00:00+01:00', '2017-09-01T09:00:00+01:00'],
          ['2017-09-01T09:00:00+01:00', '2017-10-01T09:00:00+01:00'],
          ['2017-10-01T09:00:00+01:00', '2017-11-01T09:00:00+00:00'],
          ['2017-11-01T09:00:00+00:00', '2017-12-01T09:00:00+00:00'],
          ['2017-12-01T09:00:00+00:00', '2018-01-01T09:00:00+00:00'],
        ],
      ],
      '2 weeks' => [
        // Configuration.
        [
          'interval' => [
            'period' => 'week',
            'interval' => 2,
          ],
        ],
        // Start date.
        '2017-01-01T09:00:00',
        // Successive period dates.
        [
          ['2017-01-01T09:00:00+00:00', '2017-01-15T09:00:00+00:00'],
          ['2017-01-15T09:00:00+00:00', '2017-01-29T09:00:00+00:00'],
        ],
      ],
    ];
  }

  /**
   * Tests a 2 week interval in UTC.
   */
  public function test2WeekInterval() {
    $timezone_utc = new \DateTimeZone('UTC');

    /** @var RecurringPeriodInterface $plugin */
    $plugin = $this->recurringPeriodManager->createInstance('rolling_interval', [
      'interval' => [
        'period' => 'week',
        'interval' => 2,
      ],
    ]);

    $start_date = new \DateTimeImmutable('2017-01-01T09:00:00', $timezone_utc);
    $expected_end_date = new \DateTimeImmutable('2017-01-15T09:00:00', $timezone_utc);
    $actual_end_date = $plugin->calculateDate($start_date);
    $this->assertEquals($expected_end_date, $actual_end_date);

    // The timestamp difference should be 14*86400 seconds.
    $expected_timestamp_diff = 14 * 86400;
    $actual_timestamp_diff = (int) $actual_end_date->format('U') - (int)$start_date->format('U');
    $this->assertEquals($expected_timestamp_diff, $actual_timestamp_diff);
  }

  /**
   * Tests a 2 week interval spanning a daylight saving change.
   */
  public function test2WeekIntervalSpanningDSTChange() {
    $timezone_london = new \DateTimeZone('Europe/London');

    /** @var RecurringPeriodInterface $plugin */
    $plugin = $this->recurringPeriodManager->createInstance('rolling_interval', [
      'interval' => [
        'period' => 'week',
        'interval' => 2,
      ],
    ]);

    $start_date = new \DateTimeImmutable('2017-10-17T09:00:00', $timezone_london);
    $expected_end_date = new \DateTimeImmutable('2017-10-31T09:00:00', $timezone_london);
    $actual_end_date = $plugin->calculateDate($start_date);
    $this->assertEquals($expected_end_date, $actual_end_date);

    // The timestamp difference should take into account the extra hour
    // because of the the switch from DST.
    $expected_timestamp_diff = 14 * 86400 + 3600;
    $actual_timestamp_diff = (int) $actual_end_date->format('U') - (int)$start_date->format('U');
    $this->assertEquals($expected_timestamp_diff, $actual_timestamp_diff);
  }

}
