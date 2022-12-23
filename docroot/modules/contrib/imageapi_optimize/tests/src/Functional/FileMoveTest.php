<?php

namespace Drupal\Tests\imageapi_optimize\Functional;

use Drupal\Tests\image\Functional\FileMoveTest as OriginalFileMoveTest;

/**
 * Tests the file move function for images and image styles.
 *
 * @group imageapi_optimize
 */
class FileMoveTest extends OriginalFileMoveTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

}
