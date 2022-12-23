<?php

namespace Drupal\Tests\s3fs\Functional;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * S3 File System Tests.
 *
 * Ensure that the remote file system functionality provided by S3 File System
 * works correctly.
 *
 * The AWS credentials must be configured in prepareConfig() because
 * settings.php, which does not get executed during a BrowserTestBase.
 *
 * @group s3fs
 */
class S3fsUrlGenerationTest extends BrowserTestBase {

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
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('s3fs.settings')
      ->set('presigned_urls', "600|signreq/*\n300|shortsignttl/*")
      ->set('saveas', '.*saveas/*')
      ->set('torrents', '.*tordir/*')
      ->set('bucket', '513ec7bfc9ac489781a764057973d870')
      ->set('region', 'us-east-1')
      ->save();

    // Write the access key settings into the config.
    $settings = [];
    $settings['settings']['s3fs.access_key'] = (object) [
      'value' => 'BogusAccessKey',
      'required' => TRUE,
    ];
    $settings['settings']['s3fs.secret_key'] = (object) [
      'value' => 'BogusSecretKey',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

  }

  /**
   * Test default link generation.
   */
  public function testDefaultUriGeneration() {
    $urlBase = 'http://513ec7bfc9ac489781a764057973d870.s3.amazonaws.com';
    $this->runTests($urlBase);
  }

  /**
   * Test defaults secure link generation.
   */
  public function testHttpsDefaultUriGeneration() {
    $urlBase = 'https://513ec7bfc9ac489781a764057973d870.s3.amazonaws.com';
    $this->config('s3fs.settings')->set('use_https', TRUE)->save();
    $this->runTests($urlBase);
  }

  /**
   * Test default link generation using a different region.
   */
  public function testDefaultDifferentRegion() {
    $urlBase = 'http://513ec7bfc9ac489781a764057973d870.s3.us-east-2.amazonaws.com';
    $this->config('s3fs.settings')->set('region', 'us-east-2')->save();
    $this->runTests($urlBase);
  }

  /**
   * Test with a custom S3 Endpoint that contains a non standard port.
   */
  public function testCustomEndpointWithPortUriGeneration() {
    $urlBase = 'http://513ec7bfc9ac489781a764057973d870.s3fslocalstack:4566';
    $this->config('s3fs.settings')
      ->set('use_customhost', TRUE)
      ->set('hostname', 'https://s3fslocalstack:4566')
      ->save();
    $this->runTests($urlBase);
  }

  /**
   * Test Custom Hostname link generation.
   */
  public function testCustomHostname() {
    $urlBase = 'http://test.example.org';

    $this->config('s3fs.settings')
      ->set('use_cname', TRUE)
      ->set('domain', 'test.example.org')
      ->save();

    $this->runTests($urlBase);

  }

  /**
   * Test Custom Hostname with port link generation.
   */
  public function testCustomHostnameWithPort() {
    $urlBase = 'http://test.example.org:8080';

    $this->config('s3fs.settings')
      ->set('use_cname', TRUE)
      ->set('domain', 'test.example.org:8080')
      ->save();

    $this->runTests($urlBase);

  }

  /**
   * Test custom hostname secure link generation.
   */
  public function testHttpsCustomHostname() {
    $urlBase = 'https://test.example.org';

    $this->config('s3fs.settings')->set('use_https', TRUE)->save();

    $this->config('s3fs.settings')
      ->set('use_cname', TRUE)
      ->set('domain', 'test.example.org')
      ->save();

    $this->runTests($urlBase);
  }

  /**
   * Test Custom hostname using a different region.
   */
  public function testCustomHostnameDifferentRegion() {
    $urlBase = 'http://test.example.org';

    $this->config('s3fs.settings')
      ->set('use_cname', TRUE)
      ->set('domain', 'test.example.org')
      ->set('region', 'us-east-2')
      ->save();

    $this->runTests($urlBase);
  }

  /**
   * Test using a path based endpoint bucket.
   */
  public function testPathBasedEndpoint() {
    $urlBase = 'http://s3.amazonaws.com/513ec7bfc9ac489781a764057973d870';
    $this->config('s3fs.settings')->set('use_path_style_endpoint', TRUE)->save();

    $this->runTests($urlBase);
  }

  /**
   * Test using a path based endpoint bucket with CNAME.
   */
  public function testPathBasedEndpointWithCustomHostname() {
    $urlBase = 'http://test.example.org/513ec7bfc9ac489781a764057973d870';

    $this->config('s3fs.settings')
      ->set('use_path_style_endpoint', TRUE)
      ->set('use_cname', TRUE)
      ->set('domain', 'test.example.org')
      ->save();

    $this->runTests($urlBase);
  }

  /**
   * Test using a root folder.
   */
  public function testWithRootFolder() {
    $urlBase = 'http://513ec7bfc9ac489781a764057973d870.s3.amazonaws.com/MyRootFolder';
    $this->config('s3fs.settings')->set('root_folder', 'MyRootFolder')->save();

    $this->runTests($urlBase);
  }

  /**
   * Execute common tests.
   *
   * @param string $urlBase
   *   Base path including scheme that links are expected to include.
   */
  protected function runTests(string $urlBase) {
    $publicFile = 's3://public.txt';
    $signedLongFile = 's3://signreq/signed.txt';
    $signedShortFile = 's3://shortsignttl/shortsigned.txt';
    $torrentWorksFile = 's3://tordir/thisworks.txt';
    $torrentFail = 's3://signreq/tordir/thiswontwork.txt';
    $forcedSaveFile = 's3://saveas/forcedsave.txt';
    $forcedSavePresignFile = 's3://signreq/saveas/alsoforcesaved.txt';
    $needsEncodingFile = 's3://spaces & other characters must be encoded.txt';

    $publicFileUri = $this->createUrl($publicFile);
    $this->assertEquals($urlBase . '/public.txt', $publicFileUri, 'Public request as expected');

    // Presigned URL.
    $signedLongUri = $this->createUrl($signedLongFile);
    $this->assertStringContainsString($urlBase, $signedLongUri, "Signed request contains base url");
    $this->assertStringContainsString('X-Amz-Signature', $signedLongUri, 'Signed request contains a signature');
    $this->assertStringContainsString('X-Amz-SignedHeaders=host', $signedLongUri, 'Host is part of signed request');
    $this->assertStringContainsString('X-Amz-Expires=600', $signedLongUri, 'Signed for 600 Seconds');
    // @todo Calculate a signature ourselves based on the URL to see if it is correct.
    $this->assertStringContainsString('X-Amz-Expires=300', $this->createUrl($signedShortFile), 'Signed for 300 seconds');

    // Torrent based download.
    $this->assertEquals($urlBase . '/tordir/thisworks.txt?torrent', $this->createUrl($torrentWorksFile), 'Download via torrent');
    $this->assertStringNotContainsString('torrent', $this->createUrl($torrentFail), 'Signed URLS can not use torrent download');

    // Save URLS as file.
    $forcedSaveUri = $this->createUrl($forcedSaveFile);
    $this->assertStringContainsString('X-Amz-Signature', $forcedSaveUri, 'Forced save request contains a signature');
    $this->assertStringContainsString('response-content-disposition=attachment', $forcedSaveUri, 'Forced save includes content-disposition header');

    $forcedSavePresignUri = $this->createUrl($forcedSavePresignFile);
    $this->assertStringContainsString('X-Amz-Signature', $forcedSavePresignUri, 'Forced Save on a presign contains a signature');
    $this->assertStringContainsString('response-content-disposition=attachment', $forcedSavePresignUri, 'Forced Save with forced presign still includes content-disposition');

    // Verify that special characters are encoded.
    $needsEncodingFileUri = $this->createUrl($needsEncodingFile);
    $this->assertEquals($urlBase . '/spaces%20%26%20other%20characters%20must%20be%20encoded.txt', $needsEncodingFileUri, 'Special characters are encoded');

  }

  /**
   * Test getExternalUrl (none).
   */
  public function testGetExternalUrlNone() {
    // Test the external url with the public domain root.
    $this->runDomainRootTests('none', 's3://', 'test_root/');
    $this->runDomainRootTests('none', 'public://', 'test_root/test_public/');
  }

  /**
   * Test getExternalUrl (root).
   */
  public function testGetExternalUrlRoot() {
    // Test the external url with the public domain root.
    $this->runDomainRootTests('root', 's3://', '');
    $this->runDomainRootTests('root', 'public://', 'test_public/');
  }

  /**
   * Test getExternalUrl (public).
   */
  public function testGetExternalUrlPublic() {
    // Test the external url with the public domain root.
    $this->runDomainRootTests('public', 's3://', '');
    $this->runDomainRootTests('public', 'public://', '');
  }

  /**
   * Execute domain_root tests.
   *
   * @param string $domainRoot
   *   The domain root type to set in config.
   * @param string $scheme
   *   The uri scheme.
   * @param string $expected
   *   The expected output from getExternalUrl.
   *
   * @covers \Drupal\s3fs\StreamWrapper\S3fsStream::getExternalUrl
   */
  protected function runDomainRootTests($domainRoot, $scheme, $expected) {
    $dummyFile = 'dummy.pdf';
    $domain = 'test.example.org';
    // Test the external url with the root_public domain root.
    $this->config('s3fs.settings')
      ->set('use_cname', TRUE)
      ->set('root_folder', 'test_root')
      ->set('public_folder', 'test_public')
      ->set('domain_root', $domainRoot)
      ->set('domain', $domain)
      ->save();
    /** @var \Drupal\s3fs\StreamWrapper\S3fsStream $streamWrapper */
    $streamWrapper = \Drupal::service('stream_wrapper.s3fs');
    $streamWrapper->setUri($scheme . $dummyFile);
    $this->assertEquals(
      'http://' . $domain . '/' . $expected . $dummyFile,
      $streamWrapper->getExternalUrl(),
      $domainRoot . ' domain_root as expected'
    );
  }

  /**
   * Helper for deprecated file_create_url()
   *
   *  Use file_save_data on <D9.3.
   *  Use FileRepositoryInterface::writeData() >= D9.3.
   *
   * @param string $uri
   *   Uri to create files to.
   *
   * @return string
   *   URL for uri.
   */
  protected function createUrl(string $uri) {
    if (interface_exists(FileUrlGeneratorInterface::class)) {
      /** @var \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator */
      $fileUrlGenerator = \Drupal::service('file_url_generator');
      return $fileUrlGenerator->generateAbsoluteString($uri);
    }
    else {
      // @todo remove when D9.3 is minimal supported version.
      // @phpstan-ignore-next-line
      return file_create_url($uri);
    }
  }

}
