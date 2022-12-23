<?php

namespace Drupal\Tests\s3fs\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * S3fs form validation tests.
 *
 * Ensure that the form validatior functions as designed.
 *
 * The AWS credentials must be configured in prepareConfig() because
 * settings.php, is not executed when using BrowserTestBase.
 *
 * @group s3fs
 */
class S3fsConfigValidationTest extends S3fsTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['s3fs'];

  /**
   * Coverage test for read only bucket configuration.
   */
  public function testReadOnlySetting() {

    $s3Config = $this->s3Config;
    $s3CustomConfig = $this->s3Config;

    $this->assertEmpty($this->s3fs->validate($s3Config));
    // Read only on a read/write bucket.
    $s3CustomConfig['read_only'] = TRUE;
    $errors = $this->s3fs->validate($s3CustomConfig);
    $this->assertEquals('The provided credentials are not read-only.', $errors[0]);
  }

}
