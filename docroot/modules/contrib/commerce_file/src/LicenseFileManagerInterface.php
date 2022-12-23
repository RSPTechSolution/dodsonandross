<?php

namespace Drupal\commerce_file;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;

interface LicenseFileManagerInterface {

  /**
   * Gets whether the licensed file can be downloaded.
   *
   * The logic first checks whether the current user has the permission to
   * bypass the license control or administer licenses, and then check the
   * download limits.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license entity.
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user to check for. When omitted, the license owner is used instead.
   *
   * @return bool
   *   Whether the given licensed file can be downloaded by the current user.
   */
  public function canDownload(LicenseInterface $license, FileInterface $file, AccountInterface $account = NULL);

  /**
   * Returns active licenses for the given file and the given user, optionally
   * restricted to licenses referencing the given purchasable entity.
   *
   * A file could be sold from multiple products. The user's active licenses
   * for all of them are loaded, and the first eligible one is returned.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check for. If null, the current user is used instead.
   * @param \Drupal\commerce\PurchasableEntityInterface|null $purchasable_entity
   *   (optional) The purchasable entity.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface[]
   *   The active licenses for the given file if found, an empty array
   *   otherwise.
   */
  public function getActiveLicenses(FileInterface $file, AccountInterface $account = NULL, PurchasableEntityInterface $purchasable_entity = NULL);

  /**
   * Gets the download limit for the given license.
   *
   * Note that the logic both checks for the global limit and a limit configured
   * at the product variation level (if overriden).
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license entity.
   *
   * @return int
   *   The download limit (0 for unlimited).
   */
  public function getDownloadLimit(LicenseInterface $license);

  /**
   * Gets whether the given file is licensable.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   *
   * @return bool
   *   Whether the given file is licensable.
   */
  public function isLicensable(FileInterface $file);

  /**
   * Determines whether the download should be logged for the given license.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check for. If null, the current user is used instead.
   *
   * @return bool
   *   Whether the download should be logged.
   */
  public function shouldLogDownload(LicenseInterface $license, AccountInterface $account = NULL);

  /**
   * Resets the internal static cache of licenses.
   */
  public function resetCache();

}
