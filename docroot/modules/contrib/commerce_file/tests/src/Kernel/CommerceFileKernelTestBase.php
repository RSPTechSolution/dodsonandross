<?php

namespace Drupal\Tests\commerce_file\Kernel;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Provides a base class for Commerce File kernel tests.
 */
abstract class CommerceFileKernelTestBase extends OrderKernelTestBase {

  /**
   * A test file.
   *
   * @var \Drupal\file\FileInterface;
   */
  protected $file;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'interval',
    'recurring_period',
    'file',
    'commerce_license',
    'commerce_file',
  ];

  /**
   * A test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('commerce_file', ['commerce_file_download_log']);
    $this->installConfig(['commerce_file']);
    $this->installEntitySchema('commerce_license');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->createUser();
    $variation_traits = ['commerce_license', 'commerce_file'];
    $product_variation_type = ProductVariationType::load('default');
    $product_variation_type->setTraits($variation_traits);
    $product_variation_type->save();

    $trait_manager = $this->container->get('plugin.manager.commerce_entity_trait');
    $trait = $trait_manager->createInstance('commerce_license_order_item_type');
    $trait_manager->installTrait($trait, 'commerce_order_item', 'default');
    $order_item_type = OrderItemType::load('default');
    $order_item_type->setTraits(['commerce_license_order_item_type']);
    $order_item_type->save();

    foreach ($variation_traits as $trait) {
      $trait = $trait_manager->createInstance($trait);
      $trait_manager->installTrait($trait, 'commerce_product_variation', 'default');
    }

    $file = File::create([
      'uid' => 1,
      'filename' => $this->randomMachineName(),
      'uri' => 'private://test.txt',
      'filemime' => 'text/plain',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();
    $this->file = $this->reloadEntity($file);

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
      'license_type' => [
        'target_plugin_id' => 'commerce_file',
        'target_plugin_configuration' => [
          'file_download_limit' => 2,
        ],
      ],
      'commerce_file' => [$file],
      'license_expiration' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $variation->save();
    $this->variation = $this->reloadEntity($variation);
  }

}
