<?php

namespace Drupal\commerce_file\EventSubscriber;

use Drupal\commerce_file\DownloadLoggerInterface;
use Drupal\commerce_file\LicenseFileManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * A subscriber to log downloads of licensed files.
 */
class FileResponseSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * The license file manager.
   *
   * @var \Drupal\commerce_file\LicenseFileManagerInterface
   */
  protected $licenseFileManager;

  /**
   * Constructs a new FileResponseSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_file\DownloadLoggerInterface $download_logger
   *   The download logger.
   * @param \Drupal\commerce_file\LicenseFileManagerInterface $license_file_manager
   *   The license file manager.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, DownloadLoggerInterface $download_logger, LicenseFileManagerInterface $license_file_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->downloadLogger = $download_logger;
    $this->licenseFileManager = $license_file_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['logFileDownload', 100];
    return $events;
  }

  /**
   * Logs file downloads for license owners.
   *
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *   The event object.
   */
  public function logFileDownload(TerminateEvent $event) {
    // Not a successful response, nothing to do on our side.
    if (!$event->getResponse()->isSuccessful()) {
      return;
    }
    $headers = $event->getResponse()->headers->all();
    // Check if the custom headers added by our logic in
    // See commerce_file_file_download() are present.
    // If these headers are present, this means a licensed file is being
    // downloaded, therefore we should log the download.
    if (!isset($headers['x-commerce-file-id'], $headers['x-commerce-license-id'])) {
      return;
    }
    /** @var \Drupal\commerce_license\Entity\LicenseInterface $license */
    $license = $this->entityTypeManager->getStorage('commerce_license')->load($headers['x-commerce-license-id'][0]);
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($headers['x-commerce-file-id'][0]);

    // The license or the file could not be loaded, stop here.
    if (!$license || !$file) {
      return;
    }

    // We skip logging file downloads if the download is not initiated by
    // the license owner.
    // When this happens, that means the download was initiated by an admin
    // user who's not affected by file download limits, therefore it is
    // pointless to log a file download that isn't going to be used.
    if (!$this->licenseFileManager->shouldLogDownload($license)) {
      return;
    }
    $this->downloadLogger->log($license, $file);
  }

}
