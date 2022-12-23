<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;

/**
 * Create a filer and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneFileTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'file'];

  /**
   * Theme to enable by default
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone file entity',
  ];

  /**
   * An administrative user with permission to configure files settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test file entity clone.
   */
  public function testFileEntityClone() {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();

    $this->drupalPostForm('entity_clone/file/' . $file->id(), [], t('Clone'));

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties([
        'filename' => 'druplicon.txt - Cloned',
      ]);
    $file = reset($files);
    $this->assertInstanceOf(File::class, $file, 'Test file cloned found in database.');

    $this->assertEqual($file->getFileUri(), 'public://druplicon_0.txt', 'The stored file is also cloned.');
  }

}
