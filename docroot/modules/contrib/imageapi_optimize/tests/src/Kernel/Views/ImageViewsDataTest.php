<?php

namespace Drupal\Tests\imageapi_optimize\Kernel\Views;

use Drupal\Tests\image\Kernel\Views\ImageViewsDataTest as OriginalImageViewsDataTest;

/**
 * Tests image views data.
 *
 * @group imageapi_optimize
 */
class ImageViewsDataTest extends OriginalImageViewsDataTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
