<?php

namespace Drupal\commerce_license;

use Drupal\advancedqueue\Job;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default cron implementation.
 */
class Cron implements CronInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new Cron object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $time = $this->time->getRequestTime();
    $license_ids = $this->getLicensesToExpire($time);

    if ($license_ids) {
      $queue_storage = $this->entityTypeManager->getStorage('advancedqueue_queue');
      /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
      $queue = $queue_storage->load('commerce_license');
      foreach ($license_ids as $license_id) {
        // Create a job and queue each one up.
        $expire_license_job = Job::create('commerce_license_expire', [
          'license_id' => $license_id,
        ]);
        $queue->enqueueJob($expire_license_job);
      }
    }
  }

  /**
   * Gets IDs of licenses that are set to expire.
   *
   * @param int $time
   *   Time to check against license expiration.
   *
   * @return array
   *   IDs of matching commerce_license entities.
   */
  protected function getLicensesToExpire($time) {
    // Get all of the active expired licenses.
    $query = $this->entityTypeManager->getStorage('commerce_license')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('state', 'active')
      ->condition('expires', $time, '<=')
      ->condition('expires', 0, '<>');

    $license_ids = $query->execute();
    return $license_ids;
  }

}
