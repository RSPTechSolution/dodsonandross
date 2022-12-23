<?php

namespace Drupal\Tests\cookies\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This class provides methods for testing the cookies module.
 *
 * @group cookies
 */
class FunctionalCookiesTest extends BrowserTestBase {

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
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminuser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Use the test page as the front page.
    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->adminuser = $this->drupalCreateUser([]);
    $this->adminuser->addRole($this->createAdminRole('administrator', 'administrator'));
    $this->adminuser->save();
    $this->user = $this->drupalCreateUser([]);
    $this->drupalLogin($this->adminuser);
  }

  /**
   * Test Access on cookies setting page as authenticated user.
   */
  public function testSettingsPageAccessAsAuth() {
    $this->drupalLogout();
    $this->drupalLogin($this->user);
    $this->drupalGet('/admin/config/cookies/config');
    $this->assertSession()->statusCodeEquals('403');
  }

  /**
   * Test Access on cookies setting page as admin user.
   */
  public function testSettingsPageAccessAsAdmin() {
    $this->drupalGet('/admin/config/cookies/config');
    $this->assertSession()->statusCodeEquals('200');
  }

  /**
   * Test Access on cookies setting page as anonymous user.
   */
  public function testSettingsPageAccessAsAnonymous() {
    $this->drupalLogout();
    $this->drupalGet('/admin/config/cookies/config');
    $this->assertSession()->statusCodeEquals('403');
  }

  /**
   * Test if the cookies UI block banner exists.
   *
   * @todo Implement this test.
   */
  public function todoTestCookiesUiBlockBanner() {
    // @todo The problem is that drupalPlaceBlock will create a block with a
    // random id and random header. NOTHING that indicates if it is really
    // a cookies_ui_block... no class no proper id...
    // It is also possible to create blocks with arbitrary plugin_id strings!
    // for example $this->drupalPlaceBlock('asfasdfhj') will throw no errors!
    // The only thing that happens is, that if you generate the block like this,
    // the created div will have no id or <h2> element.
    $block = $this->drupalPlaceBlock('cookies_ui_block');
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementAttributeExists('css', '#' . $block->getOriginalId(), '...');
  }

  /**
   * Test if the cookies documentation block banner exists.
   *
   * @todo Implement this test. Problem, see todoTestCookiesUiBlockBanner.
   */
  public function todoTestCookiesDocBlockBanner() {
  }

  /**
   * Tests adding a group by form.
   */
  public function testAddingGroupByForm() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/cookies_service_group/add');
    $session->statusCodeEquals(200);
    $page->fillField('edit-label', 'test123Specific');
    $page->fillField('edit-weight', '50');
    $page->fillField('edit-title', 'myDisplay');
    $page->fillField('edit-details', 'myDetails');
    $page->fillField('edit-id', 'test123');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Created the test123Specific Cookie service group.');
    $this->drupalGet('/admin/structure/cookies_service_group');
    $session->statusCodeEquals(200);
    $session->pageTextContains('test123Specific');
  }

  /**
   * Tests editing a group by form.
   */
  public function testEditingGroupByForm() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/cookies_service_group/video/edit');
    $session->statusCodeEquals(200);
    $page->fillField('edit-label', 'test123Specific');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Saved the test123Specific Cookie service group.');
    $this->drupalGet('/admin/structure/cookies_service_group');
    $session->statusCodeEquals(200);
    $session->pageTextContains('test123Specific');
  }

  /**
   * Tests deleting a group by form.
   */
  public function testDeletingGroupByForm() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/cookies_service_group/performance/delete');
    $session->statusCodeEquals(200);
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('content cookies_service_group: deleted Performance.');
    $this->drupalGet('/admin/structure/cookies_service_group');
    $session->statusCodeEquals(200);
    $session->pageTextNotContains('Performance');
  }

  /**
   * Tests adding a service by form.
   */
  public function testAddingServiceByForm() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/cookies_service/add');
    $session->statusCodeEquals(200);
    $page->fillField('edit-label', 'test123Specific');
    $page->fillField('edit-id', 'test123');
    $page->fillField('edit-group', 'default');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Created the test123Specific Cookie service entity.');
    $this->drupalGet('/admin/structure/cookies_service');
    $session->statusCodeEquals(200);
    $session->pageTextContains('test123Specific');
  }

  /**
   * Tests editing a service by form.
   */
  public function testEditingServiceByForm() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/cookies_service/base/edit');
    $session->statusCodeEquals(200);
    $page->fillField('edit-label', 'test123Specific');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Saved the test123Specific Cookie service entity.');
    $this->drupalGet('/admin/structure/cookies_service');
    $session->statusCodeEquals(200);
    $session->pageTextContains('test123Specific');
  }

  /**
   * Tests deleting a service by form.
   */
  public function testDeletingServiceByForm() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/cookies_service/base/delete');
    $session->statusCodeEquals(200);
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('content cookies_service: deleted Required cookies.');
    $this->drupalGet('/admin/structure/cookies_service');
    $session->statusCodeEquals(200);
    $session->pageTextNotContains('Required');
  }

  /**
   * Tests to see if the cdn script exists.
   *
   * @todo Implement this. Problem: the cdn script does not have an id, or
   * anything else to check against, but the src and for some reason
   * "elementAttributeContains()" does not know "src" as an attribute.
   */
  public function todoTestScriptCdnExists() {
    // Enable cookies from cdn:
    $this->config('cookies.config')->set('lib_load_from_cdn', TRUE)->save();
    // Place the Cookie UI Block:
    $this->placeBlock('cookies_ui_block');
    // Clear caches, optherwise the cached script is returned:
    drupal_flush_all_caches();
    // Check script type before consent:
    $this->drupalGet('<front>');
    $this->assertSession()->elementExists('css', 'script');
    $this->assertSession()->elementAttributeContains('css', 'script', 'src', 'cdn');
  }

}
