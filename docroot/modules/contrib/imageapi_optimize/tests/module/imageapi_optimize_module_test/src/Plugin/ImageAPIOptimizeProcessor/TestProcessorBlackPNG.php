<?php

namespace Drupal\imageapi_optimize_module_test\Plugin\ImageAPIOptimizeProcessor;

use Drupal\imageapi_optimize\ImageAPIOptimizeProcessorBase;

/**
 * Optimizes an image by making it a 1x1 pixel black PNG.
 *
 * @ImageAPIOptimizeProcessor(
 *   id = "imageapi_optimize_module_test_blackpng",
 *   label = @Translation("Testing processor - black PNG"),
 *   description = @Translation("Optimizes an image by making it a 1x1 pixel black PNG.")
 * )
 */
final class TestProcessorBlackPNG extends ImageAPIOptimizeProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function applyToImage($image_uri) {
    return (bool) file_put_contents($image_uri, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
  }
}
