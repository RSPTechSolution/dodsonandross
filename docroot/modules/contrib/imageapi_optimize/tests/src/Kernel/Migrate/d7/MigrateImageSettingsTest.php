<?php

namespace Drupal\Tests\imageapi_optimize\Kernel\Migrate\d7;

use Drupal\Tests\image\Kernel\Migrate\d7\MigrateImageSettingsTest as OriginalMigrateImageSettingsTest;

/**
 * Tests migration of Image variables to configuration.
 *
 * @group imageapi_optimize
 */
class MigrateImageSettingsTest extends OriginalMigrateImageSettingsTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
