<?php

namespace Drupal\Tests\s3fs\Functional;

use Drush\TestTraits\DrushTestTrait;

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
class S3fsDrushTest extends S3fsTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['s3fs', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $file_contents = file_get_contents(__DIR__ . '/../../fixtures/test.txt');
    $test_uri1 = "public://drush/test_file1.txt";
    $test_uri2 = "public://drush/test_file2.txt";
    $test_uri3 = "private://drush/test_private_file1.txt";
    $test_uri4 = "private://drush/test_private_file2.txt";
    mkdir('public://drush');
    mkdir('private://drush');
    $this->saveData($file_contents, $test_uri1);
    $this->saveData($file_contents, $test_uri2);
    $this->saveData($file_contents, $test_uri3);
    $this->saveData($file_contents, $test_uri4);

  }

  /**
   * Coverage test for the drush refresh-cache.
   */
  public function testDrushRefreshCache() {
    $this->drush('s3fs:refresh-cache');
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('Message: The cached list of files has been refreshed in', $messages, 'Successfully Refreshed Cache');
  }

  /**
   * Coverage test for the drush s3fs:copy-local --scheme=public.
   */
  public function testDrushCopyLocalPublicOnly() {
    $this->drush('s3fs:copy-local', [], ['scheme' => 'public']);
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('[notice] You are going to copy public scheme(s).', $messages, 'Successfully Refreshed Cache');
    $this->assertStringContainsString('Including public scheme', $messages, "Uploading public as part of all");
    $this->assertStringContainsString('Message: Copied local /public/ files to S3', $messages, "Uploading public as part of all");

    $records = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->condition('uri', '%' . $this->connection->escapeLike('/drush/') . '%', 'LIKE')
      ->execute()
      ->fetchAll();
    $this->assertEquals(2, count($records));

  }

  /**
   * Coverage test for the drush s3fs:copy-local --scheme=private.
   */
  public function testDrushCopyLocalPrivateOnly() {
    $this->drush('s3fs:copy-local', [], ['scheme' => 'private']);
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('[notice] You are going to copy private scheme(s).', $messages, 'Successfully Refreshed Cache');
    $this->assertStringContainsString('Including private scheme', $messages, "Uploading public as part of all");
    $this->assertStringContainsString('Message: Copied local /private/ files to S3', $messages, "Uploading public as part of all");

    $records = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->condition('uri', '%' . $this->connection->escapeLike('/drush/') . '%', 'LIKE')
      ->execute()
      ->fetchAll();
    $this->assertEquals(2, count($records));

  }

  /**
   * Coverage test for the drush s3fs:copy-local --scheme=all.
   */
  public function testDrushCopyLocalBoth() {
    // $this->markTestSkipped('Drush is currently stalling on this');
    $this->drush('s3fs:copy-local');
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('[notice] You are going to copy all scheme(s).', $messages, 'Including all schemes');
    $this->assertStringContainsString('Including public scheme', $messages, "Including public as part of all");
    $this->assertStringContainsString('Including private scheme', $messages, "Including private as part of all");
    $this->assertStringContainsString('Message: Copied local /public/ files to S3', $messages, "Uploaded public as part of all");
    $this->assertStringContainsString('Message: Copied local /private/ files to S3', $messages, "Uploaded private as part of all");

    $records = $this->connection->select('s3fs_file', 's')
      ->fields('s')
      ->condition('dir', 0, '=')
      ->condition('uri', '%' . $this->connection->escapeLike('/drush/') . '%', 'LIKE')
      ->execute()
      ->fetchAll();
    $this->assertEquals(4, count($records));

  }

  /**
   * Test drush s3fs:copy-local --scheme=public --condition=newer.
   */
  public function testDrushCopyLocalNewerFilesOnly() {

    touch('public://drush/test_file1.txt', '1400000000');

    $values = [
      [
        'uri' => 'public://drush/test_file1.txt',
        'filesize' => '18750',
        'timestamp' => date('U', 1500000000),
        'dir' => '0',
        'version' => '',
      ],
      [
        'uri' => 'public://drush/test_file2.txt',
        'filesize' => '18750',
        'timestamp' => date('U', 1500000000),
        'dir' => '0',
        'version' => '',
      ],
    ];

    $query = $this->connection
      ->insert('s3fs_file')
      ->fields(['uri', 'filesize', 'timestamp', 'dir', 'version']);
    foreach ($values as $record) {
      $query->values($record);
    }
    $query->execute();

    $this->drush('s3fs:copy-local', [],
      ['scheme' => 'public', 'condition' => 'newer']
    );

    $records = $this->connection->select('s3fs_file', 's')
      ->fields('s', ['uri', 'timestamp'])
      ->condition('dir', 0, '=')
      ->condition('uri', '%' . $this->connection->escapeLike('/drush/') . '%', 'LIKE')
      ->execute()
      ->fetchAllAssoc('uri', \PDO::FETCH_ASSOC);
    $this->assertEquals('1500000000', $records['public://drush/test_file1.txt']['timestamp']);
    $this->assertGreaterThan('1500000000', $records['public://drush/test_file2.txt']['timestamp']);

  }

  /**
   * Test drush s3fs:copy-local --scheme=public --condition=size.
   */
  public function testDrushCopyLocalSizeDiffersOnly() {

    touch('public://drush/test_file1.txt', '1400000000');

    $values = [
      [
        'uri' => 'public://drush/test_file1.txt',
        'filesize' => '18750',
        'timestamp' => date('U', 1500000000),
        'dir' => '0',
        'version' => '',
      ],
      [
        'uri' => 'public://drush/test_file2.txt',
        'filesize' => '10',
        'timestamp' => date('U', 1500000000),
        'dir' => '0',
        'version' => '',
      ],
    ];

    $query = $this->connection
      ->insert('s3fs_file')
      ->fields(['uri', 'filesize', 'timestamp', 'dir', 'version']);
    foreach ($values as $record) {
      $query->values($record);
    }
    $query->execute();

    $this->drush('s3fs:copy-local', [],
      ['scheme' => 'public', 'condition' => 'size']
    );

    $records = $this->connection->select('s3fs_file', 's')
      ->fields('s', ['uri', 'filesize', 'timestamp'])
      ->condition('dir', 0, '=')
      ->condition('uri', '%' . $this->connection->escapeLike('/drush/') . '%', 'LIKE')
      ->execute()
      ->fetchAllAssoc('uri', \PDO::FETCH_ASSOC);
    $this->assertEquals('1500000000', $records['public://drush/test_file1.txt']['timestamp']);
    $this->assertEquals('18750', $records['public://drush/test_file2.txt']['filesize']);
    $this->assertGreaterThan('1500000000', $records['public://drush/test_file2.txt']['timestamp']);

  }

}
