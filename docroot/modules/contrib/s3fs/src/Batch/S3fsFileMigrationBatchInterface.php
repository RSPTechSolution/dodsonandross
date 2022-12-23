<?php

namespace Drupal\s3fs\Batch;

/**
 * Interface for SFS3fsFileMigrationBatch.
 *
 *  Migrates files from public:// and private:// to S3 bucket.
 *
 * @package Drupal\s3fs\Batch
 */
interface S3fsFileMigrationBatchInterface {

  /**
   * Copies all the local files from the specified file system into S3.
   *
   * @param array $config
   *   An s3fs configuration array.
   * @param string $scheme
   *   Allowed values: 'public' | 'private'
   *   Scheme to copy.
   * @param array $uploadOptions
   *   Options to control upload operations.
   */
  public function execute(array $config, $scheme, array $uploadOptions);

  /**
   * Scans a given directory.
   *
   * @param string $dir
   *   The directory to be scanned.
   *
   * @return array
   *   Array of file paths.
   */
  public function dirScan($dir);

  /**
   * Return batch definition.
   *
   * @return array
   *   Array of batch definition.
   */
  public function getBatch();

  /**
   * Batch operation callback that copy files to S3 File System.
   *
   * @param array $config
   *   Array of configuration settings from which to configure the client.
   * @param array $file_paths
   *   Array with file paths to process.
   * @param int $total
   *   Total number of files to process in batch.
   * @param string $source_folder
   *   Folder from copy the file.
   * @param string $target_folder
   *   Folder to copy the file.
   * @param string $scheme
   *   Scheme from copy files. E.g.: public.
   * @param array $uploadOptions
   *   Options to control upload operations.
   * @param array|\DrushBatchContext $context
   *   Batch context.
   */
  public static function copyOperation(array $config, array $file_paths, $total, $source_folder, $target_folder, $scheme, array $uploadOptions, &$context);

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
  public static function finished($success, array $results, array $operations);

}
