<?php

namespace Drupal\Tests\imageapi_optimize\Kernel;

use Drupal\Tests\image\Kernel\ImageItemTest as OriginalImageItemTest;

/**
 * Tests using entity fields of the image field type.
 *
 * @group imageapi_optimize
 */
class ImageItemTest extends OriginalImageItemTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
