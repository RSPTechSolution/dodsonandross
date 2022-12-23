<?php

namespace Drupal\Tests\commerce_file\Kernel;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the file download logger.
 *
 * @group commerce_file
 * @coversDefaultClass \Drupal\commerce_file\DownloadLogger
 */
class DownloadLoggerTest extends KernelTestBase {

  /**
   * The download logger.
   *
   * @var \Drupal\commerce_file\DownloadLoggerInterface
   */
  protected $downloadLogger;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_license',
    'commerce_file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('commerce_file', ['commerce_file_download_log']);
    $this->downloadLogger = $this->container->get('commerce_file.download_logger');
  }

  /**
   * Tests the download logger.
   *
   * @covers ::clear
   * @covers ::getDownloadCounts
   * @covers ::log
   */
  public function testDownloadLog() {
    $license = $this->prophesize(LicenseInterface::class);
    $license->id()->willReturn(100);
    $license->getCacheTagsToInvalidate()->willReturn([]);
    $license->getOwnerId()->willReturn(2);
    $purchased_entity = $this->prophesize(ProductVariationInterface::class);
    $purchased_entity->hasField('commerce_file')->willReturn(TRUE);

    $file_reference = $this->prophesize(EntityReferenceItem::class);
    $file_reference->isEmpty()->willReturn(FALSE);
    $file_reference->getValue()->willReturn([
      ['target_id' => 10],
      ['target_id' => 15],
    ]);
    $file_reference->reveal();

    $purchased_entity->get('commerce_file')->willReturn($file_reference);
    $purchased_entity->reveal();
    $license->getPurchasedEntity()->willReturn($purchased_entity);
    $license = $license->reveal();

    $file = $this->prophesize(FileInterface::class);
    $file->id()->willReturn(10);
    $file = $file->reveal();

    $this->downloadLogger->log($license, $file);
    $this->assertEquals([10 => 1, 15 => 0], $this->downloadLogger->getDownloadCounts($license));
    $this->downloadLogger->log($license, $file);
    $this->assertEquals([10 => 2, 15 => 0], $this->downloadLogger->getDownloadCounts($license));

    $this->downloadLogger->clear($license);
    $this->assertEquals([10 => 0, 15 => 0], $this->downloadLogger->getDownloadCounts($license));
  }

  /**
   * Tests that an exception is thrown when passing an invalid license.
   *
   * @covers ::getDownloadCounts
   */
  public function testDownloadCountException() {
    $license = $this->prophesize(LicenseInterface::class);
    $license->id()->willReturn(100);
    $license->getOwnerId()->willReturn(2);
    $purchased_entity = $this->prophesize(ProductVariationInterface::class);
    $purchased_entity->hasField('commerce_file')->willReturn(FALSE);
    $purchased_entity->reveal();
    $license->getPurchasedEntity()->willReturn($purchased_entity);
    $license = $license->reveal();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The purchased entity referenced by the given license does not reference any file.');
    $this->downloadLogger->getDownloadCounts($license);
  }

}
