<?php

namespace Drupal\Tests\imageapi_optimize\Kernel;

use Drupal\Tests\image\Kernel\ImageStyleCustomStreamWrappersTest as OriginalImageStyleCustomStreamWrappersTest;

/**
 * Tests derivative generation with source images using stream wrappers.
 *
 * @group imageapi_optimize
 */
class ImageStyleCustomStreamWrappersTest extends OriginalImageStyleCustomStreamWrappersTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
