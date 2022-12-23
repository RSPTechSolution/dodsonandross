<?php

namespace Drupal\Tests\imageapi_optimize\Kernel;

use Drupal\Tests\image\Kernel\ImageThemeFunctionTest as OriginalImageThemeFunctionTest;

/**
 * Tests image theme functions.
 *
 * @group imageapi_optimize
 */
class ImageThemeFunctionTest extends OriginalImageThemeFunctionTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
