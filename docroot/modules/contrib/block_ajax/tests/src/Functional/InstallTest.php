<?php

namespace Drupal\Tests\block_ajax\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the Ajax Block module install without errors.
 *
 * @group block_ajax
 */
class InstallTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_ajax'];

  /**
   * Assert that the block_ajax module installed correctly.
   */
  public function testModuleInstalls() {
    // If we get here, then the module was successfully installed during the
    // setUp phase without throwing any Exceptions. Assert that TRUE is true,
    // so at least one assertion runs, and then exit.
    $this->assertTrue(TRUE, 'Module installed correctly.');
  }

}
