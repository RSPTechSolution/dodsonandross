<?php

namespace Drupal\Tests\imageapi_optimize\Kernel\Views;

use Drupal\Tests\image\Kernel\Views\RelationshipUserImageDataTest as OriginalRelationshipUserImageDataTest;

/**
 * Tests image on user relationship handler.
 *
 * @group imageapi_optimize
 */
class RelationshipUserImageDataTest extends OriginalRelationshipUserImageDataTest {

 /**
  * {@inheritdoc}
  */
  public static $modules = ['imageapi_optimize'];

    /**
     * Views used by this test.
     *
     * @var array
     */
    public static $testViews = [
    ];

}
