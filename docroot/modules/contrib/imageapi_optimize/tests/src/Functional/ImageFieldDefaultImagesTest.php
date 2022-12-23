<?php

namespace Drupal\Tests\imageapi_optimize\Functional;

use Drupal\Tests\image\Functional\ImageFieldDefaultImagesTest as OriginalImageFieldDefaultImagesTest;

/**
 * Tests setting up default images both to the field and field storage.
 *
 * @group imageapi_optimize
 */
class ImageFieldDefaultImagesTest extends OriginalImageFieldDefaultImagesTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
