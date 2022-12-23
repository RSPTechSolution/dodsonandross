<?php

namespace Drupal\Tests\s3fs\Functional;

use Drupal\s3fs\StreamWrapper\S3fsStream;

/**
 * S3 File System Metadata Cache tests.
 *
 * Ensure that metadata is properly stored and processed.
 *
 * @todo This should be a KernelTest.
 *
 * @group s3fs
 */
class S3fsMetadataTest extends S3fsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['s3fs'];

  /**
   * Coverage test for the stream wrapper.
   */
  public function testIgnoreCache() {
    $this->createTestObject('cache_hit/file.txt');
    $this->createTestObject('cache_miss/file.txt');
    $this->connection->insert('s3fs_file')->fields([
      'uri' => 's3://cache_hit',
      'filesize' => 0,
      'timestamp' => '1600000000',
      'dir' => '1',
    ])->execute();
    $this->connection->insert('s3fs_file')->fields([
      'uri' => 's3://cache_hit/file.txt',
      'filesize' => 9,
      'timestamp' => '1600000000',
      'dir' => '0',
    ])->execute();

    $wrapper = new S3fsStream();
    $this->assertIsArray($wrapper->url_stat('s3://cache_hit/file.txt', 0));
    $this->assertFalse($wrapper->url_stat('s3://cache_miss', 0));
    $this->assertFalse($wrapper->url_stat('s3://cache_miss/file.txt', 0));

    // Set the cache to ignored and rebuild the wrapper with new config.
    $this->config('s3fs.settings')->set('ignore_cache', TRUE)->save(TRUE);
    drupal_static_reset();
    $wrapper = new S3fsStream();

    // The directory will never exist however the file objet will.
    $this->assertFalse($wrapper->url_stat('s3://cache_miss', 0));
    $this->assertIsArray($wrapper->url_stat('s3://cache_miss/file.txt', 0));

  }

  /**
   * Create objects in bucket without using the s3fs module.
   *
   * @param string $path
   *   Path (under the root_folder) to create the object.
   */
  protected function createTestObject(string $path): void {

    $uploadParams = [
      'Body' => 'test data',
      'ACL' => 'public-read',
      'Bucket' => $this->s3Config['bucket'],
      'Key' => $this->s3Config['root_folder'] . '/' . $path,
    ];

    try {
      $this->s3->putObject($uploadParams);
    }
    catch (\Exception $e) {
      $this->fail('Failed to upload object to bucket');
    }

  }

}
