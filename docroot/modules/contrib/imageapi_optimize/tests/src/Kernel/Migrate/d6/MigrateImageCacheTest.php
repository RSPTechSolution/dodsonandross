<?php

namespace Drupal\Tests\imageapi_optimize\Kernel\Migrate\d6;

use Drupal\Tests\image\Kernel\Migrate\d6\MigrateImageCacheTest as OriginalMigrateImageCacheTest;

/**
 * Tests migration of ImageCache presets to image styles.
 *
 * @group imageapi_optimize
 */
class MigrateImageCacheTest extends OriginalMigrateImageCacheTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
