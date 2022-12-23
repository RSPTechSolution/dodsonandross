<?php

namespace Drupal\s3fs\StreamWrapper;

use Drupal\Core\Url;

/**
 * Defines a Drupal s3fs stream wrapper class for use with private scheme.
 *
 * Provides support for storing files on the amazon s3 file system with the
 * Drupal file interface.
 */
class PrivateS3fsStream extends S3fsStream {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Private files (s3fs)');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Private files served from Amazon S3.');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $s3_key = str_replace('\\', '/', $this->streamWrapperManager::getTarget($this->uri));
    return Url::fromRoute(
      'system.private_file_download',
      ['filepath' => $s3_key],
      ['absolute' => TRUE, 'path_processing' => FALSE]
    )
      ->toString();
  }

}
