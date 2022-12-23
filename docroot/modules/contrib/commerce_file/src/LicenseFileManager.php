<?php

namespace Drupal\commerce_file;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;

/**
 * Provides a service for managing licensed files.
 */
class LicenseFileManager implements LicenseFileManagerInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file download logger.
   *
   * @var \Drupal\commerce_file\DownloadLoggerInterface
   */
  protected $downloadLogger;

  /**
   * Static cache of licensable files.
   *
   * @var array
   */
  protected $isLicensable = [];

  /**
   * Static cache of eligible licenses, keyed by account ID and file ID.
   *
   * @var \Drupal\commerce_license\Entity\LicenseInterface[]
   */
  protected $licenses = [];

  /**
   * Constructs a new LicenseFileManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_file\DownloadLoggerInterface $download_logger
   *   The download logger.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, DownloadLoggerInterface $download_logger) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->downloadLogger = $download_logger;
  }

  /**
   * {@inheritdoc}
   */
  public function canDownload(LicenseInterface $license, FileInterface $file, AccountInterface $account = NULL) {
    $account = $account ?: $license->getOwner();

    // When the current user has the permission to bypass license control or
    // administer licenses, the file can be downloaded.
    if ($account->hasPermission('bypass license control') || $account->hasPermission('administer commerce_license')) {
      return TRUE;
    }

    // If the current account cannot access the license, or if the license
    // is not active, do not allow the download.
    if (!$license->access('view', $account) || $license->getState()->getId() !== 'active') {
      return FALSE;
    }

    // Now, check if a download limit is configured, either globally or for the
    // product variation referenced by the license.
    $download_limit = $this->getDownloadLimit($license);

    // If no download limit is configured, allow the download.
    if (!$download_limit) {
      return TRUE;
    }
    $counts = $this->downloadLogger->getDownloadCounts($license);
    if (isset($counts[$file->id()]) && $counts[$file->id()] >= $download_limit) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveLicenses(FileInterface $file, AccountInterface $account = NULL, PurchasableEntityInterface $purchasable_entity = NULL) {
    $account = $account ?: $this->currentUser;
    // Static cache key of active licenses.
    $cache_key_components = [$file->id(), $account->id()];
    if ($purchasable_entity) {
      $cache_key_components[] = $purchasable_entity->id();
    }
    $cache_key = implode(':', $cache_key_components);

    if (array_key_exists($cache_key, $this->licenses)) {
      return $this->licenses[$cache_key];
    }
    $this->licenses[$cache_key] = [];

    if ($purchasable_entity) {
      $product_variation_ids = [$purchasable_entity->id()];
    }
    else {
      /** @var \Drupal\commerce_product\ProductVariationStorageInterface $product_variation_storage */
      $product_variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');

      // First, look for product variations referencing the given file.
      $results = $product_variation_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('commerce_file.target_id', $file->id())
        ->execute();

      if (!$results) {
        return [];
      }

      $product_variation_ids = array_keys($results);
    }
    /** @var \Drupal\commerce_license\LicenseStorageInterface $license_storage */
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    $results = $license_storage->getQuery()
      ->condition('type', 'commerce_file')
      ->condition('state', 'active')
      ->condition('product_variation', $product_variation_ids, 'IN')
      ->condition('uid', $account->id())
      ->accessCheck(FALSE)
      ->sort('license_id')
      ->execute();

    if (!$results) {
      return [];
    }
    /** @var \Drupal\commerce_license\Entity\LicenseInterface[] $licenses */
    $this->licenses[$cache_key] = array_values($license_storage->loadMultiple(array_keys($results)));

    return $this->licenses[$cache_key];
  }

  /**
   * {@inheritdoc}
   */
  public function getDownloadLimit(LicenseInterface $license) {
    $download_limit = 0;
    // First, check whether a global limit is configured.
    $settings = $this->configFactory->get('commerce_file.settings')->get();
    if (!empty($settings['enable_download_limit'])) {
      $download_limit = $settings['download_limit'];
    }

    // Check the file download limit at the license level if specified.
    if ($license->hasField('file_download_limit') && !$license->get('file_download_limit')->isEmpty()) {
      $download_limit = $license->get('file_download_limit')->value;
    }

    return $download_limit;
  }

  /**
   * {@inheritdoc}
   */
  public function isLicensable(FileInterface $file) {
    if (isset($this->isLicensable[$file->id()])) {
      return $this->isLicensable[$file->id()];
    }
    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $product_variation_storage */
    $product_variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    $query = $product_variation_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('commerce_file.target_id', $file->id())
      ->count();

    $this->isLicensable[$file->id()] = (bool) $query->execute() > 0;
    return $this->isLicensable[$file->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function shouldLogDownload(LicenseInterface $license, AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    if ($account->hasPermission('bypass license control') || $account->hasPermission('administer commerce_license')) {
      return FALSE;
    }

    return $account->id() == $license->getOwnerId();
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->isLicensable = [];
    $this->licenses = [];
  }

}
