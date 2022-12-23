<?php

namespace Drupal\Tests\entity_clone\Functional;

use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;

/**
 * Create an image style and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneImageStyleTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'image'];

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
    'clone image_style entity',
    'administer image styles',
  ];

  /**
   * An administrative user with permission to configure image styles settings.
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
   * Test image style entity clone.
   */
  public function testImageStyleEntityClone() {
    $edit = [
      'label' => 'Test image style for clone',
      'name' => 'test_image_style_for_clone',
    ];
    $this->drupalPostForm("admin/config/media/image-styles/add", $edit, t('Create new style'));

    $image_styles = \Drupal::entityTypeManager()
      ->getStorage('image_style')
      ->loadByProperties([
        'name' => $edit['name'],
      ]);
    $image_style = reset($image_styles);

    $edit = [
      'id' => 'test_iamge_style_cloned',
      'label' => 'Test image_style cloned',
    ];
    $this->drupalPostForm('entity_clone/image_style/' . $image_style->id(), $edit, t('Clone'));

    $image_styles = \Drupal::entityTypeManager()
      ->getStorage('image_style')
      ->loadByProperties([
        'name' => $edit['id'],
      ]);
    $image_style = reset($image_styles);
    $this->assertInstanceOf(ImageStyle::class, $image_style, 'Test image style cloned found in database.');

    $edit = [
      'id' => 'test_image_style_clone_with_a_really_long_name_that_is_longer_than_the_max_length',
      'label' => 'Test image style clone with a really long name that is longer than the max length',
    ];
    $this->drupalPostForm('entity_clone/image_style/' . $image_style->id(), $edit, t('Clone'));
    $this->assertText('New Id cannot be longer than 64 characters');
  }

}
