<?php

namespace Drupal\commerce_file;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\file\FileInterface;

/**
 * Interface for the download logger, responsible for logging file downloads.
 */
interface DownloadLoggerInterface {

  /**
   * Clears the download log for the given license.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license.
   */
  public function clear(LicenseInterface $license);

  /**
   * Gets the download counts for the given license and its files.
   *
   * Note that the download counts are returned for the license owner since we
   * we only record downloads from the license owner.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license.
   *
   * @return array
   *   The download counts, keyed by fid.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the given license references a purchased entity that does not
   *   reference any file.
   */
  public function getDownloadCounts(LicenseInterface $license);

  /**
   * Logs the download of the given file, for the given license.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license.
   * @param \Drupal\file\FileInterface $file
   *   The downloaded file.
   */
  public function log(LicenseInterface $license, FileInterface $file);

}
