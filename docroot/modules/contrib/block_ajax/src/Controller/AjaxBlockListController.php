<?php

namespace Drupal\block_ajax\Controller;

use Drupal\block\Controller\BlockListController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller to list blocks and ajax block info.
 */
class AjaxBlockListController extends BlockListController {

  /**
   * Shows the block administration page.
   *
   * @param string|null $theme
   *   Theme key of block list.
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function listing($theme = NULL, Request $request = NULL) {
    $listing = parent::listing($theme, $request);
    return $listing;
  }

}
