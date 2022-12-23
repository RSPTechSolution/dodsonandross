<?php

namespace Drupal\Tests\imageapi_optimize\Kernel\Migrate\d7;

use Drupal\Tests\image\Kernel\Migrate\d7\MigrateImageStylesTest as OriginalMigrateImageStylesTest;

/**
 * Test image styles migration to config entities.
 *
 * @group imageapi_optimize
 */
class MigrateImageStylesTest extends OriginalMigrateImageStylesTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
