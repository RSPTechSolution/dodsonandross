<?php

namespace Drupal\Tests\cookies_ga\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests cookies_ga Javascript related functionalities.
 *
 * @group cookies_ga
 */
class TestCookiesGaFunctionalJavascript extends WebDriverTestBase {

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
    'google_analytics',
    'cookies',
    'cookies_ga',
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
    // Set google_analytics settings:
    $this->config('google_analytics.settings')->set('account', 'UA-xxxxx-yy')->save();
    $this->config('google_analytics.settings')->set('visibility.request_path_pages', '')->save();
    // Fluch caches, otherwise the script will not show up:
    drupal_flush_all_caches();
  }

  /**
   * Tests if the cookies ga javascript file is correctly knocked in / out.
   */
  public function testGoogleAnalyticsJsCorrectlyKnocked() {
    $session = $this->assertSession();

    $this->drupalGet('<front>');
    $session->elementExists('css', 'script#cookies_google_analytics_tracking_script');
    $session->elementAttributeContains('css', 'script#cookies_google_analytics_tracking_script', 'type', 'text/plain');

    // Fire consent script, accept all cookies:
    $script = "var options = { all: true };
        document.dispatchEvent(new CustomEvent('cookiesjsrSetService', { detail: options }));";
    $this->getSession()->getDriver()->executeScript($script);
    drupal_flush_all_caches();

    $this->drupalGet('<front>');
    // Since the id is removed on opt in, we have to look for our script like
    // this:
    $session->elementExists('css', 'script[src*="analytics.js"]');
    $session->elementAttributeContains('css', 'script[src*="analytics.js"]', 'type', 'text/javascript');
  }

}
