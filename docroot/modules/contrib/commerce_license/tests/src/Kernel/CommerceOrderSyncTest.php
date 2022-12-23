<?php

namespace Drupal\Tests\commerce_license\Kernel;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\commerce_cart\Kernel\CartKernelTestBase;

/**
 * Tests changes to an order are synchronized to the license.
 *
 * See \Drupal\Tests\commerce_cart\Kernel\CartOrderPlacedTest for test code for
 * working with orders.
 *
 * @group commerce_license
 */
class CommerceOrderSyncTest extends CartKernelTestBase {

  /**
   * The order type.
   *
   * @var \Drupal\commerce_order\Entity\OrderType
   */
  protected $orderType;

  /**
   * The product variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $variationType;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The license storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $licenseStorage;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'interval',
    'recurring_period',
    'commerce_license',
    'commerce_license_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_license');
    $this->createUser();

    $this->licenseStorage = $this->container->get('entity_type.manager')->getStorage('commerce_license');

    // Create an order type for licenses which uses the fulfillment workflow.
    $this->orderType = $this->createEntity('commerce_order_type', [
      'id' => 'license_order_type',
      'label' => $this->randomMachineName(),
      'workflow' => 'order_default',
    ]);

    // Create an order item type that uses that order type.
    $order_item_type = $this->createEntity('commerce_order_item_type', [
      'id' => 'license_order_item_type',
      'label' => $this->randomMachineName(),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'license_order_type',
      'traits' => ['commerce_license_order_item_type'],
    ]);
    $trait_manager = \Drupal::service('plugin.manager.commerce_entity_trait');
    $trait = $trait_manager->createInstance('commerce_license_order_item_type');
    $trait_manager->installTrait($trait, 'commerce_order_item', $order_item_type->id());

    // Create a product variation type with the license trait, using our order
    // item type.
    $this->variationType = $this->createEntity('commerce_product_variation_type', [
      'id' => 'license_pv_type',
      'label' => $this->randomMachineName(),
      'orderItemType' => 'license_order_item_type',
      'traits' => ['commerce_license'],
    ]);
    $trait = $trait_manager->createInstance('commerce_license');
    $trait_manager->installTrait($trait, 'commerce_product_variation', $this->variationType->id());

    // Create a product variation which grants a license.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'license_pv_type',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'license_type' => [
        'target_plugin_id' => 'simple',
        'target_plugin_configuration' => [],
      ],
      // Use the unlimited expiry plugin as it's simple.
      'license_expiration' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);
    $this->reloadEntity($this->variation);
    $this->variation->save();

    // Create a user to use for orders.
    $this->user = $this->createUser();
  }

  /**
   * Tests a license is created when an order gets paid.
   */
  public function testOrderPaid() {
    $licenses = $this->licenseStorage->loadMultiple();
    $this->assertCount(0, $licenses, "There are no licenses yet.");

    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('license_order_type', $this->store, $this->user);
    $this->cartManager->addEntity($cart_order, $this->variation);

    $cart_order->set('total_paid', $cart_order->getTotalPrice());
    $cart_order->save();
    $order = $this->reloadEntity($cart_order);

    // Get the order item. There should be only one in the order.
    $order_item = $order->getItems()[0];

    // Check that the order item now refers to a new license which has been
    // created for the user.
    $licenses = $this->licenseStorage->loadMultiple();
    $this->assertCount(1, $licenses, "One license was saved.");
    $license = reset($licenses);

    $this->assertEquals($license->id(), $order_item->license->entity->id(), "The order item has a reference to the saved license.");

    $this->assertEquals('commerce_license', $license->getEntityTypeId(), 'The order item has a license entity set in its license field.');
    $this->assertEquals('simple', $license->bundle(), 'The license entity is of the expected type.');
    $this->assertEquals($this->user->id(), $license->getOwnerId(), 'The license entity has the expected owner.');
    $this->assertEquals($this->variation->id(), $license->getPurchasedEntity()->id(), 'The license entity references the product variation.');
    $this->assertEquals('active', $license->getState()->getId(), 'The license is active.');
    $this->assertEquals($order->id(), $license->getOriginatingOrderId());
    $this->assertNotEmpty($license->getOriginatingOrder());
    $this->assertInstanceOf(OrderInterface::class, $license->getOriginatingOrder());
    // Note that we don't need to check that the license has activated its
    // license type plugin, as that is covered by LicenseStateChangeTest.
  }

  /**
   * Tests a license is created on order place.
   */
  public function testCreateOnOrderPlace() {
    $order = $this->container->get('commerce_cart.cart_provider')->createCart('license_order_type', $this->store, $this->user);
    $this->cartManager->addEntity($order, $this->variation);
    $order->getState()->applyTransitionById('place');
    $order->save();

    $licenses = $this->licenseStorage->loadMultiple();
    $this->assertCount(1, $licenses, "One license was saved.");
    /** @var \Drupal\commerce_license\Entity\LicenseInterface $license */
    $license = reset($licenses);
    // Get the order item. There should be only one in the order.
    $order_item = $order->getItems()[0];
    $this->assertEquals($license->id(), $order_item->license->entity->id(), "The order item has a reference to the saved license.");

    $this->assertEquals('commerce_license', $license->getEntityTypeId(), 'The order item has a license entity set in its license field.');
    $this->assertEquals('simple', $license->bundle(), 'The license entity is of the expected type.');
    $this->assertEquals($this->user->id(), $license->getOwnerId(), 'The license entity has the expected owner.');
    $this->assertEquals($this->variation->id(), $license->getPurchasedEntity()->id(), 'The license entity references the product variation.');
    $this->assertEquals('pending', $license->getState()->getId(), 'The license is pending.');
    $this->assertEquals($order->id(), $license->getOriginatingOrderId());
    $this->assertNotEmpty($license->getOriginatingOrder());
    $this->assertInstanceOf(OrderInterface::class, $license->getOriginatingOrder());
  }

  /**
   * Tests a license is created and activate with the activate_on_place setting.
   */
  public function testActivateOnOrderPlace() {
    // Change the configuration of the order type to use validation.
    $this->orderType->set('workflow', 'order_default_validation');
    $this->orderType->save();

    // Change the configuration of the product variation type to activate as
    // soon as the customer places the order.
    $this->variationType->setThirdPartySetting('commerce_license', 'activate_on_place', TRUE);
    $this->variationType->save();

    $licenses = $this->licenseStorage->loadMultiple();
    $this->assertCount(0, $licenses, "There are no licenses yet.");

    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('license_order_type', $this->store, $this->user);
    $this->cartManager->addEntity($cart_order, $this->variation);

    // Place the order. This takes it only as far as the 'validation' state.
    $cart_order->getState()->applyTransitionById('place');
    $cart_order->save();

    $order = $this->reloadEntity($cart_order);

    // Get the order item. There should be only one in the order.
    $order_item = $order->getItems()[0];

    // Check that the order item now refers to a new license which has been
    // created for the user.
    $licenses = $this->licenseStorage->loadMultiple();
    $this->assertCount(1, $licenses, "One license was saved.");
    $license = reset($licenses);

    $this->assertEquals($license->id(), $order_item->license->entity->id(), "The order item has a reference to the saved license.");
    $this->assertEquals('commerce_license', $license->getEntityTypeId(), 'The order item has a license entity set in its license field.');
    $this->assertEquals('simple', $license->bundle(), 'The license entity is of the expected type.');
    $this->assertEquals($this->user->id(), $license->getOwnerId(), 'The license entity has the expected owner.');
    $this->assertEquals($this->variation->id(), $license->getPurchasedEntity()->id(), 'The license entity references the product variation.');
    $this->assertEquals('active', $license->getState()->getId(), 'The license is active.');
    $this->assertEquals($order->id(), $license->getOriginatingOrderId());
    $this->assertNotEmpty($license->getOriginatingOrder());
    $this->assertInstanceOf(OrderInterface::class, $license->getOriginatingOrder());
    // Note that we don't need to check that the license has activated its
    // license type plugin, as that is covered by LicenseStateChangeTest.
  }

  /**
   * Creates and saves a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new, saved entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id(),
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
