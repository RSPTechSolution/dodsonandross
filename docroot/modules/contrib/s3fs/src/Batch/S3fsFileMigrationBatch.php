<?php

namespace Drupal\s3fs\Batch;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\s3fs\S3fsServiceInterface;

/**
 * Batch migrate files to a S3 bucket.
 *
 *  Copies files from public:// and private:// to the bucket.
 *
 * @package Drupal\s3fs\Batch
 */
class S3fsFileMigrationBatch implements S3fsFileMigrationBatchInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute(array $config, $scheme, array $uploadOptions) {
    if ($scheme === 'public') {
      $source_folder = realpath(PublicStream::basePath());
      $target_folder = !empty($config['public_folder']) ? $config['public_folder'] . '/' : 's3fs-public/';
    }
    elseif ($scheme === 'private') {
      $source_folder = PrivateStream::basePath() ? PrivateStream::basePath() : '';
      $source_folder_real = realpath($source_folder);
      if (empty($source_folder) || empty($source_folder_real)) {
        $this->messenger()->addError($this->t('Private file system base path is unknown. Unable to perform S3 copy.'));
        return;
      }
      $target_folder = !empty($config['private_folder']) ? $config['private_folder'] . '/' : 's3fs-private/';
    }
    else {
      $this->messenger()->addError($this->t('Scheme @scheme is not allowed', ['%scheme' => $scheme]));
      return;
    }

    if (!empty($config['root_folder'])) {
      $target_folder = $config['root_folder'] . '/' . $target_folder;
    }

    $file_paths = $this->dirScan($source_folder);

    if (!empty($file_paths)) {
      // Create batch.
      $batch = $this->getBatch();

      $total = count($file_paths);
      $file_paths_chunks = array_chunk($file_paths, 50, TRUE);
      unset($file_paths);

      foreach ($file_paths_chunks as $chunk) {
        $batch['operations'][] = [
          [
            get_class($this),
            'copyOperation',
          ],
          [
            $config,
            $chunk,
            $total,
            $source_folder,
            $target_folder,
            $scheme,
            $uploadOptions,
          ],
        ];
      }

      batch_set($batch);

      $batch =& batch_get();

    }
    else {
      $this->messenger()->addMessage($this->t("There weren't files to migrate."), 'ok');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dirScan($dir) {
    $output = [];
    $files = scandir($dir);
    foreach ($files as $file) {
      $path = "$dir/$file";

      if ($file != '.' && $file != '..') {
        // In case they put their private root folder inside their public one,
        // skip it. When listing the private file system contents, $path will
        // never trigger this.
        if ($path == realpath(PrivateStream::basePath() ? PrivateStream::basePath() : '')) {
          continue;
        }

        if (is_dir($path)) {
          $output = array_merge($output, $this->dirScan($path));
        }
        else {
          $output[] = $path;
        }
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatch() {
    return [
      'operations' => [],
      'finished' => [get_class($this), 'finished'],
      'title' => $this->t('Copy files to S3'),
      'init_message' => $this->t('The copying process is about to start..'),
      'progress_message' => $this->t('Processed batch @current out of @total.'),
      'error_message' => $this->t('Something wrong happened, please check the logs.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function copyOperation(array $config, array $file_paths, $total, $source_folder, $target_folder, $scheme, array $uploadOptions, &$context) {
    $s3fs = \Drupal::service('s3fs');
    $s3 = $s3fs->getAmazonS3Client($config);
    $streamWrapper = \Drupal::service('stream_wrapper.s3fs');
    $mimeGuesser = \Drupal::service('s3fs.mime_type.guesser');

    if (!isset($context['results']['scheme'])) {
      $context['results']['scheme'] = $scheme;
      $context['results']['progress'] = 0;
      $context['results']['percent_progress'] = 0;
      $context['results']['total'] = $total;
      $context['results']['time_start'] = time();
      $context['results']['errors'] = [];
    }
    foreach ($file_paths as $path) {
      $relative_path = substr_replace($path, '', 0, strlen($source_folder) + 1);
      $key_path = $target_folder . $relative_path;
      $uri = $scheme . '://' . $relative_path;

      if (mb_strlen($uri) > S3fsServiceInterface::MAX_URI_LENGTH) {
        $context['results']['errors'][] = new TranslatableMarkup(
          'Path @path is too long, upload skipped.',
          ['@path' => $uri]
        );
        // Update our progress information.
        self::updateProgress($context);
        continue;
      }

      // Prohibit objects with UTF8 4-byte characters due to SQL limits.
      // @see https://www.drupal.org/project/s3fs/issues/3266062
      if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $uri)) {
        $context['results']['errors'][] = new TranslatableMarkup(
          'Path @path contains UTF8 4-byte characters, upload skipped.',
          ['@path' => $uri]
        );
        // Update our progress information.
        self::updateProgress($context);
        continue;
      }

      $uploadConditions = [];
      if (isset($uploadOptions['upload_conditions'])) {
        $uploadConditions = $uploadOptions['upload_conditions'];
      }

      if (static::isFileAlreadyUploaded($path, $uri, $uploadConditions)) {
        self::updateProgress($context);
        continue;
      }

      if (method_exists($mimeGuesser, 'guessMimeType')) {
        $contentType = $mimeGuesser->guessMimeType($key_path);
      }
      else {
        $contentType = $mimeGuesser->guess($key_path);
      }

      $uploadParams = [
        'Bucket' => $config['bucket'],
        'Key' => $key_path,
        'SourceFile' => $path,
        'ContentType' => $contentType,
      ];

      if (!empty($config['encryption'])) {
        $uploadParams['ServerSideEncryption'] = $config['encryption'];
      }

      $uploadAsPrivate = Settings::get('s3fs.upload_as_private');

      if ($scheme !== 'private' && !$uploadAsPrivate) {
        $uploadParams['ACL'] = 'public-read';
      }

      // Set the Cache-Control header, if the user specified one.
      if (!empty($config['cache_control_header'])) {
        $uploadParams['CacheControl'] = $config['cache_control_header'];
      }

      \Drupal::moduleHandler()->alter('s3fs_upload_params', $uploadParams);

      try {
        $s3->putObject($uploadParams);
      }
      catch (\Exception $e) {
        $context['results']['errors'][] = new TranslatableMarkup(
          'Failed to upload @file',
          ['@file' => $path]
        );
        self::updateProgress($context);
        continue;
      }

      $streamWrapper->writeUriToCache($uri);

      self::updateProgress($context);
    }
  }

  /**
   * Copy Operation progress message generator.
   *
   * @param array|\DrushBatchContext $context
   *   Batch context.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Markup containing progress message.
   */
  private static function getCopyOperationMessage($context) {
    return new TranslatableMarkup('@percent_progress% (@progress/@total) time elapsed @elapsed_time (hh:mm:ss)', [
      '@percent_progress' => $context['results']['percent_progress'],
      '@progress' => $context['results']['progress'],
      '@total' => $context['results']['total'],
      '@elapsed_time' => static::getElapsedTimeFormatted($context['results']['time_start']),
    ]);
  }

  /**
   * Formatted string of time elapsed on batch process.
   *
   * @param int $time_start
   *   Time batch started as seconds since unix epoch.
   *
   * @return string
   *   Zero padded elapsed time in "hh:mm:ss" format.
   */
  private static function getElapsedTimeFormatted($time_start) {
    $time_elapsed_seconds = time() - $time_start;
    return gmdate('H:i:s', $time_elapsed_seconds);
  }

  /**
   * {@inheritdoc}
   */
  public static function finished($success, array $results, array $operations) {

    $msgText = new TranslatableMarkup(
      'Copied local %scheme files to S3 in @elapsed_time (hh:mm:ss).',
      [
        '%scheme' => $results['scheme'],
        '@elapsed_time' => static::getElapsedTimeFormatted($results['time_start']),
      ]
    );

    if (!empty($results['errors'])) {
      $msgText .= '<br>' . new TranslatableMarkup("The following errors occurred:");
      foreach ($results['errors'] as $error) {
        $msgText .= '<br>' . $error;
      }
    }

    \Drupal::messenger()->addStatus(new FormattableMarkup($msgText, []));
  }

  /**
   * Updates the progress counter and display.
   *
   * @param array|\DrushBatchContext $context
   *   Batch context passed by reference.
   */
  private static function updateProgress(&$context) {
    // Update our progress information.
    $context['results']['progress']++;

    // Show status message each 5 files.
    if ($context['results']['progress'] % 5 == 0) {
      $current_percent_progress = floor(($context['results']['progress'] / $context['results']['total']) * 100);

      if ($context['results']['percent_progress'] != $current_percent_progress) {
        $context['results']['percent_progress'] = $current_percent_progress;
      }

      $context['message'] = static::getCopyOperationMessage($context);
    }
  }

  /**
   * {@inheritdoc}
   */
  private static function isFileAlreadyUploaded($srcPath, $destUri, array $uploadConditions = []) {
    $streamWrapper = \Drupal::service('stream_wrapper.s3fs');
    $srcStat = stat($srcPath);
    $destStat = $streamWrapper->url_stat($destUri, 0);

    // Source has been deleted since batch created.
    if (empty($srcStat)) {
      return TRUE;
    }

    // No conditions or dest doesnt exist.
    if (empty($uploadConditions) || empty($destStat)) {
      return FALSE;
    }

    if (!empty($uploadConditions['newer'])) {
      if ($srcStat['mtime'] > $destStat['mtime']) {
        return FALSE;
      }
    }

    if (!empty($uploadConditions['size'])) {
      if ($srcStat['size'] != $destStat['size']) {
        return FALSE;
      }
    }

    return TRUE;

  }

}
