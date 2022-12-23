<?php

namespace Drupal\commerce_license;

/**
 * Provides the interface for the License module's cron.
 *
 * Queues licenses for expiration.
 */
interface CronInterface {

  /**
   * Runs the cron.
   */
  public function run();

}
