<?php

namespace Drupal\Tests\imageapi_optimize\Functional;

use Drupal\Tests\image\Functional\ImageDimensionsTest as OriginalImageDimensionsTest;

/**
 * Tests that images have correct dimensions when styled.
 *
 * @group imageapi_optimize
 */
class ImageDimensionsTest extends OriginalImageDimensionsTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
