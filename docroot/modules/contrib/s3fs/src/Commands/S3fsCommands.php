<?php

namespace Drupal\s3fs\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\s3fs\Batch\S3fsFileMigrationBatchInterface;
use Drupal\s3fs\Batch\S3fsRefreshCacheBatchInterface;
use Drupal\s3fs\S3fsServiceInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * S3FS Drush commands handler.
 *
 * @package Drupal\s3fs\Commands
 */
class S3fsCommands extends DrushCommands {

  /**
   * S3fs service.
   *
   * @var \Drupal\s3fs\S3fsServiceInterface
   */
  private $s3fs;

  /**
   * S3fs file migration service.
   *
   * @var \Drupal\s3fs\Batch\S3fsFileMigrationBatchInterface
   */
  private $s3fsFileMigrationBatch;

  /**
   * S3fs Config data.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $s3fsConfig;

  /**
   * S3fs RefreshCacheBatch service.
   *
   * @var \Drupal\s3fs\Batch\S3fsRefreshCacheBatchInterface
   */
  private $s3fsRefreshCacheBatch;

  /**
   * S3fsCommands constructor.
   *
   * @param \Drupal\s3fs\S3fsServiceInterface $s3fs
   *   S3fs service.
   * @param \Drupal\s3fs\Batch\S3fsFileMigrationBatchInterface $s3fs_file_migration_batch
   *   S3fs file migration service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal config.factory service.
   * @param \Drupal\s3fs\Batch\S3fsRefreshCacheBatchInterface $s3fs_refresh_cache_batch
   *   S3fs RefreshCacheBatch service.
   */
  public function __construct(
    S3fsServiceInterface $s3fs,
    S3fsFileMigrationBatchInterface $s3fs_file_migration_batch,
    ConfigFactoryInterface $config_factory,
    S3fsRefreshCacheBatchInterface $s3fs_refresh_cache_batch
  ) {
    $this->s3fs = $s3fs;
    $this->s3fsFileMigrationBatch = $s3fs_file_migration_batch;
    $this->s3fsConfig = $config_factory->get('s3fs.settings');
    $this->s3fsRefreshCacheBatch = $s3fs_refresh_cache_batch;
  }

  /**
   * Refresh the S3 File System metadata cache.
   *
   * @command s3fs:refresh-cache
   * @aliases s3fs-rc, s3fs-refresh-cache
   */
  public function refreshCache() {
    $config = $this->s3fsConfig->get();

    if ($errors = $this->s3fs->validate($config)) {
      foreach ($errors as $error) {
        $this->logger()->error($error);
      }
      throw new \Exception(
        new TranslatableMarkup('Unable to validate your s3fs configuration settings. Please configure S3 File System from the admin/config/media/s3fs page and try again.')
      );
    }

    $this->s3fsRefreshCacheBatch->execute($config);
  }

  /**
   * Copy local files into your S3 bucket.
   *
   *  Copies files from local public:// or private:// into the S3 bucket.
   *
   * @command s3fs:copy-local
   * @aliases s3fs-cl, s3fs-copy-local
   * @option scheme Limit the process to an specific scheme. E.g. (public or private), all by default.
   * @option condition Limits when to migrate files one of always,newer,size,newer_size. Default always.
   * @usage drush s3fs-copy-local
   *   Copy local files from your public and/or private file system(s)
   *   into your S3 bucket.
   * @usage drush s3fs-copy-local --scheme=public
   *   Copy local files only from your public file system into your S3 bucket.
   */
  public function copyLocal(
    array $options = [
      'scheme' => 'all',
      'condition' => 'always',
    ]
  ) {
    $scheme = $options['scheme'];
    $uploadOptions = [];
    switch ($options['condition']) {
      case 'always':
        $uploadOptions['upload_conditions']['always'] = TRUE;
        break;

      case 'newer_size':
        $uploadOptions['upload_conditions']['newer'] = TRUE;
        $uploadOptions['upload_conditions']['size'] = TRUE;
        break;

      case 'newer':
        $uploadOptions['upload_conditions']['newer'] = TRUE;
        break;

      case 'size':
        $uploadOptions['upload_conditions']['size'] = TRUE;
        break;
    }

    $this->logger()->notice(new TranslatableMarkup('You are going to copy @scheme scheme(s).', ['@scheme' => $scheme]));
    $this->logger()->warning(new TranslatableMarkup('You should have read "Copy local files to S3" section in README.txt.'));
    $this->logger()->warning(new TranslatableMarkup('This command only is useful if you have or you are going to have enabled s3 for public/private in your setting.php'));

    if (!$this->io()->confirm(new TranslatableMarkup('Are you sure?'))) {
      return new UserAbortException();
    }

    $config = $this->s3fsConfig->get();

    if ($errors = $this->s3fs->validate($config)) {
      foreach ($errors as $error) {
        $this->logger()->error($error);
      }
      throw new \Exception(
        new TranslatableMarkup('Unable to validate your s3fs configuration settings. Please configure S3 File System from the admin/config/media/s3fs page and try again.')
      );
    }

    if ($scheme == 'all' || $scheme == 'public') {
      $this->logger()->notice(new TranslatableMarkup('Including @scheme scheme', ['@scheme' => 'public']));
      $this->s3fsFileMigrationBatch->execute($config, 'public', $uploadOptions);
    }

    if ($scheme == 'all' || $scheme == 'private') {
      if (Settings::get('file_private_path')) {
        $this->logger()->notice(new TranslatableMarkup('Including @scheme scheme', ['@scheme' => 'private']));
        $this->s3fsFileMigrationBatch->execute($config, 'private', $uploadOptions);
      }
      else {
        $this->logger()->warning(new TranslatableMarkup('Scheme @scheme is not properly configured, you must enable this scheme in your settings.php',
          ['@scheme' => 'private']
        ));
      }
    }

    drush_backend_batch_process();

  }

}
