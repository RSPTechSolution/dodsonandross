<?php

namespace Drupal\block_ajax;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\TermInterface;

/**
 * Ajax Blocks Service.
 */
class AjaxBlocks {

  use StringTranslationTrait;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheTagsInvalidator;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Block view builder service.
   *
   * @var \Drupal\block_ajax\BlockViewBuilder
   */
  protected $blockViewBuilder;

  /**
   * The Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Ajax Blocks Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Cache\CacheTagsInvalidator $cache_tags_invalidator
   *   The cache tags invalidator service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\block_ajax\BlockViewBuilder $block_view_builder
   *   Block view builder service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(
    ConfigFactory $config_factory,
    AccountProxyInterface $current_user,
    CacheTagsInvalidator $cache_tags_invalidator,
    DateFormatterInterface $date_formatter,
    BlockManager $block_manager,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    BlockViewBuilder $block_view_builder,
    RouteMatchInterface $route_match
  ) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->dateFormatter = $date_formatter;
    $this->blockManager = $block_manager;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->blockViewBuilder = $block_view_builder;
    $this->routeMatch = $route_match;
  }

  /**
   * Get the current node id.
   *
   * @return int
   *   Returns the current node id.
   */
  public function getCurrentNodeId(): int {
    $nid = 0;
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      // You can get nid and anything else you need from the node object.
      $nid = $node->id();
    }
    return $nid;
  }

  /**
   * Get current user id.
   *
   * @return int
   *   Returns the current user id.
   */
  public function getCurrentUserId(): int {
    return $this->currentUser->id();
  }

  /**
   * Get current taxonomy id.
   *
   * @return int
   *   Returns the current term id.
   */
  public function getCurrentTaxonomyTermId(): int {
    $term_id = 0;
    $term = $this->routeMatch->getParameter('taxonomy_term');
    if ($term instanceof TermInterface) {
      $term_id = $term->id();
    }
    return $term_id;
  }

  /**
   * Check to see if user has proper permissions/access rights.
   *
   * @param string $permission
   *   The permission.
   *
   * @return bool
   *   Returns access right.
   */
  public function hasAccess(string $permission = 'administer ajax blocks'): bool {
    return $this->currentUser->hasPermission($permission);
  }

  /**
   * Check if given block is configured as an Ajax block.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   Block plugin.
   *
   * @return bool
   *   Returns TRUE if current block is AJAX block.
   */
  public function isAjaxBlock(BlockPluginInterface $block): bool {
    $conf = $block->getConfiguration();
    return !empty($conf['block_ajax']['is_ajax']);
  }

  /**
   * Invalidate Ajax block cache tags.
   */
  public function invalidateAjaxBlocks() {
    $this->cacheTagsInvalidator->invalidateTags(['block_ajax']);
  }

  /**
   * Get max age options.
   *
   * @return array
   *   Returns an array of max age options.
   */
  public function getMaxAgeOptions(): array {
    $periods = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];
    $periods = array_map([
      $this->dateFormatter,
      'formatInterval',
    ], array_combine($periods, $periods));
    $periods[0] = $this->t('No caching');

    return $periods;
  }

  /**
   * Get Ajax defaults for $.ajax().
   *
   * @param array $blockConfig
   *   The block configuration array.
   *
   * @return array
   *   Returns an array of configuration for ajax method.
   */
  public function getAjaxDefaults(array $blockConfig): array {
    return [
      'type' => $blockConfig['method'] ?? 'POST',
      'timeout' => $blockConfig['timeout'] ?? 10000,
      'async' => !isset($blockConfig['others']['async']) ||
      !empty(($blockConfig['others']['async'] ?? TRUE)),
      'cache' => !empty($blockConfig['others']['cache']),
      'dataType' => 'json',
    ];
  }

}
