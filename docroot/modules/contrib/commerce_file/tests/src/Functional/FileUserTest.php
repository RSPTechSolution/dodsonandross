<?php

namespace Drupal\Tests\commerce_file\Functional;

/**
 * Tests normal user operations with licensed files.
 *
 * @group commerce_file
 */
class FileUserTest extends FileBrowserTestBase {

  /**
   * Tests the files user tab.
   */
  public function testFilesView() {
    $this->drupalGet('/user/' . $this->user->id() . '/files');
    $this->assertSession()->statusCodeEquals(200);
    // No licenses yet, so the empty text should be shown.
    $this->assertSession()->pageTextContains('No files yet.');
    $this->createEntity('commerce_license', [
      'type' => 'commerce_file',
      'product_variation' => $this->variation,
      'state' => 'active',
      'uid' => $this->user->id(),
      'file_download_limit' => 2,
      'expiration_type' => [
        'target_plugin_id' => 'unlimited',
        'target_plugin_configuration' => [],
      ],
    ]);
    $this->getSession()->reload();
    $this->assertSession()->pageTextContains($this->files[0]->getFilename());
    $this->clickLink($this->files[0]->getFilename());
  }

}
