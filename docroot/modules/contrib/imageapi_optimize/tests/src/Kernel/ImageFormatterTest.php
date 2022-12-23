<?php

namespace Drupal\Tests\imageapi_optimize\Kernel;

use Drupal\Tests\image\Kernel\ImageFormatterTest as OriginalImageFormatterTest;

/**
 * Tests the image field rendering using entity fields of the image field type.
 *
 * @group imageapi_optimize
 */
class ImageFormatterTest extends OriginalImageFormatterTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
