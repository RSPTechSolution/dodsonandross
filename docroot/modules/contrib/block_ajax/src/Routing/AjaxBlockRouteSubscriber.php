<?php

namespace Drupal\block_ajax\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Ajax block route subscriber.
 *
 * @package Drupal\block_ajax\Routing
 */
class AjaxBlockRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('block.admin_display')) {
      $route->setDefault('_controller', '\Drupal\block_ajax\Controller\AjaxBlockListController::listing');
    }
  }

}
