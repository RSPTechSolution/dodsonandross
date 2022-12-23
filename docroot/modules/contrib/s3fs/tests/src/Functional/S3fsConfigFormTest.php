<?php

namespace Drupal\Tests\s3fs\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests s3fs configuration form.
 *
 * @group s3fs
 */
class S3fsConfigFormTest extends S3fsTestBase {

  use StringTranslationTrait;

  /**
   * A user with administration access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['s3fs'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer s3fs',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the S3fs config form.
   */
  public function testS3fsConfigurationForm() {

    $bucketRegion = $this->config('s3fs.settings')->get('region');

    // Set the region incorrectly so we can verify it changes before
    // it reaches the getAmazonS3Client() call.
    if ($bucketRegion == 'us-east-1') {
      $this->config('s3fs.settings')->set('region', 'us-east-2')->save();
    }
    else {
      $this->config('s3fs.settings')->set('region', 'us-east-1')->save();
    }

    $edit['credentials_file'] = '/tmp/test.ini';
    $edit['use_credentials_cache'] = 1;
    $edit['credentials_cache_dir'] = '/tmp/testcache';
    $edit['bucket'] = 's3fs-testing-bucket';
    $edit['use_cname'] = 1;
    $edit['domain_root'] = 'none';
    $edit['domain'] = 'domaincheck.com';
    $edit['use_path_style_endpoint'] = 1;
    $edit['encryption'] = 'AES256';
    $edit['use_https'] = 1;
    $edit['read_only'] = 1;
    $edit['disable_cert_verify'] = 0;
    $edit['disable_shared_config_files'] = 1;
    $edit['root_folder'] = 'rootfoldercheck';
    $edit['presigned_urls'] = '60|private_files/*';
    $edit['saveas'] = 'video/*';
    $edit['torrents'] = 'big_files/*';
    $this->drupalGet('admin/config/media/s3fs');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $currentRegion = $this->config('s3fs.settings')->get('region');
    $this->assertEquals($bucketRegion, $currentRegion, 'Region Detection Successful');
  }

}
