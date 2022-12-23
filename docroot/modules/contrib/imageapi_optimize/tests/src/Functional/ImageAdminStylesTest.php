<?php

namespace Drupal\Tests\imageapi_optimize\Functional;

use Drupal\Tests\image\Functional\ImageAdminStylesTest as OriginalImageAdminStylesTest;

/**
 * Tests creation, deletion, and editing of image styles and effects.
 *
 * @group imageapi_optimize
 */
class ImageAdminStylesTest extends OriginalImageAdminStylesTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
