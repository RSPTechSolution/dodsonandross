<?php

namespace Drupal\Tests\cookies_recaptcha\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests cookies_recaptcha Javascript related functionalities.
 *
 * @group cookies_recaptcha
 */
class CookiesRecaptchaFunctionalJavascriptTest extends WebDriverTestBase {

  /**
   * An admin user with all permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'test_page_test',
    'filter_test',
    'block',
    'captcha',
    'recaptcha',
    'cookies',
    'cookies_recaptcha',
  ];

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('cookies_ui_block');
    // Set captcha and recaptcha settings:
    $this->config('captcha.settings')->set('default_challenge', 'cookies_recaptcha/reCAPTCHA')->save();
    $this->config('recaptcha.settings')->set('site_key', '0000000000000000000000000000000000000000')->save();
    $this->config('recaptcha.settings')->set('secret_key', '0000000000000000000000000000000000000000')->save();
    // Fluch caches, otherwise the script will not show up:
    drupal_flush_all_caches();
  }

  /**
   * Tests if the cookies ga javascript file is correctly knocked in / out.
   */
  public function testMatomoJsCorrectlyKnocked() {
    $session = $this->assertSession();
    $driver = $this->getSession()->getDriver();
    // Enable login captcha point:
    $captcha_point = \Drupal::entityTypeManager()
      ->getStorage('captcha_point')
      ->load('user_login_form');
    $captcha_point->enable()->save();
    $this->drupalLogout();

    // Got to login page and check blocked recaptcha:
    $this->drupalGet('/user/login');
    $session->elementExists('css', 'script#cookies_recaptcha');
    $session->elementAttributeContains('css', 'script#cookies_recaptcha', 'type', 'text/plain');
    $session->elementAttributeContains('css', 'script[src*="https://www.google.com/recaptcha/api.js"]', 'type', 'text/plain');
    // Fire consent script, accept all cookies:
    $script = "var options = { all: true };
        document.dispatchEvent(new CustomEvent('cookiesjsrSetService', { detail: options }));";
    $driver->executeScript($script);
    drupal_flush_all_caches();

    $this->drupalGet('/user/login');
    $session->elementExists('css', 'script[src*="https://www.google.com/recaptcha/api.js"]');
    $session->elementNotExists('css', 'script#cookies_recaptcha');
    $session->elementAttributeNotExists('css', 'script[src*="https://www.google.com/recaptcha/api.js"]', 'type');
  }

}
