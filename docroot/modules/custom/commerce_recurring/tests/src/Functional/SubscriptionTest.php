<?php

namespace Drupal\Tests\commerce_recurring\Functional;

use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the subscription UI.
 *
 * @group commerce_recurring
 */
class SubscriptionTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
    'commerce_recurring',
  ];

  /**
   * The test billing schedule.
   *
   * @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface
   */
  protected $billingSchedule;

  /**
   * Holds the date pattern string for the "html_date" format.
   *
   * @var string
   */
  protected $dateFormat;

  /**
   * Holds the date pattern string for the "html_time" format.
   *
   * @var string
   */
  protected $timeFormat;

  /**
   * The test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '39.99',
        'currency_code' => 'USD',
      ],
    ]);
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$this->variation],
      'stores' => [$this->store],
    ]);
    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $this->billingSchedule = $this->createEntity('commerce_billing_schedule', [
      'id' => 'test_id',
      'label' => 'Hourly schedule',
      'displayLabel' => 'Hourly schedule',
      'billingType' => BillingSchedule::BILLING_TYPE_POSTPAID,
      'plugin' => 'fixed',
      'configuration' => [
        'interval' => [
          'number' => '1',
          'unit' => 'hour',
        ],
      ],
    ]);
    $this->dateFormat = DateFormat::load('html_date')->getPattern();
    $this->timeFormat = DateFormat::load('html_time')->getPattern();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'administer commerce_subscription',
    ] + parent::getAdministratorPermissions();
  }

  /**
   * Tests creating a subscription.
   */
  public function testSubscriptionCreation() {
    $this->drupalGet('admin/commerce/subscriptions/add');
    $page = $this->getSession()->getPage();
    $page->clickLink('Product variation');
    $this->assertSession()->addressEquals('admin/commerce/subscriptions/product_variation/add');
    $start_date = DrupalDateTime::createFromTimestamp(time() + 3600);

    $values = [
      'title[0][value]' => 'Test subscription',
      'billing_schedule' => $this->billingSchedule->id(),
      'purchased_entity[0][target_id]' => $this->variation->getTitle() . ' (' . $this->variation->id() . ')',
      'uid[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
      'unit_price[0][number]' => '9.99',
      'starts[0][value][date]' => $start_date->format($this->dateFormat),
      'starts[0][value][time]' => $start_date->format($this->timeFormat),
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('A subscription been successfully saved.');

    $subscription = Subscription::load(1);
    $this->assertSession()->pageTextContains($subscription->getTitle());
    $this->assertSession()->pageTextContains($subscription->getState()->getId());
    $this->assertSession()->pageTextContains($subscription->getBillingSchedule()->label());
    $this->assertEquals($values['title[0][value]'], $subscription->getTitle());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertNull($subscription->getTrialStartTime());
    $this->assertNull($subscription->getTrialEndTime());
    $this->assertNull($subscription->getEndTime());
    $this->assertEquals($start_date->getTimestamp(), $subscription->getStartTime());
    $this->assertNull($subscription->getPaymentMethod());
    $this->assertEquals($subscription->getCustomerId(), $this->adminUser->id());
    $this->assertEquals('pending', $subscription->getState()->getId());
  }

  /**
   * Tests editing a subscription.
   */
  public function testSubscriptionEditing() {
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = $this->createEntity('commerce_subscription', [
      'title' => $this->randomString(),
      'uid' => $this->adminUser->id(),
      'billing_schedule' => $this->billingSchedule,
      'type' => 'product_variation',
      'purchased_entity' => $this->variation,
      'store_id' => $this->store->id(),
      'unit_price' => $this->variation->getPrice(),
      'starts' => time() + 3600,
      'trial_starts' => time(),
      'state' => 'trial',
    ]);
    $trial_end = DrupalDateTime::createFromTimestamp($subscription->getStartTime());
    $end = DrupalDateTime::createFromTimestamp($subscription->getStartTime() + 7200);
    $this->drupalGet('admin/commerce/subscriptions/' . $subscription->id() . '/edit');
    $this->assertSession()->pageTextContains('Trial');
    $values = [
      'title[0][value]' => 'Test (Modified)',
      'trial_ends[0][has_value]' => 1,
      'trial_ends[0][container][value][date]' => $trial_end->format($this->dateFormat),
      'trial_ends[0][container][value][time]' => $trial_end->format($this->timeFormat),
      'ends[0][has_value]' => 1,
      'ends[0][container][value][date]' => $end->format($this->dateFormat),
      'ends[0][container][value][time]' => $end->format($this->timeFormat),
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('A subscription been successfully saved.');
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals($values['title[0][value]'], $subscription->getTitle());
    $this->assertSession()->pageTextContains($subscription->getTitle());
    $this->assertNotEmpty($subscription->getTrialStartTime());
    $this->assertEquals($subscription->getStartTime(), $subscription->getTrialEndTime());
  }

}
