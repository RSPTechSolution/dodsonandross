<?php

namespace Drupal\Tests\commerce_license\FunctionalJavascript;

use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the admin UI for licenses.
 *
 * @group commerce_license
 */
class LicenseAdminTest extends CommerceWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'path',
    'commerce_product',
    'commerce_license',
  ];

  /**
   * A test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'access commerce_license overview',
      'administer commerce_license',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $trait_manager = \Drupal::service('plugin.manager.commerce_entity_trait');
    $this->createEntity('commerce_product_variation_type', [
      'id' => 'license_pv_type',
      'label' => $this->randomMachineName(),
      'orderItemType' => 'default',
      'traits' => ['commerce_license'],
    ]);
    $trait = $trait_manager->createInstance('commerce_license');
    $trait_manager->installTrait($trait, 'commerce_product_variation', 'license_pv_type');
    $roles = $this->adminUser->getRoles(TRUE);
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'license_pv_type',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 5.55,
        'currency_code' => 'USD',
      ],
      'license_type' => [
        'target_plugin_id' => 'role',
        'target_plugin_configuration' => [
          'license_role' => reset($roles),
        ],
      ],
    ]);
  }

  /**
   * Tests creating a license.
   */
  public function testCreateLicense() {
    $this->drupalGet('admin/commerce/licenses');
    $this->getSession()->getPage()->clickLink('Add license');

    // Check the integrity of the form.
    $this->assertSession()->fieldExists('product_variation[0][target_id]');
    $this->assertSession()->fieldExists('uid[0][target_id]');
    $this->assertSession()->fieldExists('expiration_type[0][target_plugin_id]');
    $this->assertSession()->fieldExists('license_role');
    $this->getSession()->getPage()->selectFieldOption('expiration_type[0][target_plugin_id]', 'rolling_interval');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('expiration_type[0][target_plugin_configuration][rolling_interval][interval][interval]', 30);
    $this->getSession()->getPage()->selectFieldOption('expiration_type[0][target_plugin_configuration][rolling_interval][interval][period]', 'day');
    $this->assertSession()->optionNotExists('license_role', RoleInterface::AUTHENTICATED_ID);
    $this->assertSession()->optionNotExists('license_role', RoleInterface::ANONYMOUS_ID);
    $this->getSession()->getPage()->fillField('product_variation[0][target_id]', $this->variation->getSku() . ' (' . $this->variation->id() . ')');
    $roles = $this->adminUser->getRoles(TRUE);
    $this->getSession()->getPage()->selectFieldOption('license_role', reset($roles));
    $this->submitForm([], t('Save'));

    /** @var \Drupal\commerce_license\Entity\LicenseInterface $license */
    $license = $this->container->get('entity_type.manager')->getStorage('commerce_license')->load(1);
    $this->assertEquals('role', $license->bundle());
    $this->assertEquals($this->variation->id(), $license->getPurchasedEntity()->id());
    $this->assertEquals('new', $license->getState()->getId());
    $this->assertEquals('rolling_interval', $license->getExpirationPluginType());
    $this->assertEquals([
      'interval' => [
        'interval' => '30',
        'period' => 'day',
      ],
    ], $license->get('expiration_type')->target_plugin_configuration);
  }

}
