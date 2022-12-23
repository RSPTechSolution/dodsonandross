<?php

namespace Drupal\Tests\imageapi_optimize\Kernel;

use Drupal\Tests\image\Kernel\ImageStyleIntegrationTest as OriginalImageStyleIntegrationTest;

/**
 * Tests the integration of ImageStyle with the core.
 *
 * @group imageapi_optimize
 */
class ImageStyleIntegrationTest extends OriginalImageStyleIntegrationTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
