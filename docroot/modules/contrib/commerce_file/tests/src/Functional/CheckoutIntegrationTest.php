<?php

namespace Drupal\Tests\commerce_file\Functional;

/**
 * Tests the commerce_file checkout integration.
 *
 * @group commerce
 */
class CheckoutIntegrationTest extends FileBrowserTestBase {

  /**
   * Tests the download file checkout pane.
   */
  public function testDownloadPane() {
    $this->drupalGet($this->variation->getProduct()->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');

    $this->submitForm([
      'billing_information[profile][address][0][address][given_name]' => 'John',
      'billing_information[profile][address][0][address][family_name]' => 'Smith',
      'billing_information[profile][address][0][address][organization]' => 'Centarro',
      'billing_information[profile][address][0][address][address_line1]' => '9 Drupal Ave',
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->submitForm([], 'Complete checkout');

    $this->assertSession()->pageTextContains('Files');
    $this->assertSession()->pageTextContains('Downloads');
    $this->assertSession()->pageTextContains('Expires');
    foreach ($this->files as $file) {
      $this->assertSession()->pageTextContains($file->getFilename());
    }
    $this->clickLink($this->files[0]->getFilename());
    $this->assertSession()->statusCodeEquals(200);
  }

}
