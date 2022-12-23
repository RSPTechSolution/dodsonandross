<?php

namespace Drupal\s3fs\Batch;

/**
 * Performs a refresh of the s3fs_file table with data from S3 bucket.
 *
 * @package Drupal\s3fs\Batch
 */
interface S3fsRefreshCacheBatchInterface {

  /**
   * Refreshes the complete list of objects in the S3 bucket.
   *
   * @param array $config
   *   An s3fs configuration array.
   */
  public function execute(array $config);

  /**
   * Initialise a batch builder object.
   *
   * @return \Drupal\Core\Batch\BatchBuilder
   *   The instantiated batch builder.
   */
  public function getBatch();

  /**
   * Batch operation callback to refresh the cached list of S3 bucket objects.
   *
   * @param array $config
   *   The S3 bucket configuration.
   * @param array|\DrushBatchContext $context
   *   Batch context.
   */
  public static function refreshCacheOperation(array $config, &$context);

  /**
   * Finished batch callback.
   *
   * @param bool $success
   *   Whether the batch completed successfully or not.
   * @param array $results
   *   The results key of the batch context.
   * @param array $operations
   *   The operations that were carried out.
   */
  public static function finished(bool $success, array $results, array $operations);

}
