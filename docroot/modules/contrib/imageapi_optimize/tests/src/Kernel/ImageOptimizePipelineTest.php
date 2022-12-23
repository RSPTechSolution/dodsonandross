<?php

namespace Drupal\Tests\imageapi_optimize\Kernel;

use Drupal\imageapi_optimize\Entity\ImageAPIOptimizePipeline;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Image Optimize pipelines.
 *
 * @group imageapi_optimize
 */
class ImageOptimizePipelineTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'imageapi_optimize', 'imageapi_optimize_module_test'];

  /**
   * Test using image pipeline
   */
  public function testValidImagePipeline() {

    // Valid pink 1x1 PNG file.
    $original_image_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==');

    // Include special characters in the filename.
    $image_uri = $this->createUri('Файл для тестирования ' . $this->randomMachineName() . '.png', $original_image_data);

    // Check that our file has got to the filesystem correctly.
    $this->assertStringEqualsFile($image_uri, $original_image_data, 'Image data written to file successfully');

    // Setup our pipeline.
    $pipeline = ImageAPIOptimizePipeline::create([
      'name' => 'test',
    ]);
    $pipeline->addProcessor(['id' => 'imageapi_optimize_module_test_blackpng']);

    // Apply the pipeline.
    $pipeline->applyToImage($image_uri);

    // Check that the file was correctly 'optimized' to a black 1x1 PNG.
    $this->assertStringEqualsFile($image_uri, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
  }

  /**
   * Test using image pipeline that should not change the image.
   */
  public function testFailureImagePipeline() {

    // Valid pink 1x1 PNG file.
    $original_image_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==');

    // Include special characters in the filename.
    $image_uri = $this->createUri('Файл для тестирования ' . $this->randomMachineName() . '.png', $original_image_data);

    // Check that our file has got to the filesystem correctly.
    $this->assertStringEqualsFile($image_uri, $original_image_data, 'Image data written to file successfully.');

    // Setup our pipeline.
    $pipeline = ImageAPIOptimizePipeline::create([
      'name' => 'test',
    ]);
    $pipeline->addProcessor(['id' => 'imageapi_optimize_module_test_failedgreenpng']);

    // Apply the pipeline.
    $pipeline->applyToImage($image_uri);

    // Check that the file was correctly 'optimized' to a black 1x1 PNG.
    $this->assertStringEqualsFile($image_uri, $original_image_data, 'Original image preserved.');
  }

  /**
   * Test using image pipeline that should not change the image.
   */
  public function testCompoundFailureImagePipeline() {

    // Valid pink 1x1 PNG file.
    $original_image_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==');

    // Include special characters in the filename.
    $image_uri = $this->createUri('Файл для тестирования ' . $this->randomMachineName() . '.png', $original_image_data);

    // Check that our file has got to the filesystem correctly.
    $this->assertStringEqualsFile($image_uri, $original_image_data, 'Image data written to file successfully.');

    // Setup our pipeline.
    $pipeline = ImageAPIOptimizePipeline::create([
      'name' => 'test',
    ]);
    // Add ten 1 characters.
    $pipeline->addProcessor(['id' => 'imageapi_optimize_module_test_appendcharacters']);
    // Change the image to a green PNG, but fail the processor.
    $pipeline->addProcessor(['id' => 'imageapi_optimize_module_test_failedgreenpng']);
    // Add ten 1 characters.
    $pipeline->addProcessor(['id' => 'imageapi_optimize_module_test_appendcharacters']);

    // Apply the pipeline.
    $pipeline->applyToImage($image_uri);

    // Check that the file was correctly 'optimized': adding 20 '1' characters.
    $this->assertFileEqualsString($original_image_data . str_repeat('1', 20), $image_uri);

  }

  /**
   * Test procesors are cumulative.
   */
  public function testCompoundImagePipeline() {

    // Valid pink 1x1 PNG file.
    $original_image_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z/C/HgAGgwJ/lK3Q6wAAAABJRU5ErkJggg==');

    // Include special characters in the filename.
    $image_uri = $this->createUri('Файл для тестирования ' . $this->randomMachineName() . '.png', $original_image_data);

    // Check that our file has got to the filesystem correctly.
    $this->assertStringEqualsFile($image_uri, $original_image_data, 'Image data written to file successfully.');

    // Setup our pipeline.
    $pipeline = ImageAPIOptimizePipeline::create([
      'name' => 'test',
    ]);
    $pipeline->addProcessor(['id' => 'imageapi_optimize_module_test_appendcharacters']);
    $pipeline->addProcessor(['id' => 'imageapi_optimize_module_test_appendcharacters']);

    // Apply the pipeline.
    $pipeline->applyToImage($image_uri);

    // Check that the file was correctly 'optimized': adding 20 '1' characters.
    $this->assertFileEqualsString($original_image_data . str_repeat('1', 20), $image_uri);
  }

  /**
   * Asserts that the contents of a string is equal
   * to the contents of a file.
   *
   * @param string $extectedString
   * @param string $actualFile
   * @param string $message
   * @param bool   $canonicalize
   * @param bool   $ignoreCase
   *
   * @since  Method available since Release 3.3.0
   */
  public static function assertFileEqualsString($extectedString, $actualFile, $message = '', $canonicalize = false, $ignoreCase = false)
  {
    self::assertFileExists($actualFile, $message);

    self::assertEquals(
      $extectedString,
      file_get_contents($actualFile),
      $message,
      0,
      10,
      $canonicalize,
      $ignoreCase
    );
  }

  /**
   * Create a file and return the URI of it.
   *
   * @param $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   * @return
   *   File URI.
   */
  public function createUri($filepath = NULL, $contents = NULL, $scheme = NULL) {
    if (!isset($filepath)) {
      // Prefix with non-latin characters to ensure that all file-related
      // tests work with international filenames.
      $filepath = 'Файл для тестирования ' . $this->randomMachineName();
    }
    if (!isset($scheme)) {
      $scheme = \Drupal::config('system.file')->get('default_scheme');
    }
    $filepath = $scheme . '://' . $filepath;

    if (!isset($contents)) {
      $contents = "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.";
    }

    file_put_contents($filepath, $contents);
    $this->assertFileExists($filepath, t('The test file exists on the disk.'));
    return $filepath;
  }

  /**
   * Test the pipeline does not fail badly when image does not exist.
   */
  public function testNonExistentImagePipeline() {

    // Include special characters in the filename.
    $image_uri = \Drupal::config('system.file')->get('default_scheme') . '://Файл для тестирования ' . $this->randomMachineName() . '.png';
    $this->assertFileNotExists($image_uri, t('The test file does not exist on the disk.'));

    // Setup our pipeline.
    $pipeline = ImageAPIOptimizePipeline::create([
      'name' => 'test',
    ]);
    $pipeline->addProcessor(['id' => 'imageapi_optimize_module_test_appendcharacters']);

    // Apply the pipeline.
    $result = $pipeline->applyToImage($image_uri);

    // Check that the file was correctly 'optimized' to a black 1x1 PNG.
    $this->assertFalse($result, 'Image pipeline failed to apply gracefully.');
  }

}
