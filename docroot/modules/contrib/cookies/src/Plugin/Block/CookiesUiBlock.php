<?php

namespace Drupal\cookies\Plugin\Block;

use Drupal\cookies\Services\CookiesConfigService;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CookiesUiBlock' block.
 *
 * @Block(
 *  id = "cookies_ui_block",
 *  admin_label = @Translation("Cookies UI"),
 * )
 */
class CookiesUiBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Config object for cookies module.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $cookiesConfig;

  /**
   * The COOKiES config service.
   *
   * @var \Drupal\cookies\Services\CookiesConfigService
   */
  protected $cookiesConfigService;

  /**
   * Constructor for COOKiES UI block.
   *
   * @param array $configuration
   *   Block config.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   Block plugun definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory to return module config.
   * @param \Drupal\cookies\Services\CookiesConfigService $cookies_config_service
   *   The config serve providing the drupalSettings (JS).
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config_factory, CookiesConfigService $cookies_config_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->cookiesConfig = $config_factory->get('cookies.config');
    $this->cookiesConfigService = $cookies_config_service;
  }

  /**
   * Static creator for dependencies injection in blocks.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container delivers the services.
   * @param array $configuration
   *   Block config.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   Block plugun definition.
   *
   * @return static
   *   Static object instance.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('cookies.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $lib = $this->cookiesConfigService->getLibrary();
    $attatched = [
      'library' => [$lib],
      'drupalSettings' => [
        'cookiesjsr' => $this->cookiesConfigService->getCookiesConfig(),
      ],
    ];
    $build = [
      '#theme' => 'cookies_container',
      '#styles' => (bool) $this->cookiesConfig->get('use_default_styles'),
      '#attached' => $attatched,
      '#cache' => [
        'contexts' => ['languages'],
        'tags' => [
          'config:cookies.config',
          'config:cookies.texts',
          'config:cookies.cookies_service',
          'config:cookies.cookies_service_group',
        ],
      ],
    ];

    return $build;
  }

}
