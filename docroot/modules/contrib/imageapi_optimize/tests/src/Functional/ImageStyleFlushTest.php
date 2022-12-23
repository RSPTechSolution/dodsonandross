<?php

namespace Drupal\Tests\imageapi_optimize\Functional;

use Drupal\Tests\image\Functional\ImageStyleFlushTest as OriginalImageStyleFlushTest;

/**
 * Tests flushing of image styles.
 *
 * @group imageapi_optimize
 */
class ImageStyleFlushTest extends OriginalImageStyleFlushTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
