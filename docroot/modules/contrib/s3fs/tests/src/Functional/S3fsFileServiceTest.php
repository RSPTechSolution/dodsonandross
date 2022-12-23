<?php

namespace Drupal\Tests\s3fs\Functional;

use Drupal\Core\File\Exception\FileNotExistsException;

/**
 * S3 File System Service Decorator Tests.
 *
 * Ensure that the decorator override works correctly.
 *
 * @group s3fs
 */
class S3fsFileServiceTest extends S3fsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['s3fs'];

  /**
   * Coverage test for the file_system service decorator.
   *
   * Most of this is implicitly tested in S3fsTest. however lets make sure
   * to explicitly test the decorator.
   */
  public function testFileService() {
    $testUri1 = "{$this->remoteTestsFolderUri}/test_file1.txt";
    $testUri2 = "{$this->remoteTestsFolderUri}/test_file2.txt";
    $testUri3 = "{$this->remoteTestsFolderUri}/test_file3.txt";
    $testUri4 = "{$this->remoteTestsFolderUri}/test_file4.txt";

    $fileSystem = \Drupal::service('file_system');

    $file_contents = file_get_contents(__DIR__ . '/../../fixtures/test.txt');
    $temp_file = $fileSystem->saveData($file_contents, 'temporary://123');

    $this->assertTrue($fileSystem->moveUploadedFile($temp_file, $testUri1), 'Uploaded file with S3fsFileService');
    $this->assertFalse($fileSystem->moveUploadedFile('/tmp/invalidFile', $testUri1), 'Did not upload nonexistent file');

    $this->assertEquals($testUri2, $fileSystem->move($testUri1, $testUri2), 'Moved file with S3fsFileService');
    $this->expectException(FileNotExistsException::class);
    $fileSystem->move($testUri1, $testUri3);

    $this->assertEquals($testUri4, $fileSystem->copy($testUri2, $testUri4), 'Copied file from uri2 to uri4');
    $this->expectException(FileNotExistsException::class);
    $fileSystem->copy($testUri1, $testUri2);
  }

  /**
   * Coverage test for the file_system setting cache headers.
   *
   * Make sure that Cache-Control headers are set on the file.
   */
  public function testCacheHeaders() {
    $this->config('s3fs.settings')->set('cache_control_header', 'public, max-age=300')->save();
    /** @var \Drupal\s3fs\S3fsFileService $fileSystem */
    $fileSystem = \Drupal::service('file_system');
    $file_contents = file_get_contents(__DIR__ . '/../../fixtures/test.txt');

    // Verify that $filesystem->putObject() sets cache headers.
    $headerTestUri1 = "s3://" . $this->randomMachineName();
    $cacheTestFile = $fileSystem->saveData($file_contents, $headerTestUri1);
    $url = $this->createUrl($cacheTestFile);
    $this->drupalGet($url);
    $this->assertSession()->responseHeaderEquals('cache-control', 'public, max-age=300');

    // Verify that filesystem->copyObject() replaces cache headers.
    $this->config('s3fs.settings')->set('cache_control_header', 'public, max-age=301')->save();
    $headerTestUri2 = "s3://" . $this->randomMachineName();
    $copyTestFile = $fileSystem->copy($cacheTestFile, $headerTestUri2);
    $url = $this->createUrl($copyTestFile);
    $this->drupalGet($url);
    $this->assertSession()->responseHeaderEquals('cache-control', 'public, max-age=301');
  }

}
