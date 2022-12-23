<?php

namespace Drupal\Tests\imageapi_optimize\Functional;

use Drupal\Tests\image\Functional\ImageFieldWidgetTest as OriginalImageFieldWidgetTest;

/**
 * Tests the image field widget.
 *
 * @group imageapi_optimize
 */
class ImageFieldWidgetTest extends OriginalImageFieldWidgetTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
