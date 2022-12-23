<?php

namespace Drupal\Tests\commerce_file\Functional;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Provides a base class for Commerce File functional tests.
 */
abstract class FileBrowserTestBase extends CommerceBrowserTestBase {

  /**
   * Test files.
   *
   * @var \Drupal\file\FileInterface[]
   */
  protected $files;

  /**
   * A test user with little privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

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
  protected function setUp(): void {
    parent::setUp();

    $variation_traits = ['commerce_license', 'commerce_file'];
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface. $product_variation_type */
    $product_variation_type = ProductVariationType::load('default');
    $product_variation_type->setThirdPartySetting('commerce_license', 'activate_on_place', TRUE);
    $product_variation_type->setTraits($variation_traits);
    $product_variation_type->save();

    $trait_manager = $this->container->get(
      'plugin.manager.commerce_entity_trait'
    );
    $trait = $trait_manager->createInstance('commerce_license_order_item_type');
    $trait_manager->installTrait($trait, 'commerce_order_item', 'default');
    $order_item_type = OrderItemType::load('default');
    $order_item_type->setTraits(['commerce_license_order_item_type']);
    $order_item_type->save();

    foreach ($variation_traits as $trait) {
      $trait = $trait_manager->createInstance($trait);
      $trait_manager->installTrait(
        $trait,
        'commerce_product_variation',
        'default'
      );
    }

    // Create several test files.
    for ($i = 0; $i < 3; $i++) {
      $machine_name = $this->randomMachineName();
      $uri = "private://$machine_name.txt";
      file_put_contents($uri, $machine_name);
      $file = File::create([
        'uid' => 1,
        'filename' => $machine_name,
        'uri' => $uri,
        'filemime' => 'text/plain',
        'status' => FileInterface::STATUS_PERMANENT,
      ]);
      $file->save();
      $this->files[] = $this->reloadEntity($file);
    }

    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
      'license_type' => [
        'target_plugin_id' => 'commerce_file',
        'target_plugin_configuration' => [
          'file_download_limit' => 3,
        ],
      ],
      'commerce_file' => $this->files,
      'license_expiration' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My license file',
      'variations' => [$this->variation],
      'stores' => [$this->store],
    ]);

    $this->user = $this->createUser(['view commerce_product', 'access checkout', 'view own commerce_license']);
    $this->drupalLogin($this->user);
  }

}
