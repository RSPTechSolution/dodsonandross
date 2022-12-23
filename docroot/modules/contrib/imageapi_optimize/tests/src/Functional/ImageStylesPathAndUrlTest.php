<?php

namespace Drupal\Tests\imageapi_optimize\Functional;

use Drupal\Tests\image\Functional\ImageStylesPathAndUrlTest as OriginalImageStylesPathAndUrlTest;

/**
 * Tests the functions for generating paths and URLs for image styles.
 *
 * @group imageapi_optimize
 */
class ImageStylesPathAndUrlTest extends OriginalImageStylesPathAndUrlTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
