<?php

namespace Drupal\Tests\commerce_license\Kernel\System;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests that cron expires a license.
 *
 * @group commerce_license
 */
class LicenseCronExpiryTest extends OrderKernelTestBase {

  use AssertMailTrait;

  /**
   * The number of seconds in one day.
   */
  const TIME_ONE_DAY = 60 * 60 * 24;

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'advancedqueue',
    'commerce_license',
    'commerce_license_test',
    'interval',
    'recurring_period',
    'commerce_license_set_expiry_test',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('advancedqueue', 'advancedqueue');
    $this->installEntitySchema('commerce_license');
    // This is just duplicates of the queues in the real module, as we don't
    // want to have to install the admin view in this test.
    $this->installConfig('commerce_license_set_expiry_test');

    $this->cron = \Drupal::service('cron');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * Returns the current time.
   *
   * @return int
   *   The time.
   */
  protected static function today() {
    return time();
  }

  /**
   * Returns the time a day ago.
   *
   * @return int
   *   Yesterday's time.
   */
  protected static function yesterday() {
    return self::today() - self::TIME_ONE_DAY;
  }

  /**
   * Returns the time a day from now.
   *
   * @return int
   *   Tomorrow's time.
   */
  protected static function tomorrow() {
    return self::today() + self::TIME_ONE_DAY;
  }

  /**
   * Gets a protected method for testing.
   *
   * @param string $class
   *   Name of the class.
   * @param string $name
   *   Name of the method.
   *
   * @return mixed
   *   The method.
   */
  protected static function getMethod($class, $name) {
    $class = new \ReflectionClass($class);
    $method = $class->getMethod($name);
    $method->setAccessible(TRUE);
    return $method;
  }

  /**
   * License hasn't expired.
   *
   * Tests that getLicenseIdsToExpire doesn't return a license that hasn't
   * expired yet.
   */
  public function testGetLicenseIdsToExpireTomorrow() {
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');

    $license_owner = $this->createUser();

    // Create a license in the 'active' state.
    $license = $license_storage->create([
      'type' => 'simple',
      'state' => 'active',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();

    // Force the expiration timestamp.
    // As the state is not being changed, the expiration plugin won't be called.
    $license->expires = self::tomorrow();
    $license->save();

    // Test getLicensesToExpire() method.
    $cron = \Drupal::service('commerce_license.cron');
    $getLicenseIdsToExpire = self::getMethod('\Drupal\commerce_license\Cron', 'getLicensesToExpire');
    $expire_ids = $getLicenseIdsToExpire->invokeArgs($cron, [self::today()]);
    $this->assertEquals([], $expire_ids, "The license ID is not returned by the expiration query.");
  }

  /**
   * License has expired.
   *
   * Tests that getLicenseIdsToExpire doesn't return a license that hasn't
   * expired yet.
   */
  public function testGetLicenseIdsToExpireYesterday() {
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');

    $license_owner = $this->createUser();

    // Create a license in the 'active' state.
    $license = $license_storage->create([
      'type' => 'simple',
      'state' => 'active',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();

    // Force the expiration timestamp.
    // As the state is not being changed, the expiration plugin won't be called.
    $license->expires = self::yesterday();
    $license->save();

    // Test getLicensesToExpire() method.
    $cron = \Drupal::service('commerce_license.cron');
    $getLicenseIdsToExpire = self::getMethod('\Drupal\commerce_license\Cron', 'getLicensesToExpire');
    $expire_ids = $getLicenseIdsToExpire->invokeArgs($cron, [self::today()]);
    $this->assertEquals([$license->id() => $license->id()], $expire_ids, "The license ID is returned by the expiration query.");
  }

  /**
   * Tests that a cron run won't expire a current license.
   */
  public function testLicenseCronExpiryCurrent() {
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');

    $license_owner = $this->createUser();

    // Create a license in the 'active' state.
    $license = $license_storage->create([
      'type' => 'simple',
      'state' => 'active',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();

    // Force the expiration timestamp.
    // As the state is not being changed, the expiration plugin won't be called.
    $license->expires = self::tomorrow();
    $license->save();

    $this->cron->run();

    // Check the license has not been changed.
    $this->assertEquals('active', $license->getState()->getId(), "The license has not been changed and is still active.");

    $queue = $this->container->get('queue')->get('commerce_license_expire');
    $this->assertEquals(0, $queue->numberOfItems(), 'The license item was not added to the queue.');
  }

  /**
   * Tests that a cron run expires an expired license.
   */
  public function testLicenseCronExpiryExpired() {
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');

    $license_owner = $this->createUser();

    // Create a license in the 'active' state.
    $license = $license_storage->create([
      'type' => 'simple',
      'state' => 'active',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();

    // Force the expiration timestamp.
    // As the state is not being changed, the expiration plugin won't be called.
    $license->expires = self::yesterday();
    $license->save();

    // This cron run sets up the queued jobs.
    $this->cron->run();

    $license = $this->reloadEntity($license);

    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_license');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_QUEUED => 1], $counts);

    $job1 = $queue->getBackend()->claimJob();
    $this->assertArraySubset(['license_id' => $license->id()], $job1->getPayload());
    $this->assertEquals('commerce_license_expire', $job1->getType());
  }

  /**
   * Tests that the LicenseExpire job expires the license.
   */
  public function testLicenseExpireJob() {
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');

    $license_owner = $this->createUser();

    // Create a license in the 'active' state.
    $license = $license_storage->create([
      'type' => 'simple',
      'state' => 'active',
      'product' => 1,
      'uid' => $license_owner->id(),
      // Use the unlimited expiry plugin as it's simple.
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();

    // Force the expiration timestamp.
    // As the state is not being changed, the expiration plugin won't be called.
    $license->expires = self::yesterday();
    $license->save();

    $license = $this->reloadEntity($license);
    $this->assertEquals('active', $license->getState()->getId(), "The license is currently active.");

    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_license');
    $job = Job::create('commerce_license_expire', ['license_id' => $license->id()]);
    $queue->enqueueJob($job);
    $processor = $this->container->get('advancedqueue.processor');
    $num_processed = $processor->processQueue($queue);
    $this->assertEquals(1, $num_processed);
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_SUCCESS => 1], $counts);

    $license = $this->reloadEntity($license);
    $this->assertEquals('expired', $license->getState()->getId(), "The license is now expired.");

    // Note that we don't need to check that the expiry did something, as that
    // is covered by LicenseStateChangeTest.
    // Check the notification email is now queued.
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $notification_queue = Queue::load('commerce_license_notify');
    $counts = array_filter($notification_queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_QUEUED => 1], $counts);

    // Run the notification queue.
    $num_processed = $processor->processQueue($notification_queue);
    $this->assertEquals(1, $num_processed);
    $counts = array_filter($notification_queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_SUCCESS => 1], $counts);

    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));

    $expiry_email = reset($mails);
    $this->assertEquals('text/html; charset=UTF-8;', $expiry_email['headers']['Content-Type']);
    $this->assertEquals('8Bit', $expiry_email['headers']['Content-Transfer-Encoding']);
    $this->assertMailString('subject', 'Your purchase of test license has now expired', 1);
    $this->assertMailString('body', 'License Expiry', 1);
    $this->assertMailString('body', 'Your purchase of test license has now expired', 1);
    // TODO add a product to test the product text.
  }

}
