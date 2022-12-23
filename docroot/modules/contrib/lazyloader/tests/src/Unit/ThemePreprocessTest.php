<?php

namespace Drupal\Tests\lazyloader\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\lazyloader\ThemePreprocess;
use PHPUnit\Framework\TestCase;

/**
 * Tests preprocessing.
 *
 * @coversDefaultClass \Drupal\lazyloader\ThemePreprocess
 * @group lazyloader
 */
class ThemePreprocessTest extends TestCase {

  /**
   * Tests that cache tags are properly added to the render array.
   *
   * @test
   */
  public function addsCacheTagToRenderArray() {
    $sut = new ThemePreprocess($this->createMock(ConfigFactoryInterface::class));

    $expected = ['#cache' => ['tags' => [0 => 'config:lazyloader.configuration']]];

    $this->assertEquals($expected, $sut->addCacheTags([]));
  }

}
