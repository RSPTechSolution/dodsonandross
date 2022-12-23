<?php

namespace Drupal\Tests\s3fs\Kernel;

use Composer\Semver\Semver;
use Drupal\KernelTests\Core\File\FileTestBase;

/**
 * Tests filename mimetype detection.
 *
 * @group File
 */
class S3fsMimeTypeTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file_test', 's3fs'];

  /**
   * Tests mapping of mimetypes from filenames.
   */
  public function testFileMimeTypeDetection() {
    $prefixes = ['public://', 'private://', 'temporary://', 'dummy-remote://'];
    $uses_new_guesser = Semver::satisfies(\Drupal::VERSION, '>=9.1');

    $test_case = [
      'test.jar' => 'application/java-archive',
      'test.jpeg' => 'image/jpeg',
      'test.JPEG' => 'image/jpeg',
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.Z' => 'application/x-font',
      'pcf.z' => 'application/octet-stream',
      'jar' => 'application/octet-stream',
      'some.junk' => 'application/octet-stream',
      'foo.file_test_1' => 'madeup/file_test_1',
      'foo.file_test_2' => 'madeup/file_test_2',
      'foo.doc' => 'madeup/doc',
      'test.ogg' => 'audio/ogg',
    ];

    $guesser = $this->container->get('s3fs.mime_type.guesser');
    // Test using default mappings.
    foreach ($test_case as $input => $expected) {
      // Test stream [URI].
      foreach ($prefixes as $prefix) {
        if ($uses_new_guesser) {
          $output = $guesser->guessMimeType($prefix . $input);
        }
        else {
          $output = $guesser->guess($prefix . $input);
        }
        $this->assertSame($expected, $output);
      }

      // Test normal path equivalent.
      if ($uses_new_guesser) {
        $output = $guesser->guessMimeType($input);
      }
      else {
        $output = $guesser->guess($input);
      }
      $this->assertSame($expected, $output);
    }

    // Now test the extension guesser by passing in a custom mapping.
    $mapping = [
      'mimetypes' => [
        0 => 'application/java-archive',
        1 => 'image/jpeg',
      ],
      'extensions' => [
        'jar' => 0,
        'jpg' => 1,
      ],
    ];

    $test_case = [
      'test.jar' => 'application/java-archive',
      'test.jpeg' => 'application/octet-stream',
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.z' => 'application/octet-stream',
      'pcf.z' => 'application/octet-stream',
      'jar' => 'application/octet-stream',
      'some.junk' => 'application/octet-stream',
      'foo.file_test_1' => 'application/octet-stream',
      'foo.file_test_2' => 'application/octet-stream',
      'foo.doc' => 'application/octet-stream',
      'test.ogg' => 'application/octet-stream',
    ];
    $extension_guesser = $this->container->get('s3fs.mime_type.guesser.extension');
    $extension_guesser->setMapping($mapping);

    foreach ($test_case as $input => $expected) {
      if ($uses_new_guesser) {
        $output = $guesser->guessMimeType($input);
      }
      else {
        $output = $guesser->guess($input);
      }
      $this->assertSame($expected, $output);
    }
  }

}
