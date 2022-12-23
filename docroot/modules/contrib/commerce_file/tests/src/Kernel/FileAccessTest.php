<?php

namespace Drupal\Tests\commerce_file\Kernel;

use Drupal\commerce_license\Entity\License;
use Drupal\commerce_product\Entity\Product;

/**
 * Tests the file access.
 *
 * @group commerce_file
 */
class FileAccessTest extends CommerceFileKernelTestBase {

  /**
   * Tests the file access.
   */
  public function testFileAccess() {
    // The file is licensable, despite the fact that the user has the right
    // to view the product variation referencing the file, access is not allowed
    // since a license is necessary in order to view/download the file.
    $account = $this->createUser([], ['view commerce_product', 'view own commerce_license']);
    // A product has to reference the variation, since the variation access is
    // determined by checking the access on parent product.
    $product = Product::create([
      'type' => 'default',
      'title' => 'My license file',
      'variations' => [$this->variation],
      'stores' => [$this->store],
    ]);
    $product->save();
    $this->assertFalse($this->file->access('view', $account));
    $this->assertFalse($this->file->access('download', $account));

    $another_user = $this->createUser([], ['bypass license control', 'view commerce_product']);
    $this->assertTrue($this->file->access('view', $another_user));
    $this->assertTrue($this->file->access('download', $another_user));

    $another_user = $this->createUser([], ['administer commerce_license', 'view commerce_product']);
    $this->assertTrue($this->file->access('view', $another_user));
    $this->assertTrue($this->file->access('download', $another_user));

    $file_access_handler = \Drupal::entityTypeManager()->getAccessControlHandler('file');
    $file_access_handler->resetCache();
    $license = License::create([
      'type' => 'commerce_file',
      'state' => 'active',
      'uid' => $account->id(),
      'product_variation' => $this->variation,
      'file_download_limit' => 2,
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $license->save();
    $this->assertTrue($this->file->access('view', $account));
    $this->assertTrue($this->file->access('download', $account));

    /** @var \Drupal\commerce_file\DownloadLoggerInterface $download_logger */
    $download_logger = $this->container->get('commerce_file.download_logger');
    $download_logger->log($license, $this->file);
    $file_access_handler->resetCache();
    $this->assertTrue($this->file->access('view', $account));
    $this->assertTrue($this->file->access('download', $account));

    // There's a limit of 2 downloads configured at the product variation level
    // access should be denied.
    $download_logger->log($license, $this->file);
    $file_access_handler->resetCache();
    $this->assertFalse($this->file->access('view', $account));
    $this->assertFalse($this->file->access('download', $account));
  }

}
