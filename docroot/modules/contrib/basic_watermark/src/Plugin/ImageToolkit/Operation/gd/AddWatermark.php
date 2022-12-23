<?php

namespace Drupal\basic_watermark\Plugin\ImageToolkit\Operation\gd;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\GDImageToolkitOperationBase;

/**
 * Defines GD2 Add Watermark operation.
 *
 * @ImageToolkitOperation(
 *   id = "gd_add_watermark",
 *   toolkit = "gd",
 *   operation = "add_watermark",
 *   label = @Translation("Add Watermark"),
 *   description = @Translation("Adds a watermark to the image.")
 * )
 */
class AddWatermark extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'watermark_path' => [
        'description' => 'The path to the watermark image',
      ],
      'apply_type' => [
        'description' => 'How to apply the watermark, repeat until it covers the whole image or once',
      ],
      'position' => [
        'description' => 'Where to put the watermark, ex: top-left, center, bottom-right',
      ],
      'margins' => [
        'description' => 'Empty area to keep around the watermark in pixels',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    $path = DRUPAL_ROOT . $arguments['watermark_path'];
    if (!file_exists($path) || !getimagesize($path)) {
      throw new \InvalidArgumentException("Invalid image ('{$arguments['watermark_path']}')");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    $image_resource = $this->getToolkit()->getResource();
    $watermark_filepath = DRUPAL_ROOT . $arguments['watermark_path'];
    $watermark_image = imagecreatefrompng($watermark_filepath);

    $image['width'] = imagesx($image_resource);
    $image['height'] = imagesy($image_resource);

    $watermark = $this->scaleWatermark($watermark_image, $image, $arguments['margins']);
    $margins = $this->getMargins($image, $watermark, $arguments['position'], $arguments['margins']);

    $temp_resource = $this->getToolkit()->getResource();

    switch ($arguments['apply_type']) {
      case 'repeat':
        // Repeat always starts from top left.
        $start_x = $arguments['margins']['left'];
        for ($i = 0; $i < ($image['width'] / $watermark['width']) + 1; $i++) {
          $start_y = $arguments['margins']['top'];
          for ($j = 0; $j < ($image['height'] / $watermark['height']) + 1; $j++) {
            $resource = imagecopy($temp_resource, $watermark['image'], $start_x, $start_y, 0, 0, min($watermark['width'], $image['width'] - $start_x), min($watermark['height'], $image['height'] - $start_y));

            // If at any point the image copy fails fail the operation.
            if (!$resource) {
              $this->getToolkit()->setResource($image_resource);
              return FALSE;
            }
            $start_y += $arguments['margins']['top'] + $watermark['height'];
          }
          $start_x += $arguments['margins']['left'] + $watermark['width'];
        }
        break;

      case 'once':
        $resource = imagecopy($temp_resource, $watermark['image'], $margins['x'], $margins['y'], 0, 0, $watermark['width'], $watermark['height']);
        if (!$resource) {
          $this->getToolkit()->setResource($image_resource);
          return FALSE;
        }
        break;

      default:
        return FALSE;
    }

    $this->getToolkit()->setResource(TRUE);
    imagedestroy($image_resource);
    return TRUE;
  }

  /**
   * Scales the watermark to fit in the image.
   *
   * The watermark will only be scaled down if its too big, taking into
   * consideration the provided margins.
   *
   * @param resource $watermark_image
   *   The watermark gd resource.
   * @param array $image
   *   An array with the width and height of the image to apply the watermark.
   * @param array $margins
   *   The margins to keep around the watermark.
   *
   * @return array
   *   An array of the scaled watermark as well as its width and height.
   */
  private function scaleWatermark($watermark_image, array $image, array &$margins) {
    $watermark['width'] = imagesx($watermark_image);
    $watermark['height'] = imagesy($watermark_image);

    // If the width of the margins exceed the image height remove the margins.
    if ($margins['left'] + $margins['right'] >= $image['width']) {
      $margins['left'] = 0;
      $margins['right'] = 0;
    }

    // If the height of the margins exceed the image height remove the margins.
    if ($margins['top'] + $margins['bottom'] >= $image['height']) {
      $margins['top'] = 0;
      $margins['bottom'] = 0;
    }

    // Scale Watermark to fit on image horizontaly.
    if ($watermark['width'] + $margins['left'] + $margins['right'] > $image['width']) {
      $watermark['width'] = $image['width'] - $margins['left'] - $margins['right'];
      $watermark_image = imagescale($watermark_image, $watermark['width']);
      $watermark['height'] = imagesy($watermark_image);
    }

    // Scale Watermark to fit on image vertically.
    if ($watermark['height'] + $margins['top'] + $margins['bottom'] > $image['height']) {
      $watermark['height'] = $image['height'] - $margins['top'] - $margins['bottom'];
      // New width = new height * (original width / original height)
      $watermark['width'] = $watermark['height'] * (imagesx($watermark_image) / imagesy($watermark_image));
      $watermark_image = imagescale($watermark_image, $watermark['width'], $watermark['height']);
    }

    return [
      'image' => $watermark_image,
      'width' => $watermark['width'],
      'height' => $watermark['height'],
    ];
  }

  /**
   * Gets the offset of where to put the watermark dependend of its position.
   *
   * Depending on the position selected we calculate the x and y offset taking
   * into consideration the margins provided.
   *
   * @param array $image
   *   The image width and height.
   * @param array $watermark
   *   The watermark gd resource, width and height.
   * @param string $position
   *   The position the watermark is going to be placed.
   * @param array $margins
   *   The margins to keep around the watermark.
   *
   * @return array
   *   The x and y offset.
   */
  private function getMargins(array $image, array $watermark, string $position, array $margins) {
    switch ($position) {
      case 'left-top':
        return [
          'x' => $margins['left'],
          'y' => $margins['top'],
        ];

      case 'center-top':
        return [
          'x' => ($image['width'] / 2) - ($watermark['width'] / 2),
          'y' => $margins['top'],
        ];

      case 'right-top':
        return [
          'x' => $image['width'] - $watermark['width'] - $margins['left'],
          'y' => $margins['top'],
        ];

      case 'left-center':
        return [
          'x' => $margins['left'],
          'y' => ($image['height'] / 2) - ($watermark['height'] / 2),
        ];

      case 'center-center':
        return [
          'x' => ($image['width'] / 2) - ($watermark['width'] / 2),
          'y' => ($image['height'] / 2) - ($watermark['height'] / 2),
        ];

      case 'right-center':
        return [
          'x' => $image['width'] - $watermark['width'] - $margins['left'],
          'y' => ($image['height'] / 2) - ($watermark['height'] / 2),
        ];

      case 'left-bottom':
        return [
          'x' => $margins['left'],
          'y' => $image['height'] - $watermark['height'] - $margins['top'],
        ];

      case 'center-bottom':
        return [
          'x' => ($image['width'] / 2) - ($watermark['width'] / 2),
          'y' => $image['height'] - $watermark['height'] - $margins['top'],
        ];

      case 'right-bottom':
        return [
          'x' => $image['width'] - $watermark['width'] - $margins['left'],
          'y' => $image['height'] - $watermark['height'] - $margins['top'],
        ];

      default:
        return [
          'x' => $margins['left'],
          'y' => $margins['top'],
        ];
    }
  }

}
