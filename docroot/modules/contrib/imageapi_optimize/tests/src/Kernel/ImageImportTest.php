<?php

namespace Drupal\Tests\imageapi_optimize\Kernel;

use Drupal\Tests\image\Kernel\ImageImportTest as OriginalImageImportTest;

/**
 * Tests config import for Image styles.
 *
 * @group imageapi_optimize
 */
class ImageImportTest extends OriginalImageImportTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
