<?php

namespace Drupal\imageapi_optimize_webp\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Override 'image.style_public' controller to handle .webp deriver.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('image.style_public')) {
      $route->setDefault('_controller', 'Drupal\imageapi_optimize_webp\Controller\ImageStyleDownloadController::deliver');
    }
    if ($route = $collection->get('image.style_private')) {
      $route->setDefault('_controller', 'Drupal\imageapi_optimize_webp\Controller\ImageStyleDownloadController::deliver');
    }
  }

}
