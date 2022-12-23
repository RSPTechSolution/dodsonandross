<?php

namespace Drupal\imageapi_optimize_module_test\Plugin\ImageAPIOptimizeProcessor;

use Drupal\imageapi_optimize\ImageAPIOptimizeProcessorBase;

/**
 * Optimizes an image by making it a 1x1 pixel green PNG, but then failing.
 *
 * @ImageAPIOptimizeProcessor(
 *   id = "imageapi_optimize_module_test_failedgreenpng",
 *   label = @Translation("Testing processor - green PNG fail"),
 *   description = @Translation("Optimizes an image by making it a 1x1 pixel green PNG, but then failing.")
 * )
 */
final class TestProcessorFailedGreenPNG extends ImageAPIOptimizeProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function applyToImage($image_uri) {
    // Change the file to a green 1x1 PNG.
    file_put_contents($image_uri, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M/wHwAEBgIApD5fRAAAAABJRU5ErkJggg=='));
    // Fail even though we changed the file.
    return FALSE;
  }
}
