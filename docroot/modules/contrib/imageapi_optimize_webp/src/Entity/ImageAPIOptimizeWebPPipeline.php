<?php

namespace Drupal\imageapi_optimize_webp\Entity;

use Drupal\imageapi_optimize\Entity\ImageAPIOptimizePipeline;
use Drupal\Core\File\FileSystemInterface;

/**
 * Wrap ImageAPIOptimizePipeline to copy webp derivative to proper directory.
 *
 * This wrapper allows for .webp image derivatives to be copied
 * to the correct directory after the webp image_api handler takes place.
 *
 * Class ImageAPIOptimizeWebPPipeline
 *
 * @package Drupal\imageapi_optimize_webp\Entity
 *
 * @param \Drupal\Core\File\FileSystemInterface $filesystem
 */
class ImageAPIOptimizeWebPPipeline extends ImageAPIOptimizePipeline {

  /**
   * {@inheritdoc}
   */
  public function applyToImage($image_uri) {
    parent::applyToImage($image_uri);
    // If the source file doesn't exist, return FALSE.
    $image = \Drupal::service('image.factory')->get($image_uri);
    if (!$image->isValid()) {
      return FALSE;
    }
    if (count($this->getProcessors())) {
      $webp_uri = $image_uri . '.webp';
      foreach ($this->temporaryFiles as $temp_image_uri) {
        $temp_webp_uri = $temp_image_uri . '.webp';
        if (file_exists($temp_webp_uri)) {
          $temp_image_uri = \Drupal::service('file_system')->copy($temp_webp_uri, $webp_uri, FileSystemInterface::EXISTS_RENAME);
          if ($temp_image_uri) {
            $this->temporaryFiles[] = $temp_webp_uri;
            break;
          }
        }
      }
    }
  }

}

