<?php

namespace Drupal\Tests\imageapi_optimize\Kernel;

use Drupal\Tests\image\Kernel\ImageEffectsTest as OriginalImageEffectsTest;

/**
 * Tests that the image effects pass parameters to the toolkit correctly.
 *
 * @group imageapi_optimize
 */
class ImageEffectsTest extends OriginalImageEffectsTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
