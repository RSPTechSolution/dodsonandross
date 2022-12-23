<?php

namespace Drupal\commerce_file;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a service responsible for logging licensed file downloads.
 */
class DownloadLogger implements DownloadLoggerInterface {

  /**
   * The database table name.
   */
  const TABLE_NAME = 'commerce_file_download_log';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Static cache of download counts, keyed by license ID.
   *
   * @var array
   */
  protected $downloadCounts = [];

  /**
   * Constructs a new DownloadLogger object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(Connection $connection, RequestStack $request_stack, TimeInterface $time) {
    $this->connection = $connection;
    $this->requestStack = $request_stack;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function clear(LicenseInterface $license) {
    $this->connection->delete(self::TABLE_NAME)
      ->condition('license_id', $license->id())
      ->execute();
    Cache::invalidateTags($license->getCacheTagsToInvalidate());

    if (isset($this->downloadCounts[$license->id()])) {
      unset($this->downloadCounts[$license->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDownloadCounts(LicenseInterface $license) {
    if (isset($this->downloadCounts[$license->id()])) {
      return $this->downloadCounts[$license->id()];
    }
    $purchased_entity = $license->getPurchasedEntity();
    if (!$purchased_entity->hasField('commerce_file') || $purchased_entity->get('commerce_file')->isEmpty()) {
      throw new \InvalidArgumentException("The purchased entity referenced by the given license does not reference any file.");
    }
    // Collect the file IDS.
    $fids = array_column($purchased_entity->get('commerce_file')->getValue(), 'target_id');

    $query = $this->connection->select(self::TABLE_NAME);
    $query->addField(self::TABLE_NAME, 'fid');
    $query->addExpression("COUNT(fid)", 'count');
    $query
      ->condition('uid', $license->getOwnerId())
      ->condition('license_id', $license->id())
      ->condition('fid', $fids, 'IN')
      ->groupBy(self::TABLE_NAME . '.fid');
    $results = $query->execute()->fetchAllKeyed();

    // Default the download count to 0, in case no downloads were ever logged.
    foreach ($fids as $fid) {
      $this->downloadCounts[$license->id()][$fid] = $results[$fid] ?? 0;
    }

    return $this->downloadCounts[$license->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function log(LicenseInterface $license, FileInterface $file) {
    // Reset the download count static cache for the given license.
    if (isset($this->downloadCounts[$license->id()])) {
      unset($this->downloadCounts[$license->id()]);
    }
    $request = $this->requestStack->getCurrentRequest();
    $this->connection->insert(self::TABLE_NAME)
      ->fields([
        'license_id' => $license->id(),
        'fid' => $file->id(),
        'uid' => $license->getOwnerId(),
        'timestamp' => $this->time->getRequestTime(),
        'ip_address' => $request->getClientIp(),
      ])
      ->execute();
    Cache::invalidateTags($license->getCacheTagsToInvalidate());
  }

}
