<?php

namespace Drupal\Tests\s3fs\Functional;

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Drupal\Core\Config\Config;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * S3 File System Test Base.
 *
 * Provides a base for BrowserTest to execute against.
 *
 * The AWS credentials must be configured in prepareConfig() or using
 * environment variables because settings.php does not get executed
 * by BrowserTestBase.
 *
 * Environment variables available for configuration:
 *   S3FS_AWS_KEY - AWS IAM user key
 *   S3FS_AWS_SECRET - AWS IAM secret
 *   S3FS_AWS_BUCKET - Name of S3 bucket
 *   S3FS_AWS_REGION - Region of bucket.
 *
 * @group s3fs
 */
abstract class S3fsTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['s3fs'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The s3fs module config.
   *
   * @var array
   */
  protected $s3Config;

  /**
   * The AWS SDK for PHP S3Client object.
   *
   * @var \Aws\S3\S3Client
   */
  protected $s3;

  /**
   * The AWS SDK for PHP S3Client object.
   *
   * @var \Drupal\S3fs\S3fsService
   */
  protected $s3fs;

  /**
   * S3 Credentials provided and bucket exists.
   *
   * @var bool
   */
  protected $bucketNotFound = FALSE;

  /**
   * Folder name to use for placing tests files.
   *
   * @var string
   */
  protected $remoteTestsFolder = '_s3fs_tests';

  /**
   * Full base key path for tests folder.
   *
   * @var string
   */
  protected $remoteTestsFolderKey = '';

  /**
   * URI for accessing the data via StreamWrapper.
   *
   * @var string
   */
  protected $remoteTestsFolderUri = '';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->prepareConfig($this->config('s3fs.settings'));

    if (empty($this->s3Config['bucket'])) {
      // No sense to test anything if credentials absent.
      $this->bucketNotFound = TRUE;
      $this->markTestSkipped('S3 not configured');
    }

    $this->s3fs = \Drupal::service('s3fs');

    $this->connection = $this->container->get('database');
    $this->s3 = $this->s3fs->getAmazonS3Client($this->s3Config);
    $this->remoteTestsFolderKey = $this->s3Config['root_folder'];
    $this->remoteTestsFolderUri = "s3://{$this->remoteTestsFolder}";
    $this->bucketNotFound = !$this->s3->doesBucketExist($this->s3Config['bucket']);

    $connectAttempts = 0;
    while ($this->bucketNotFound && $connectAttempts <= 5) {
      try {
        $result = $this->s3->createBucket([
          'Bucket' => $this->s3Config['bucket'],
        ]);
        $this->bucketNotFound = FALSE;
      }
      catch (S3Exception $e) {
        // Bucket possibly was created by another script between checking.
        $this->bucketNotFound = !$this->s3->doesBucketExist($this->s3Config['bucket']);
      }
      catch (AwsException $e) {
        // No need to continue tests if can't access the bucket. Either the
        // credentials are incorrect or problem with S3Client.
        $this->fail("Unable to create bucket '{$this->s3Config['bucket']}' in region '{$this->s3Config['region']}'.
          Please verify the S3 settings.");
      }

      if ($this->bucketNotFound) {
        // Wait before we loop again.
        sleep(5);
      }
      $connectAttempts++;
    }

    if (!$this->bucketNotFound) {
      // Empty out the bucket before the test, to prevent unexpected errors.
      $this->s3->deleteMatchingObjects($this->s3Config['bucket'], $this->remoteTestsFolderKey);
    }
    else {
      $this->fail("Unable to access bucket '{$this->s3Config['bucket']}' in region '{$this->s3Config['region']}'.
          Please verify the S3 settings.");
    }
  }

  /**
   * Clean up S3 folder.
   */
  protected function tearDown(): void {
    if (!$this->bucketNotFound) {
      $this->s3->deleteMatchingObjects($this->s3Config['bucket'], $this->remoteTestsFolderKey);
    }
    parent::tearDown();
  }

  /**
   * Converts s3fs config to an array.
   *
   * @param \Drupal\Core\Config\Config $config
   *   A s3fs.settings config object.
   */
  protected function prepareConfig(Config $config) {
    $this->s3Config = [];

    // Array to hold global settings to be written later.
    $settings = [];

    // Configuration for test bots here. Can be modified for local installs
    // that do not use environment variables.
    $config->set('bucket', 's3fs-test-bucket')
      ->set('region', 'us-east-1')
      ->set('use_customhost', TRUE)
      ->set('hostname', 's3fslocalstack:4566')
      ->save();

    $settings['settings']['s3fs.access_key'] = (object) [
      'value' => 'test',
      'required' => TRUE,
    ];
    $settings['settings']['s3fs.secret_key'] = (object) [
      'value' => 'test',
      'required' => TRUE,
    ];

    // Check for environment variable overrides.
    if (getenv('S3FS_AWS_BUCKET')) {
      $config->set('bucket', getenv('S3FS_AWS_BUCKET'))->save();
    }
    if (getenv('S3FS_AWS_REGION')) {
      $config->set('region', getenv('S3FS_AWS_REGION'))->save();
    }
    if (!empty(getenv('S3FS_AWS_NO_CUSTOM_HOST'))) {
      $config->set('use_customhost', FALSE)->save();
    }
    if (!empty(getenv('S3FS_AWS_CUSTOM_HOST'))) {
      $config->set('hostname', getenv('S3FS_AWS_CUSTOM_HOST'))->save();
    }

    if (getenv('S3FS_AWS_KEY')) {
      $settings['settings']['s3fs.access_key'] = (object) [
        'value' => getenv('S3FS_AWS_KEY'),
        'required' => TRUE,
      ];
    }

    if (getenv('S3FS_AWS_SECRET')) {
      $settings['settings']['s3fs.secret_key'] = (object) [
        'value' => getenv('S3FS_AWS_SECRET'),
        'required' => TRUE,
      ];
    }

    // Set the standardized root_folder.
    $rootPath = $this->remoteTestsFolder . '/' . uniqid('', TRUE);
    if (!empty($config->get('root_folder'))) {
      $rootPath = $config->get('root_folder') . '/' . $rootPath;
    }

    $config->set('root_folder', $rootPath)->save();

    $this->writeSettings($settings);

    foreach ($config->get() as $prop => $value) {
      $this->s3Config[$prop] = $value;
    }
  }

  /**
   * Helper for deprecated file_save_data()
   *
   *  Use file_save_data on <D9.3.
   *  Use FileRepositoryInterface::writeData() >= D9.3.
   *
   * @param string $data
   *   Data to be written into file.
   * @param string $destination
   *   Path to file.
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists, then its database entry
   *     will be updated. If no database entry is found, then a new one will be
   *     created.
   *
   * @return \Drupal\file\FileInterface|false
   *   File interface if successful else false.
   */
  protected function saveData(string $data, string $destination, int $replace = FileSystemInterface::EXISTS_RENAME) {
    if (interface_exists(FileRepositoryInterface::class)) {
      /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
      $fileRepository = \Drupal::service('file.repository');
      return $fileRepository->writeData($data, $destination, $replace);
    }
    else {
      // @todo remove when D9.3 is minimal supported version.
      // @phpstan-ignore-next-line
      return file_save_data($data, $destination, $replace);
    }
  }

  /**
   * Helper for deprecated file_copy()
   *
   *  Use file_save_data on <D9.3.
   *  Use FileRepositoryInterface::writeData() >= D9.3.
   *
   * @param \Drupal\file\FileInterface $source
   *   File to copy from.
   * @param string $destination
   *   Path to destination.
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists, then its database entry
   *     will be updated. If no database entry is found, then a new one will be
   *     created.
   *
   * @return \Drupal\file\FileInterface|false
   *   File interface if successful else false.
   */
  protected function copyFile(FileInterface $source, string $destination, int $replace = FileSystemInterface::EXISTS_RENAME) {
    if (interface_exists(FileRepositoryInterface::class)) {
      /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
      $fileRepository = \Drupal::service('file.repository');
      return $fileRepository->copy($source, $destination, $replace);
    }
    else {
      // @todo remove when D9.3 is minimal supported version.
      // @phpstan-ignore-next-line
      return file_copy($source, $destination, $replace);
    }
  }

  /**
   * Helper for deprecated file_create_url()
   *
   *  Use file_save_data on <D9.3.
   *  Use FileRepositoryInterface::writeData() >= D9.3.
   *
   * @param string $uri
   *   Uri to create files to.
   *
   * @return string
   *   URL for uri.
   */
  protected function createUrl(string $uri) {
    if (interface_exists(FileUrlGeneratorInterface::class)) {
      /** @var \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator */
      $fileUrlGenerator = \Drupal::service('file_url_generator');
      return $fileUrlGenerator->generateAbsoluteString($uri);
    }
    else {
      // @todo remove when D9.3 is minimal supported version.
      // @phpstan-ignore-next-line
      return file_create_url($uri);
    }
  }

}
