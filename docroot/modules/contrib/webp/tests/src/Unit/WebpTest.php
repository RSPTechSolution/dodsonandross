<?php

namespace Drupal\Tests\webp\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Simple test to ensure that asserts pass.
 *
 * @group phpunit_example
 */
class WebpTest extends UnitTestCase {

  /**
   * Before a test method is run, setUp() is invoked.
   *
   * Create new unit object.
   */
  public function setUp() {
    // Mock the class to avoid the constructor.
    $this->webp = $this->getMockBuilder('\Drupal\webp\Webp')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
  }

  /**
   * @covers Drupal\webp\Webp::getWebpSrcset
   */
  public function testgetWebpSrcset() {
    $this->assertEquals("testimage.webp", $this->webp->getWebpSrcset("testimage.jpg"));
    $this->assertEquals("testimage2.webp", $this->webp->getWebpSrcset("testimage2.png"));
    $this->assertEquals("testimage2.webp", $this->webp->getWebpSrcset("testimage2.jpeg"));
    $this->assertEquals("testimage2.webp", $this->webp->getWebpSrcset("testimage2.jpg"));
    $this->assertEquals("testimage2.ext.webp", $this->webp->getWebpSrcset("testimage2.ext.jpg"));
    $this->assertEquals("testimage2.ext.ext.webp", $this->webp->getWebpSrcset("testimage2.ext.ext.jpg"));

    // Test that double extensions are handled properly.
    $this->assertEquals("testimage2.png.webp", $this->webp->getWebpSrcset("testimage2.png.jpg"));
    $this->assertEquals("testimage2.jpeg.png.webp", $this->webp->getWebpSrcset("testimage2.jpeg.png.jpg"));

    // Test source sets with width descriptor/pixel density and multiple images.
    $this->assertEquals("some/path/image.webp?itok=vOpRgtYZ 1x", $this->webp->getWebpSrcset("some/path/image.JPG?itok=vOpRgtYZ 1x"));
    $this->assertEquals("some/path/image.webp?itok=vOpRgtYZ 1x, some/path/image.webp?itok=vOpRgtYZ 2x", $this->webp->getWebpSrcset("some/path/image.JPG?itok=vOpRgtYZ 1x, some/path/image.JPG?itok=vOpRgtYZ 2x"));

    // Test source sets with multiple images but without width descriptor/pixel density.
    $this->assertEquals("some/path/image.webp?itok=vOpRgtYZ, some/path/image.webp?itok=vOpRgtYZ", $this->webp->getWebpSrcset("some/path/image.JPG?itok=vOpRgtYZ, some/path/image.JPG?itok=vOpRgtYZ"));

    // And multiple source sets with multiple images.
    $this->assertEquals("some/path/image.png.webp?itok=vOpRgtYZ 1x, some/path/image.jpg.ext.webp?itok=vOpRgtYZ 2x", $this->webp->getWebpSrcset("some/path/image.png.JPG?itok=vOpRgtYZ 1x, some/path/image.jpg.ext.JPG?itok=vOpRgtYZ 2x"));
    $this->assertEquals("some/path/image.png.webp?itok=vOpRgtYZ, some/path/image.jpg.ext.webp?itok=vOpRgtYZ", $this->webp->getWebpSrcset("some/path/image.png.JPG?itok=vOpRgtYZ, some/path/image.jpg.ext.JPG?itok=vOpRgtYZ"));
  }

}
