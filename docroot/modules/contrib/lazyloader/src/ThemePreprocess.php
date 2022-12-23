<?php

namespace Drupal\lazyloader;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ThemePreprocess.
 */
class ThemePreprocess {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new ThemePreprocess instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Attaches the Lazyloader library to a render array.
   *
   * @param array $vars
   *   The render array.
   *
   * @return array
   *   The render array with added library.
   */
  public function attachLibrary(array $vars) {
    $config = $this->configFactory->get('lazyloader.configuration');
    if (!$config->get('enabled')) {
      return $vars;
    }

    $vars['#attached']['library'][] = $this->determineLibraryToAttach();
    return $vars;
  }

  /**
   * Adds the cache tags.
   *
   * @param array $vars
   *   The variables.
   *
   * @return array
   *   The input array with cache tags.
   */
  public function addCacheTags(array $vars) {
    $vars['#cache']['tags'][] = 'config:lazyloader.configuration';
    return $vars;
  }

  /**
   * Determines the library to attach.
   *
   * @return string
   *   The library to attach.
   */
  private function determineLibraryToAttach() {
    $config = $this->configFactory->get('lazyloader.configuration');

    if ($config->get('debugging')) {
      $library = 'lazyloader/lazysizes';
      return $library;
    }

    if ($config->get('cdn') || !file_exists('libraries/lazysizes/lazysizes.min.js')) {
      $library = 'lazyloader/lazysizes-min.cdn';
      return $library;
    }

    $library = 'lazyloader/lazysizes-min';
    return $library;
  }

}
