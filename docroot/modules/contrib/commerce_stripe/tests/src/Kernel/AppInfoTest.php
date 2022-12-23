<?php

namespace Drupal\Tests\commerce_stripe\Kernel;

use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_price\MinorUnitsConverterInterface;
use Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway\Stripe;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\KernelTests\KernelTestBase;
use Stripe\Stripe as StripeLibrary;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the Stripe app information.
 *
 * @note This cannot be a Unit test due to dependency on system_get_info().
 *
 * @group commerce_stripe
 */
class AppInfoTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'commerce_stripe',
  ];

  /**
   * Tests Stripe app info set during plugin initialization.
   */
  public function testStripeAppInfo() {
    $extension_list = $this->prophesize(ModuleExtensionList::class);
    $extension_list->getExtensionInfo('commerce_stripe')->willReturn([
      'version' => '8.x-1.0-test',
    ]);
    $secret_key = $this->randomMachineName();
    new Stripe(
      ['secret_key' => $secret_key],
      'stripe',
      [
        'payment_type' => 'default',
        'payment_method_types' => ['credit_card'],
        'forms' => [],
        'modes' => ['test', 'prod'],
        'display_label' => 'Stripe',
      ],
      $this->prophesize(EntityTypeManagerInterface::class)->reveal(),
      $this->prophesize(PaymentTypeManager::class)->reveal(),
      $this->prophesize(PaymentMethodTypeManager::class)->reveal(),
      $this->prophesize(TimeInterface::class)->reveal(),
      $this->prophesize(MinorUnitsConverterInterface::class)->reveal(),
      $this->prophesize(EventDispatcherInterface::class)->reveal(),
      $extension_list->reveal()
    );

    $app_info = StripeLibrary::getAppInfo();
    $this->assertEquals([
      'name' => 'Centarro Commerce for Drupal',
      'partner_id' => 'pp_partner_Fa3jTqCJqTDtHD',
      'url' => 'https://www.drupal.org/project/commerce_stripe',
      'version' => '8.x-1.0-test',
    ], $app_info);
  }

}
