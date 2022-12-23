<?php

namespace Drupal\Tests\imageapi_optimize\FunctionalJavascript;

use Drupal\Tests\image\FunctionalJavascript\ImageFieldValidateTest as OriginalImageFieldValidateTest;

/**
 * Tests validation functions such as min/max resolution.
 *
 * @group imageapi_optimize
 */
class ImageFieldValidateTest extends OriginalImageFieldValidateTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
