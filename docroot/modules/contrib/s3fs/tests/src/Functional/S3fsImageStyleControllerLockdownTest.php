<?php

namespace Drupal\Tests\s3fs\Functional;

use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * S3 File System Image Style Lockdown verification.
 *
 * Ensure that the remote file system functionality provided by S3 File System
 * works correctly.
 *
 * The AWS credentials must be configured in prepareConfig() because
 * settings.php, which does not get executed during a BrowserTestBase.
 *
 * @group s3fs
 */
class S3fsImageStyleControllerLockdownTest extends S3fsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['s3fs', 'image', 'file'];

  /**
   * Test the image derivative functionality.
   */
  public function testLockedDown() {
    $this->drupalLogin($this->rootUser);
    // Prevent issues with derivative tokens during test.
    $this->config('image.settings')->set('allow_insecure_derivatives', TRUE)->save();

    $img_uri1 = "{$this->remoteTestsFolderUri}/test.png";
    $img_localpath = __DIR__ . '/../../fixtures/test.png';

    // Upload the test image.
    $this->assertTrue(\Drupal::service('file_system')->mkdir($this->remoteTestsFolderUri), 'Created the testing directory in the DB.');
    $img_data = file_get_contents($img_localpath);
    $img_file = $this->saveData($img_data, $img_uri1);
    $this->assertNotFalse($img_file, "Copied the the test image to $img_uri1.");

    $private_image_name = $this->randomGenerator->word(15) . '.jpg';
    $this->randomGenerator->image('private://' . $private_image_name, '400x300', '800x600');
    // Manually create the file record for the private:// file as we want it
    // to be temporary to pass hook_download() acl's.
    $values = [
      'uid' => $this->rootUser->id(),
      'status' => 0,
      'filename' => $private_image_name,
      'uri' => 'private://' . $private_image_name,
      'filesize' => filesize('private://' . $private_image_name),
      'filemime' => 'image/jpeg',
    ];
    $private_file = File::create($values);
    $private_file->save();
    $this->assertNotFalse(getimagesize($private_file->getFileUri()));
    $public_image_name = $this->randomGenerator->word(15) . '.jpg';
    $temp_public_image = $this->randomGenerator->image('temporary://' . $public_image_name, '400x300', '800x600');
    $this->saveData(file_get_contents($temp_public_image), 'public://' . $public_image_name);
    $this->assertNotFalse(getimagesize('public://' . $public_image_name));

    // Request a derivative. for s3 scheme via s3fs route.
    $private_route = Url::fromRoute(
      's3fs.image_styles',
      [
        'image_style' => 'large',
        'scheme' => 's3',
      ],
    );
    $derivative = $this->drupalGet($private_route->toString() . '/' . $this->remoteTestsFolder . '/test.png');
    $this->assertNotFalse(imagecreatefromstring($derivative), 's3fs.image_styles processes s3://');

    // Request a derivative. for s3 scheme via private route.
    $private_route = Url::fromRoute(
      'image.style_private',
      [
        'image_style' => 'large',
        'scheme' => 's3',
      ],
    );
    $derivative = $this->drupalGet($private_route->toString() . '/' . $this->remoteTestsFolder . '/test.png');
    $this->assertSession()->statusCodeEquals(403);

    // Request a derivative. for s3 scheme via public route.
    $private_route = Url::fromRoute(
      'image.style_public',
      [
        'image_style' => 'large',
        'scheme' => 's3',
      ],
    );
    $derivative = $this->drupalGet($private_route->toString() . '/' . $this->remoteTestsFolder . '/test.png');
    $this->assertSession()->statusCodeEquals(403);

    /*
     * Ensure core public:// and private:// image style still works.
     */

    // Request a derivative for private:// scheme via private route.
    $private_via_private_route = Url::fromRoute(
      'image.style_private',
      [
        'image_style' => 'large',
        'scheme' => 'private',
      ],
    );
    $derivative = $this->drupalGet($private_via_private_route->toString() . '/' . $private_image_name);
    $this->assertNotFalse(imagecreatefromstring($derivative), 'image.style_private does process private://');

    // Request a derivative for public:// scheme via public route.
    $public_via_public_route = Url::fromRoute(
      'image.style_public',
      [
        'image_style' => 'large',
        'scheme' => 'public',
      ],
    );
    $derivative = $this->drupalGet($public_via_public_route->toString() . '/' . $public_image_name);
    $this->assertNotFalse(imagecreatefromstring($derivative), 'image.style_public does process public://');

    // Enable public and private takeover.
    $settings = [];
    $settings['settings']['s3fs.use_s3_for_public'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $settings['settings']['s3fs.use_s3_for_private'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    // Make sure the test runner and Drupal gets the new StreamWrappers.
    $this->rebuildAll();

    // Create new files in s3.
    $private_image_name = $this->randomGenerator->word(15) . '.jpg';
    $this->randomGenerator->image('private://' . $private_image_name, '400x300', '800x600');
    // Manually create the file record for the private:// file as we want it
    // to be temporary to pass hook_download() acl's.
    $values = [
      'uid' => $this->rootUser->id(),
      'status' => 0,
      'filename' => $private_image_name,
      'uri' => 'private://' . $private_image_name,
      'filesize' => filesize('private://' . $private_image_name),
      'filemime' => 'image/jpeg',
    ];
    $private_file = File::create($values);
    $private_file->save();
    $this->assertNotFalse(getimagesize($private_file->getFileUri()));
    $public_image_name = $this->randomGenerator->word(15) . '.jpg';
    $this->randomGenerator->image('public://' . $public_image_name, '400x300', '800x600');
    $this->assertNotFalse(getimagesize('public://' . $public_image_name));

    /*
     * Test derivatives takeover.
     */

    // Request a derivative for public:// scheme via public route.
    $public_via_public_route = Url::fromRoute(
      'image.style_public',
      [
        'image_style' => 'large',
        'scheme' => 'public',
      ],
    );
    $derivative = $this->drupalGet($public_via_public_route->toString() . '/' . $public_image_name);
    // We want 403 or 404 here.
    // The image.style_pubilc route never works correctly with s3fs takeover
    // because PathProcessorImageInbound can't handle public:// being on
    // a remote streamWrapper. As such we get 404.
    $this->assertSession()->statusCodeEquals(404);

    // Request a derivative for public:// scheme via s3fs route.
    $public_via_s3fs_route = Url::fromRoute(
      's3fs.image_styles',
      [
        'image_style' => 'large',
        'scheme' => 'public',
      ],
    );
    $derivative = $this->drupalGet($public_via_s3fs_route->toString() . '/' . $public_image_name);
    $this->assertNotFalse(imagecreatefromstring($derivative), 'With takeover s3fs.image_styles does process public://');

    // Verify that s3fs.image_styles does not process private://.
    $private_via_s3fs_route = Url::fromRoute(
      's3fs.image_styles',
      [
        'image_style' => 'large',
        'scheme' => 'private',
      ],
    );
    $derivative = $this->drupalGet($private_via_s3fs_route->toString() . '/' . $private_image_name);
    // We want 403 or 404 here.  We get 404 due to
    // S3fsPathProcessorImageStyles being programmed not to accept
    // the private scheme.
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Ensure that S3fsImageStyleDownloadController only serves s3fs schemes.
   */
  public function testValidateS3fsStyleImageControllerOnlyServeS3fs() {
    // Prevent issues with derivative tokens during test.
    $this->config('image.settings')->set('allow_insecure_derivatives', TRUE)->save();

    // Obtain url objects for routes we need to test with.
    $s3fs_route_s3 = Url::fromRoute(
      's3fs.image_styles',
      [
        'image_style' => 'large',
        'scheme' => 's3',
      ],
    );

    $s3fs_route_private = Url::fromRoute(
      's3fs.image_styles',
      [
        'image_style' => 'large',
        'scheme' => 'private',
      ],
    );

    $s3fs_route_public = Url::fromRoute(
      's3fs.image_styles',
      [
        'image_style' => 'large',
        'scheme' => 'public',
      ],
    );

    $s3fs_route_temporary = Url::fromRoute(
      's3fs.image_styles',
      [
        'image_style' => 'large',
        'scheme' => 'temporary',
      ],
    );

    // Create images to be used in tests.
    $s3_image_path = $this->randomGenerator->word(15) . '.jpg';
    $s3_image_uri = 's3://' . $s3_image_path;
    $this->randomGenerator->image($s3_image_uri, '400x300', '800x600');
    $this->assertNotFalse(getimagesize($s3_image_uri), 's3:// image uploaded');
    $public_image_path = $this->randomGenerator->word(15) . '.jpg';
    $public_image_uri = 'public://' . $public_image_path;
    $this->randomGenerator->image($public_image_uri, '400x300', '800x600');
    $this->assertNotFalse(getimagesize($public_image_uri), 'public:// image uploaded');
    $private_image_path = $this->randomGenerator->word(15) . '.jpg';
    $private_image_uri = 'private://' . $private_image_path;
    $this->randomGenerator->image($private_image_uri, '400x300', '800x600');
    $this->assertNotFalse(getimagesize($private_image_uri), 'private:// image uploaded');
    $temporary_image_path = $this->randomGenerator->word(15) . '.jpg';
    $temporary_image_uri = 'temporary://' . $temporary_image_path;
    $this->randomGenerator->image($temporary_image_uri, '400x300', '800x600');
    $this->assertNotFalse(getimagesize($temporary_image_uri), 'private:// image uploaded');

    $this->drupalLogin($this->rootUser);

    // Ensure we generate a derivative for s3://.
    $derivative = $this->drupalGet($s3fs_route_s3->toString() . '/' . $s3_image_path);
    $this->assertNotFalse(imagecreatefromstring($derivative));

    // Takeover disabled, public:// scheme should not be allowed.
    $this->drupalGet($s3fs_route_public->toString() . '/' . $public_image_path);
    $this->assertSession()->statusCodeEquals(403);

    // The private:// scheme is never allowed.
    $this->drupalGet($s3fs_route_private->toString() . '/' . $private_image_path);
    // We want 403 or 404 here. We get 404 due to S3fsPathProcessorsImageStyles.
    $this->assertSession()->statusCodeEquals(404);

    // The temporary:// scheme is never allowed.
    $this->drupalGet($s3fs_route_temporary->toString() . '/' . $temporary_image_path);
    // We want 403 or 404 here. We get 404 due to S3fsPathProcessorsImageStyles.
    $this->assertSession()->statusCodeEquals(404);

    // Enable public takeover.
    $settings = [];
    $settings['settings']['s3fs.use_s3_for_public'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    // Make sure the test runner and Drupal gets the new StreamWrappers.
    $this->rebuildAll();

    // Generate a new image that will be stored in the bucket.
    $public_image_path = $this->randomGenerator->word(15) . '.jpg';
    $public_image_uri = 'public://' . $public_image_path;
    $this->randomGenerator->image($public_image_uri, '400x300', '800x600');

    // With public:// takeover enabled we should now generate a derivative.
    $derivative = $this->drupalGet($s3fs_route_public->toString() . '/' . $public_image_path);
    $this->assertNotFalse(imagecreatefromstring($derivative));
  }

}
