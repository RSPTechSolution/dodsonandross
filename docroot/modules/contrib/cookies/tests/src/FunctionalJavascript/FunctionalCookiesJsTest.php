<?php

namespace Drupal\Tests\cookies_userlike\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests cookies javascript functionalities.
 *
 * @group cookies
 */
class FunctionalCookiesJsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Requirements for cookies:
    'language',
    'file',
    'field',
    'locale',
    'config_translation',
    // Other module:
    'block',
    'cookies',
    'test_page_test',
  ];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A test administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A regular authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Use the test page as the front page.
    $this->config('system.site')->set('page.front', '/test-page')->save();
    // Create users:
    $this->user = $this->drupalCreateUser();
    $this->adminUser = $this->drupalCreateUser();
    $this->adminUser->addRole($this->createAdminRole('administrator', 'administrator'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests to see if the cdn script exists.
   */
  public function testCookieIsSet() {
    // Enable cookies from cdn:
    $this->config('cookies.config')->set('lib_load_from_cdn', TRUE)->save();
    // Place the Cookie UI Block:
    $this->placeBlock('cookies_ui_block');
    // Check script type before consent:
    $this->drupalGet('<front>');
    // Fire consent script:
    $script = "var options = { all: true };
      document.dispatchEvent(new CustomEvent('cookiesjsrSetService', { detail: options }));";
    $this->getSession()->getDriver()->executeScript($script);
    $cookie = $this->getSession()->getDriver()->getCookie('cookiesjsr');
    $this->assertEquals($cookie, '{"base":true}');
  }

}
