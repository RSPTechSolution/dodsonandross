<?php

namespace Drupal\Tests\commerce_license\Functional;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests our traits validation logic, submission.
 *
 * @group commerce_license
 */
class LicenseAdminTest extends CommerceBrowserTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_license',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product_type',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests editing a variation type.
   */
  public function testVariationTypeEdit() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $product_variation_type */
    $product_variation_type = ProductVariationType::load('default');
    $this->drupalGet($product_variation_type->toUrl('edit-form'));
    $this->getSession()->getPage()->checkField('traits[commerce_license]');
    $this->submitForm([], 'Save');

    /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
    $order_item_type = OrderItemType::load('default');
    $this->assertTrue($order_item_type->hasTrait('commerce_license_order_item_type'));
    $this->assertSession()->pageTextContains('The License trait requires an order item type with the order item license trait, it was automatically installed for your convenience.');
    $product_variation_type = $this->reloadEntity($product_variation_type);
    $third_party_settings = $product_variation_type->getThirdPartySettings('commerce_license');
    $this->assertNotEmpty($third_party_settings);
    $this->assertArrayHasKey('license_types', $third_party_settings);
    $this->assertArrayHasKey('activate_on_place', $third_party_settings);

    // Ensure the license third party settings are correctly cleaned up.
    $this->drupalGet($product_variation_type->toUrl('edit-form'));
    $this->getSession()->getPage()->uncheckField('traits[commerce_license]');
    $this->submitForm([], 'Save');
    $product_variation_type = $this->reloadEntity($product_variation_type);
    $this->assertEmpty($product_variation_type->getThirdPartySettings('commerce_license'));
  }

}
