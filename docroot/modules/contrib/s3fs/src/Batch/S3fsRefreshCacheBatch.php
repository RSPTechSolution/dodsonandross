<?php

namespace Drupal\s3fs\Batch;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Performs a refresh of the s3fs_file table with data from S3 bucket.
 *
 * @package Drupal\s3fs\Batch
 */
class S3fsRefreshCacheBatch implements S3fsRefreshCacheBatchInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute(array $config) {

    // Create batch.
    $batch_builder = $this->getBatch();
    $args = [
      $config,
    ];
    $batch_builder->addOperation([
      S3fsRefreshCacheBatch::class,
      'refreshCacheOperation',
    ], $args);
    batch_set($batch_builder->toArray());

    $batch =& batch_get();

    // Drush integration.
    if (PHP_SAPI === 'cli') {
      $batch['progressive'] = FALSE;
      drush_backend_batch_process();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBatch() {
    return (new BatchBuilder())
      ->setTitle($this->t('Refresh cached list of files S3'))
      ->setFinishCallback([S3fsRefreshCacheBatch::class, 'finished'])
      ->setInitMessage($this->t('The process to refresh the cached list of files is about to start..'))
      ->setProgressMessage($this->t('Processed batch @current out of an unknown total (AWS S3 does not make the total number of files available without looping through all files).'))
      ->setErrorMessage($this->t('Something wrong happened, please check the logs.'));
  }

  /**
   * {@inheritdoc}
   */
  public static function refreshCacheOperation(array $config, &$context) {
    $s3fs = \Drupal::Service('s3fs');
    $s3 = $s3fs->getAmazonS3Client($config);

    if (!isset($context['results']['progress'])) {
      $context['results']['progress'] = 0;
      $context['results']['time_start'] = time();

      $context['sandbox']['NextKeyMarker'] = '';
      $context['sandbox']['NextVersionIdMarker'] = '';
      $context['sandbox']['ContinuationToken'] = '';

      $context['sandbox']['list_object_versions_args'] = $s3fs::getListObjectVersionArgs($config);
      $context['sandbox']['current_page'] = 0;
      $context['sandbox']['folders'] = $s3fs->getExistingFolders();
      $context['sandbox']['estimated_max'] = \Drupal::database()
        ->select('s3fs_file')
        ->countQuery()
        ->execute()
        ->fetchField();
      $s3fs->setupTempTable();
    }

    // General batch settings.
    $per_batch = 1000;

    // Make API call.
    $args = $context['sandbox']['list_object_versions_args'];
    $args['MaxKeys'] = $per_batch;

    if (empty($config['disable_version_sync'])) {
      if (!empty($context['sandbox']['NextKeyMarker'])) {
        $args['KeyMarker'] = $context['sandbox']['NextKeyMarker'];
      }
      if (!empty($context['sandbox']['NextVersionIdMarker'])) {
        $args['VersionIdMarker'] = $context['sandbox']['NextVersionIdMarker'];
      }
      // Get the object versions from the S3 API.
      $response = $s3->listObjectVersions($args);
    }
    else {
      if (!empty($context['sandbox']['ContinuationToken'])) {
        $args['ContinuationToken'] = $context['sandbox']['ContinuationToken'];
      }
      $response = $s3->listObjectsV2($args);
    }

    $finalise_cache_refresh = TRUE;
    if ($response->count()) {
      $result = $response->toArray();

      // Process the objects in this batch.
      $file_metadata_list = [];

      if (array_key_exists('Versions', $result)) {
        foreach ($result['Versions'] as $s3_metadata) {
          $s3fs->getObjectMetadata($file_metadata_list, $context['sandbox']['folders'], $s3_metadata, $config);
          $context['results']['progress']++;
        }
      }
      elseif (array_key_exists('Contents', $result)) {
        foreach ($result['Contents'] as $s3_metadata) {
          $s3fs->getObjectMetadata($file_metadata_list, $context['sandbox']['folders'], $s3_metadata, $config);
          $context['results']['progress']++;
        }
      }

      // Store the results of this batch in the file meta data table.
      $s3fs->writeTemporaryMetadata($file_metadata_list, $context['sandbox']['folders']);

      // The API indicates that there are more results through this truncated
      // flag.
      if ($result['IsTruncated'] === TRUE) {
        $finalise_cache_refresh = FALSE;

        // Store pager data for use in the start of the next
        // chunk in the batch.
        if (array_key_exists('NextKeyMarker', $result)) {
          $context['sandbox']['NextKeyMarker'] = $result['NextKeyMarker'];
        }
        if (array_key_exists('NextVersionIdMarker', $result)) {
          $context['sandbox']['NextVersionIdMarker'] = $result['NextVersionIdMarker'];
        }
        if (array_key_exists('NextContinuationToken', $result)) {
          $context['sandbox']['ContinuationToken'] = $result['NextContinuationToken'];
        }

        // Estimate the percentage based on the previous count.
        if ($context['sandbox']['estimated_max'] && $context['sandbox']['estimated_max'] > $context['results']['progress']) {
          $context['finished'] = $context['results']['progress'] / $context['sandbox']['estimated_max'];
          $context['message'] = new TranslatableMarkup('@percent_progress% (@progress/@estimated_max) time elapsed @elapsed_time (hh:mm:ss)', [
            '@estimated_max' => $context['sandbox']['estimated_max'],
            '@percent_progress' => round($context['finished'] * 100),
            '@progress' => $context['results']['progress'],
            '@elapsed_time' => static::getElapsedTimeFormatted($context['results']['time_start']),
          ]);
        }
        else {

          // Just set an arbitrary number as we are unable to calculate how
          // many results there will be in total.
          $context['finished'] = 0.75;
          $context['message'] = new TranslatableMarkup('Iterating through the S3 bucket beyond the previously found total items (currently @progress), time elapsed @elapsed_time (hh:mm:ss)', [
            '@elapsed_time' => static::getElapsedTimeFormatted($context['results']['time_start']),
            '@progress' => $context['results']['progress'],
          ]);
        }
      }
    }

    if ($finalise_cache_refresh) {

      // Store the folders in the database.
      $s3fs->writeFolders($context['sandbox']['folders']);

      // Set the final tables.
      $s3fs->setTables();

      // Clear every s3fs entry in the Drupal cache.
      Cache::invalidateTags([S3FS_CACHE_TAG]);

      // Mark batch as completed.
      $context['finished'] = 1;
    }
  }

  /**
   * Get the elapsed time since start of the batch process.
   *
   * @param int $time_start
   *   Unix timestamp of when function started.
   *
   * @return string
   *   Elapsed time as a string format of 'hh:mm:ss'.
   */
  protected static function getElapsedTimeFormatted($time_start) {
    $time_elapsed_seconds = time() - $time_start;
    return gmdate('H:i:s', $time_elapsed_seconds);
  }

  /**
   * {@inheritdoc}
   */
  public static function finished(bool $success, array $results, array $operations) {
    \Drupal::messenger()
      ->addStatus(new TranslatableMarkup('The cached list of files has been refreshed in @elapsed_time (hh:mm:ss).', [
        '@elapsed_time' => static::getElapsedTimeFormatted($results['time_start']),
      ]));

  }

}
