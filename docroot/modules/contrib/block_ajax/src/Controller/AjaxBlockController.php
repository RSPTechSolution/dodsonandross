<?php

namespace Drupal\block_ajax\Controller;

use Drupal\block\BlockInterface;
use Drupal\block_ajax\BlockViewBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\block_ajax\Response\AjaxBlockResponse;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ajax Blocks Controller.
 */
class AjaxBlockController extends ControllerBase {

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
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Block Ajax Controller Constructor.
   *
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\block_ajax\BlockViewBuilder $block_view_builder
   *   Block view builder service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token.
   */
  public function __construct(
    BlockManager $block_manager,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    BlockViewBuilder $block_view_builder,
    Token $token
  ) {
    $this->blockManager = $block_manager;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->blockViewBuilder = $block_view_builder;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AjaxBlockController {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('block_ajax.block_view_builder'),
      $container->get('token')
    );
  }

  /**
   * Implements ajax block update request handler.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $block_id
   *   The block id.
   *
   * @return \Drupal\block_ajax\Response\AjaxBlockResponse
   *   Returns the ajax block response.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\MissingValueContextException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadBlock(
    Request $request,
    string $block_id
  ): AjaxBlockResponse {
    // Response.
    $response = new AjaxBlockResponse();

    // Node check.
    $node = NULL;
    if (!empty($request->query->get('nid'))) {
      $node = $this->entityTypeManager->getStorage('node')->load($request->query->get('nid'));
    }

    // User check.
    $user = NULL;
    if (!empty($request->query->get('uid'))) {
      $user = $this->entityTypeManager->getStorage('user')->load($request->query->get('uid'));
    }

    // Taxonomy term check.
    $term = NULL;
    if (!empty($request->query->get('tid'))) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($request->query->get('tid'));
    }

    // Check for block id.
    if (empty($block_id)) {
      return $response;
    }

    // Load block.
    $block = $this->entityTypeManager->getStorage('block')->load($block_id);
    if (!$block instanceof BlockInterface) {
      return $response;
    }

    // Add in custom cache tag.
    $block->addCacheTags(['block_ajax']);

    // Make sure we have plugin id.
    $plugin_id = $request->get('plugin_id', '');
    if (empty($plugin_id)) {
      return $response;
    }

    // Block configuration.
    $configuration = [];

    // Check if block has special plugin and add it to dependency.
    $plugin = $block->getPlugin();
    if (is_object($plugin) && $plugin->getPluginId() == $plugin_id) {
      $configuration = $plugin->getConfiguration();
    }

    // Grab any configuration passed in.
    if (empty($configuration)) {
      $configuration = $request->get('config', []);
    }

    // Construct and render the block.
    $blockInstance = $this->blockViewBuilder->build($plugin_id, $configuration, TRUE);
    $renderedBlock = $this->renderer->renderRoot($blockInstance);

    // Apply token replacement.
    $renderedBlock = $this->token->replace($renderedBlock, [
      'node' => $node,
      'user' => $user,
      'term' => $term,
    ]);

    // Clean up ajax links.
    $renderedBlock = str_replace('/block/ajax/' . $block_id, '', trim($renderedBlock));

    // Set max age.
    $max_age = 0;
    if (isset($configuration['block_ajax']['max_age'])) {
      $max_age = $configuration['block_ajax']['max_age'];
    }
    $response->setMaxAge($max_age);
    $response->setSharedMaxAge($max_age);

    // Set data for response.
    $response->setData([
      'content' => $renderedBlock,
    ]);

    // Returns response.
    return $response;
  }

}
