<?php

namespace Drupal\Tests\commerce_file\Kernel;

use Drupal\commerce_license\Entity\License;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Tests the license file manager.
 *
 * @group commerce_file
 * @coversDefaultClass \Drupal\commerce_file\LicenseFileManager
 */
class LicenseFileManagerTest extends CommerceFileKernelTestBase {

  /**
   * A test file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * A test license.
   *
   * @var \Drupal\commerce_license\Entity\LicenseInterface
   */
  protected $license;

  /**
   * The license file manager.
   *
   * @var \Drupal\commerce_file\LicenseFileManagerInterface
   */
  protected $licenseFileManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $test_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['view own commerce_license']);
    $license = License::create([
      'type' => 'commerce_file',
      'state' => 'active',
      'uid' => $test_user->id(),
      'product_variation' => $this->variation,
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
      'file_download_limit' => 2,
    ]);
    $license->save();
    $this->license = $this->reloadEntity($license);

    $this->licenseFileManager = $this->container->get('commerce_file.license_file_manager');
  }

  /**
   * @covers ::canDownload
   */
  public function testCanDownload() {
    $account = $this->createUser([], ['bypass license control']);
    $this->assertTrue($this->licenseFileManager->canDownload($this->license, $this->file, $account));
    $account = $this->createUser([], ['administer commerce_license']);
    $this->assertTrue($this->licenseFileManager->canDownload($this->license, $this->file, $account));
    $account = $this->createUser();
    $this->assertFalse($this->licenseFileManager->canDownload($this->license, $this->file, $account));

    // Ensure the license owner can download its own license.
    $this->assertTrue($this->licenseFileManager->canDownload($this->license, $this->file));
    /** @var \Drupal\commerce_file\DownloadLoggerInterface $download_logger */
    $download_logger = $this->container->get('commerce_file.download_logger');
    $download_logger->log($this->license, $this->file);
    $this->assertTrue($this->licenseFileManager->canDownload($this->license, $this->file));
    $download_logger->log($this->license, $this->file);
    $this->assertFalse($this->licenseFileManager->canDownload($this->license, $this->file));
  }

  /**
   * @covers ::getActiveLicenses
   */
  public function testGetActiveLicenses() {
    $active_licenses = $this->licenseFileManager->getActiveLicenses($this->file, $this->license->getOwner());
    $this->assertNotEmpty($active_licenses);
    $this->assertInstanceOf(LicenseInterface::class, $active_licenses[0]);
    $this->assertEquals($this->license->id(), $active_licenses[0]->id());
    $account = $this->createUser();
    $this->assertEmpty($this->licenseFileManager->getActiveLicenses($this->file, $account));
    $this->license->set('state', 'canceled');
    $this->license->save();
    $this->assertEmpty($this->licenseFileManager->getActiveLicenses($this->file, $this->license->getOwner()));
  }

  /**
   * @covers ::getDownloadLimit
   */
  public function testDownloadLimit() {
    $this->assertEquals(2, $this->licenseFileManager->getDownloadLimit($this->license));
    $this->license->set('file_download_limit', 0);
    $this->assertEquals(0, $this->licenseFileManager->getDownloadLimit($this->license));
    $this->license->set('file_download_limit', NULL);
    $this->assertEquals(0, $this->licenseFileManager->getDownloadLimit($this->license));
    $this->config('commerce_file.settings')
      ->set('enable_download_limit', TRUE)
      ->set('download_limit', 50)
      ->save();
    $this->assertEquals(50, $this->licenseFileManager->getDownloadLimit($this->license));
  }

  /**
   * @covers ::isLicensable
   */
  public function testIsLicensable() {
    $this->assertTrue($this->licenseFileManager->isLicensable($this->file));
    $another_file = File::create([
      'uid' => 1,
      'filename' => $this->randomMachineName(),
      'uri' => 'private://test2.txt',
      'filemime' => 'text/plain',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $another_file->save();
    $this->assertFalse($this->licenseFileManager->isLicensable($another_file));
  }

  /**
   * @covers ::shouldLogDownload
   */
  public function testShouldLogDownload() {
    $account = $this->createUser([], ['bypass license control']);
    $this->assertFalse($this->licenseFileManager->shouldLogDownload($this->license, $account));
    $account = $this->createUser([], ['administer commerce_license']);
    $this->assertFalse($this->licenseFileManager->shouldLogDownload($this->license, $account));
    $this->assertTrue($this->licenseFileManager->shouldLogDownload($this->license, $this->license->getOwner()));
  }

}
