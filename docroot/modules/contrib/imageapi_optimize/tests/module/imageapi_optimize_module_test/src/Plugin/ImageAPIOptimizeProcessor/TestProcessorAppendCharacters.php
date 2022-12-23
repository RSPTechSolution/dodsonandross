<?php

namespace Drupal\imageapi_optimize_module_test\Plugin\ImageAPIOptimizeProcessor;

use Drupal\imageapi_optimize\ImageAPIOptimizeProcessorBase;

/**
 * Optimizes an image by making it 10 characters longer.
 *
 * @ImageAPIOptimizeProcessor(
 *   id = "imageapi_optimize_module_test_appendcharacters",
 *   label = @Translation("Testing processor - append 10 chars"),
 *   description = @Translation("Optimizes an image by making it 10 characters longer.")
 * )
 */
final class TestProcessorAppendCharacters extends ImageAPIOptimizeProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function applyToImage($image_uri) {
    return (bool) file_put_contents($image_uri, file_get_contents($image_uri) . str_repeat('1', 10));
  }
}
