<?php

namespace Drupal\Tests\s3fs\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\s3fs\Batch\S3fsRefreshCacheBatch;

/**
 * S3 File System Tests.
 *
 * Ensure that the remote file system functionality provided by S3 File System
 * works correctly.
 *
 * The AWS credentials must be configured in prepareConfig() because
 * settings.php, which does not get executed during a BrowserTestBase.
 *
 * @group s3fs
 */
class S3fsTest extends S3fsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['s3fs', 'image'];

  /**
   * Coverage test for the stream wrapper.
   */
  public function testStreamWrapperCoverage() {
    $test_uri1 = "{$this->remoteTestsFolderUri}/test_file1.txt";
    $test_uri2 = "{$this->remoteTestsFolderUri}/test_file2.txt";

    $this->assertTrue(\Drupal::service('stream_wrapper_manager')->isValidScheme('s3'), '"s3" is a valid stream wrapper scheme.');
    $this->assertEquals('Drupal\s3fs\StreamWrapper\S3fsStream', \Drupal::service('stream_wrapper_manager')->getClass('s3'), 'URIs with scheme "s3" should be handled by S3fsStream.');

    // The test.txt file contains enough data to force multiple calls
    // to write_stream().
    $file_contents = file_get_contents(__DIR__ . '/../../fixtures/test.txt');

    $this->assertTrue(\Drupal::service('file_system')->mkdir($this->remoteTestsFolderUri), 'Exercised mkdir to create the testing directory (in the DB).');
    $this->assertTrue(is_dir($this->remoteTestsFolderUri), 'Make sure the folder we just created correctly reports that it is a folder.');

    // Exercising file upload functionality.
    $s3_file1 = $this->saveData($file_contents, $test_uri1);

    $this->assertTrue(\Drupal::service('stream_wrapper_manager')->isValidUri($s3_file1->getFileUri()), "Uploaded the first test file, $test_uri1.");

    // Exercising file copy functionality.
    $s3_file2 = $this->copyFile($s3_file1, $test_uri2);
    $this->assertNotFalse($s3_file2, "Copied the the first test file to $test_uri2.");

    // Exercising the dir_*() functions.
    $files = \Drupal::service('file_system')->scanDirectory($this->remoteTestsFolderUri, '#.*#');
    $this->assertTrue(isset($files[$test_uri1]), 'The first test file is in the tests directory.');
    $this->assertTrue(isset($files[$test_uri2]), 'The second test file is in the tests directory.');
    $this->assertEquals(2, count($files), "There are exactly two files in the tests directory.");

    // Exercising getExternalUrl()
    $url = $this->createUrl($test_uri1);
    $this->assertNotFalse($url, 'getExternalUrl test succeeded.');

    // Exercising unlink()
    $this->assertTrue(self::fileDelete($s3_file1), "Deleted the first test file.");
    $this->assertFalse(file_exists($test_uri1), 'The wrapper reports that the first test file no longer exists.');

    // Exercising rename()
    $this->assertTrue(rename($test_uri2, $test_uri1), "Renamed the second test file to the newly-vacated URI of $test_uri1.");
    $s3_file2->setFileUri($test_uri1);
    $s3_file2->save();

    // Rename a 'directory' should fail.
    $dir_move_test_src = $this->remoteTestsFolderUri . '/directoryToBeMoved';
    $dir_move_test_dest = $this->remoteTestsFolderUri . '/destinationDirectory';
    $this->assertTrue(\Drupal::service('file_system')->mkdir($dir_move_test_src), 'Created testing directory to attempt move.');
    $this->assertNotFalse(file_put_contents($dir_move_test_src . '/test.file', 'test'), "Created file in directory that will be moved.");
    $this->assertFalse(rename($dir_move_test_src, $dir_move_test_dest), 'Should not be able to move a directory.');
    $this->assertFalse(is_file($dir_move_test_dest . '/test.file'), 'Test file should not exist as directory moves are not supported.');
    $this->assertTrue(unlink($dir_move_test_src . '/test.file'), "Deleted the test move file.");

    // Exercising rmdir()
    $this->assertFalse(\Drupal::service('file_system')->rmdir($this->remoteTestsFolderUri), 'rmdir() did not delete the tests folder because it is not empty.');
    $this->assertTrue(\Drupal::service('file_system')->rmdir($dir_move_test_src), "Delete the move test directory");
    $this->assertTrue(self::fileDelete($s3_file2), 'Deleted the last test file.');
    $this->assertTrue(\Drupal::service('file_system')->rmdir($this->remoteTestsFolderUri), 'Deleted the tests folder.');
    $this->assertFalse(is_dir($this->remoteTestsFolderUri), 'The wrapper reports that the tests folder is gone.');

    // Testing max filename limits.
    // 250 characters + 's3://' = 255 characters max limit.
    $sourceMaxString = 'C9Xa0jcb8RqTvu5KKSjziAmiRHJJDsbIdZTSt345KkJJRAhkfJk8OddTyBgps5u6RAEwkQ6WKfnSd2jQKQAm5BmcYVSMMtkUcSJo5TsvCgS4s5UrVEXPKcLqsP4JQuEAMDbIrqCD0WXuTDAUCF38oQvyaXplxwSwgjBavS4XkeYCqUjMXBSWtUeDLbiLkzfMFUHa1QcHciy318id75KOuSvMC4x2z1177Ht90zR4PNvTDvE7smPNEOL8Y';
    $uriMaxLength = 255;
    // 245 Character long string to work with on future tests.
    $baseLongDir = "{$this->remoteTestsFolderUri}/" . substr($sourceMaxString, 0, $uriMaxLength - strlen($this->remoteTestsFolderUri) - 11);
    $this->assertEquals($uriMaxLength - 10, strlen($baseLongDir));

    // Max length mkdir().
    // 256 characters long the last / is stripped making 255 limit safe.
    $pathDirMaxLength = "{$baseLongDir}/dir/12345/";
    $this->assertTrue(\Drupal::service('file_system')->mkdir($pathDirMaxLength), 'Creating max path length directory');
    $this->assertTrue(is_dir($pathDirMaxLength), 'Verify max path length directory exists');
    // 257 characters long the last / is stripped making 256 exceeding limit.
    $pathDirExceedMaxLength = "{$baseLongDir}/dir/123456/";
    $this->assertFalse(\Drupal::service('file_system')->mkdir($pathDirExceedMaxLength), 'Creating directory that exceeds path length limit');
    $this->assertFalse(is_dir($pathDirExceedMaxLength), 'Verify directory that exceeds max path length doesnt exist');

    // Max length stream_open().
    $pathFileMaxLength = "{$baseLongDir}/78901.txt";
    $pathFileExceedMaxLength = "{$baseLongDir}/789012.txt";
    $this->assertNotFalse(file_put_contents($pathFileMaxLength, $file_contents), 'Creating max path length filename');
    $this->assertTrue(is_file($pathFileMaxLength), 'Verify max path length file exists');
    $this->assertFalse(@file_put_contents($pathFileExceedMaxLength, $file_contents), 'Creating file exceeds max path length');
    $this->assertFalse(is_file($pathFileExceedMaxLength), 'File that exceeds max path length doesnt exist');

    // Max length rename().
    $pathFileRenameMaxLength = "{$baseLongDir}/78901.ace";
    $this->assertTrue(rename($pathFileMaxLength, $pathFileRenameMaxLength), 'Rename file to max path length');
    $this->assertFalse(rename($pathFileRenameMaxLength, $pathFileExceedMaxLength), 'Rename file to exceed max path length');

    // Set config and flush static to test cache-control header.
    $this->config('s3fs.settings')->set('cache_control_header', 'public, max-age=300')->save();
    drupal_static_reset('S3fsStream_constructed_settings');
    // Verify header exists on new files.
    $headerPutUri = 's3://' . $this->randomMachineName();
    file_put_contents($headerPutUri, $file_contents);
    $url = $this->createUrl($headerPutUri);
    $this->drupalGet($url);
    $this->assertSession()->responseHeaderEquals('cache-control', 'public, max-age=300');
    // Make sure the header exists and age changes on copy().
    $this->config('s3fs.settings')->set('cache_control_header', 'public, max-age=301')->save();
    drupal_static_reset('S3fsStream_constructed_settings');
    $headerCopyUri = 's3://' . $this->randomMachineName();
    copy($headerPutUri, $headerCopyUri);
    $url = $this->createUrl($headerCopyUri);
    $this->drupalGet($url);
    $this->assertSession()->responseHeaderEquals('cache-control', 'public, max-age=301');

    // Test copy a filename containing a UTF8 character (umlaut).
    $src_uri_umlaut = $this->remoteTestsFolderUri . '/' . base64_decode('RGF0ZWluYW1lIGVudGjDpGx0IFVtbGF1dGU=');
    $s3_file_umlaut = $this->saveData($file_contents, $src_uri_umlaut);
    $this->assertNotFalse(copy($src_uri_umlaut, $test_uri1), "Copy a file with an umlaut using copy()");
    $umlaut_copy = $this->copyFile($s3_file_umlaut, $test_uri2);
    $this->assertNotFalse($umlaut_copy, "Copy a file with an umlaut using FileSystem Service");

    // Test filenames containing UTF8 4-byte characters.
    $s3_file1 = $this->saveData($file_contents, $test_uri1);
    $s3_uri_utf84b = $this->remoteTestsFolderUri . '/' . base64_decode('bmFtZV9jb250YWluc19lbW9qaV/wn5iBLnR4dA==');
    $file_write_exception_thrown = FALSE;
    try {
      $s3_file_utf84b = $this->saveData($file_contents, $s3_uri_utf84b);
    }
    catch (FileWriteException $e) {
      $file_write_exception_thrown = TRUE;
    }
    $this->assertTrue($file_write_exception_thrown, "FileWriteException thrown by save data");

    $file_write_exception_thrown = FALSE;
    try {
      $s3_file_utf84b = $this->copyFile($s3_file1, $s3_uri_utf84b);
    }
    catch (FileWriteException $e) {
      $file_write_exception_thrown = TRUE;
    }
    $this->assertTrue($file_write_exception_thrown, "FileWriteException thrown by copy data");

    $this->assertFalse(@file_put_contents($s3_uri_utf84b, $file_contents), "Save content to a file containing a UTF8-4-byte char");
    $this->assertFalse(@rename($test_uri1, $s3_uri_utf84b), "Rename containing a UTF8-4-byte char using rename()");

  }

  /**
   * Test the image derivative functionality.
   */
  public function testImageDerivatives() {
    // Prevent issues with derivative tokens during test.
    $this->config('image.settings')->set('allow_insecure_derivatives', TRUE)->save();

    // Use the large image style for for presigned tests.
    $this->config('s3fs.settings')
      ->set('presigned_urls', "6000|.*/large/.*")
      ->save();

    $img_uri1 = "{$this->remoteTestsFolderUri}/test.png";
    $img_localpath = __DIR__ . '/../../fixtures/test.png';

    // Upload the test image.
    $this->assertTrue(\Drupal::service('file_system')->mkdir($this->remoteTestsFolderUri), 'Created the testing directory in the DB.');
    $img_data = file_get_contents($img_localpath);
    $img_file = $this->saveData($img_data, $img_uri1);
    $this->assertNotFalse($img_file, "Copied the the test image to $img_uri1.");

    // Request a derivative.
    // Parse query parameters to ensure they get passed.
    $style_url_parsed = UrlHelper::parse(ImageStyle::load('thumbnail')->buildUrl($img_uri1));
    $derivative = $this->drupalGet($style_url_parsed['path'], ['query' => $style_url_parsed['query']]);
    $this->assertNotFalse(imagecreatefromstring($derivative), 'The returned derivative is a valid image.');

    $style_presigned_url_parsed = UrlHelper::parse(ImageStyle::load('large')->buildUrl($img_uri1));
    $presigned_derivative = $this->drupalGet($style_presigned_url_parsed['path'], ['query' => $style_presigned_url_parsed['query']]);
    $this->assertNotFalse(imagecreatefromstring($presigned_derivative), 'The returned signed derivative is a valid image.');

  }

  /**
   * Tests that files stored in the root folder are converted properly.
   *
   * Imported from core.
   */
  public function testConvertFileInRoot() {
    // Create the test image style with a Convert effect.
    $image_style = ImageStyle::create([
      'name' => 'image_effect_test',
      'label' => 'Image Effect Test',
    ]);
    $this->assertEquals(SAVED_NEW, $image_style->save());
    $image_style->addImageEffect([
      'id' => 'image_convert',
      'data' => [
        'extension' => 'jpeg',
      ],
    ]);
    $this->assertEquals(SAVED_UPDATED, $image_style->save());

    // Create a copy of a test image file in root.
    $test_uri = 's3://image-test-do.png';
    $img_localpath = __DIR__ . '/../../fixtures/test.png';
    $img_data = file_get_contents($img_localpath);
    $this->saveData($img_data, $test_uri);

    $this->assertFileExists($test_uri);

    // Execute the image style on the test image via a GET request.
    $derivative_uri = 's3://styles/image_effect_test/s3/image-test-do.png.jpeg';
    $this->assertFileDoesNotExist($derivative_uri);
    $style_url_parsed = UrlHelper::parse(ImageStyle::load('image_effect_test')->buildUrl($test_uri));
    $this->drupalGet($style_url_parsed['path'], ['query' => $style_url_parsed['query']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($derivative_uri);
  }

  /**
   * Test the cache refresh.
   */
  public function testCacheRefresh() {
    // Add several files to the bucket using the AWS SDK directly, so that
    // s3fs won't cache them.
    $filenames = ['files/test2.txt', 'parts/test3.txt', 'test.txt'];
    foreach ($filenames as $filename) {
      $filename = $this->remoteTestsFolderKey . '/' . $filename;
      $this->s3->putObject(
        [
          'Bucket' => $this->s3Config['bucket'],
          'Key' => $filename,
          'ACL' => 'public-read',
        ]
      );
    }

    $config = $this->s3Config;
    // Set the current test folder as the root prefix.
    $config['root_folder'] = $this->remoteTestsFolderKey;
    \Drupal::service('s3fs')->refreshCache($config);

    // Query the DB to confirm that all the new files are cached.
    $result = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->execute();
    $cached_files = [];
    foreach ($result as $record) {
      $cached_files[] = str_replace('s3://', '', $record->uri);
    }
    $this->assertEquals($filenames, $cached_files, 'The test files were all cached.');

    // Flush the cache, then do a refresh without versions support.
    $this->connection->delete('s3fs_file')->execute();
    // Disable Version Syncing.
    $config['disable_version_sync'] = TRUE;
    \Drupal::service('s3fs')->refreshCache($config);
    $config['disable_version_sync'] = FALSE;

    // Query the DB to confirm that all the new files are cached.
    $result = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->execute();
    $cached_files = [];
    foreach ($result as $record) {
      $cached_files[] = str_replace('s3://', '', $record->uri);
    }
    $this->assertEquals($filenames, $cached_files, 'The test files were all cached without versions.');

    // Flush the cache, then do a refresh using the root_folder setting.
    // Only the file in the root folder (test3.txt) should become cached.
    $this->connection->delete('s3fs_file')->execute();
    $config['root_folder'] = $this->remoteTestsFolderKey . '/parts';
    \Drupal::service('s3fs')->refreshCache($config);

    // Confirm that only the file in the "parts" folder was cached.
    $records = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->execute()
      ->fetchAll();
    $this->assertEquals(1, count($records), 'There was only one file in the partially refreshed cache.');
    $this->assertEquals('s3://test3.txt', $records[0]->uri, 'That file was the one in the "parts" folder, which is now the root folder, so "parts" is not in the URI.');

    // Now test using the Batch system.
    // Set the current test folder as the root prefix.
    $config['root_folder'] = $this->remoteTestsFolderKey;

    $this->cacheBatchExecute($config);

    // Query the DB to confirm that all the new files are cached.
    $result = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->execute();
    $cached_files = [];
    foreach ($result as $record) {
      $cached_files[] = str_replace('s3://', '', $record->uri);
    }
    $this->assertEquals($cached_files, $filenames, 'Batch refresh cached all files.');

    // Flush the cache, then do a refresh using the root_folder setting.
    // Only the file in the root folder (test3.txt) should become cached.
    $this->connection->delete('s3fs_file')->execute();
    $config['root_folder'] = $this->remoteTestsFolderKey . '/parts';

    $this->cacheBatchExecute($config);

    // Confirm that only the file in the "parts" folder was cached.
    $records = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->execute()
      ->fetchAll();
    $this->assertEquals(count($records), 1, 'Batch partial refresh cached only one file.');
    $this->assertEquals($records[0]->uri, 's3://test3.txt', 'Batched refresh successfully stripped the "parts" folder which is now the root folder.');

    // Batch with disable_version_sync.
    $config['root_folder'] = $this->remoteTestsFolderKey;
    $config['disable_version_sync'] = TRUE;

    $this->cacheBatchExecute($config);

    // Query the DB to confirm that all the new files are cached.
    $result = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->execute();
    $cached_files = [];
    foreach ($result as $record) {
      $cached_files[] = str_replace('s3://', '', $record->uri);
    }
    $this->assertEquals($cached_files, $filenames, 'Batch refresh with disable_version_sync cached all files.');

    // Flush the cache, then do a refresh using the root_folder setting.
    // Only the file in the root folder (test3.txt) should become cached.
    $this->connection->delete('s3fs_file')->execute();
    $config['root_folder'] = $this->remoteTestsFolderKey . '/parts';

    $this->cacheBatchExecute($config);

    // Confirm that only the file in the "parts" folder was cached.
    $records = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->execute()
      ->fetchAll();
    $this->assertEquals(1, count($records), 'Batch partial refresh with disable_version_sync cached only one file.');
    $this->assertEquals('s3://test3.txt', $records[0]->uri, 'Batched refresh with disable_version_sync successfully stripped the "parts" folder which is now the root folder.');

  }

  /**
   * File delete wrapper that returns result.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file object to delete.
   *
   * @return bool
   *   TRUE if file was deleted, FALSE otherwise.
   */
  protected static function fileDelete(FileInterface $file) {
    $file->delete();
    $exists = file_exists($file->getFileUri());
    return !$exists;
  }

  /**
   * Create and execute a S3fsRefreshCacheBatch job.
   *
   * @param array $config
   *   S3fs Config array to be used for the batch.
   */
  private function cacheBatchExecute(array $config) {
    $cacheBatchService = \Drupal::service('s3fs.refresh_cache_batch');
    // This should match S3fsRefreshCacheBatch->execute() to just before
    // PHP_SAPI check and a rewrite of $this.
    // Create batch.
    $batch_builder = $cacheBatchService->getBatch();
    $args = [
      $config,
    ];
    $batch_builder->addOperation([
      S3fsRefreshCacheBatch::class,
      'refreshCacheOperation',
    ], $args);
    batch_set($batch_builder->toArray());

    $batch =& batch_get();

    // End copy from S3fsRefreshCacheBatch->execute().
    // We intentionally cut out the PHP_SAPI check and go to executing it.
    $batch['progressive'] = FALSE;

    if (class_exists(ExtensionPathResolver::class)) {
      $base_path = \Drupal::service('extension.path.resolver')->getPath('module', 's3fs');
    }
    else {
      // @todo remove when D9.3 is minimal supported version.
      // @phpstan-ignore-next-line
      $base_path = drupal_get_path('module', 's3fs');
    }
    $batch['file'] = $base_path . 'src/Batch/S3fsRefreshCacheBatch.php';

    batch_process();

  }

}
