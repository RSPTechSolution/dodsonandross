<?php

namespace Drupal\Tests\s3fs\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\s3fs\Traits\S3fsPathsTrait;

/**
 * Tests S3fsPathResolutionTrait.
 *
 * @group s3fs
 */
class S3fsPathsTraitTest extends KernelTestBase {

  use S3fsPathsTrait;

  /**
   * Test relative path resolution.
   *
   * @dataProvider resolvePathDataProvider
   */
  public function testPathResolution(string $uri, string $expected): void {
    $this->assertEquals($expected, $this->resolvePath($uri));
  }

  /**
   * Data for testing relative path resolution.
   *
   * @return array
   *   An array of test data.
   */
  public function resolvePathDataProvider(): array {
    return [
      'Fully Resolved file' => [
        's3://folder1/folder2/test.txt',
        's3://folder1/folder2/test.txt',
      ],
      'Fully Resolved directory' => [
        's3://folder1/folder2/',
        's3://folder1/folder2',
      ],
      'Scheme root' => [
        's3://',
        's3://',
      ],
      'Root with single dot ' => [
        's3://.',
        's3://',
      ],
      'Root with double dot' => [
        's3://..',
        's3://',
      ],
      'Path contains a double dot folder ' => [
        's3://folder1/../folder1/folder2/test1.txt',
        's3://folder1/folder2/test1.txt',
      ],
      'Path starts with a double dot folder ' => [
        's3://../folder1/folder2/test1.txt',
        's3://folder1/folder2/test1.txt',
      ],
      'Path contains a single dot folder ' => [
        's3://folder1/./folder2/test1.txt',
        's3://folder1/folder2/test1.txt',
      ],
      'Path starts with a single dot folder ' => [
        's3://./folder1/folder2/test1.txt',
        's3://folder1/folder2/test1.txt',
      ],

    ];
  }

}
