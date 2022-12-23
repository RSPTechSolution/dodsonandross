<?php

namespace Drupal\lazyloader\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ThemeRegistryAlter.
 */
class ThemeRegistryAlter {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * Creates a new ThemeRegistryAlter instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $config) {
    $this->moduleHandler = $moduleHandler;
    $this->config = $config;
  }

  /**
   * Alters the theme registry.
   *
   * @param array $theme_registry
   *   The theme registry.
   */
  public function themeRegistryAlter(array &$theme_registry) {
    if ($this->config->get('lazyloader.configuration')->get('enabled')) {
      $theme_registry['image']['path'] = $this->moduleHandler->getModule('lazyloader')->getPath() . '/templates';
      $theme_registry['image']['template'] = 'image';

      $theme_registry['responsive_image']['preprocess functions'][] = 'lazyloader_preprocess_responsive_image';
    }
  }

}
