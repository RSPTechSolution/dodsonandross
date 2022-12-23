<?php

namespace Drupal\Tests\vapn\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure the behavior of Vapn module
 *
 * @group vapn
 */
class VapnFunctionalTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['vapn','node'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $permissions = [
      'administer site configuration',
      'access administration pages',
      'use vapn',
      'bypass node access'
    ];
    $this->user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the configuration of the module.
   */
  public function testConfigForm() {
    $this->drupalCreateContentType(['type' => 'page']);
    $this->editForm = 'admin/config/vapn/vapnconfig';
    $form = [
      'vapn_node_list[page]' => 1
    ];
    $this->drupalPostForm($this->editForm,$form,t('Save configuration'));
    $this->drupalGet('node/add/page');
    $this->assertSession()->elementExists('css', '#edit-vapn');
  }

}
