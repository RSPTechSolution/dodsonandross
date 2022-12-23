<?php

namespace Drupal\webp\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (['image.style_public', 'image.style_private'] as $route_id) {
      if ($route = $collection->get($route_id)) {
        $route->setDefault('_controller', 'Drupal\webp\Controller\ImageStyleDownloadController::deliver');
      }
    }
    foreach (['system.private_file_download', 'system.files'] as $route_id) {
      if ($route = $collection->get($route_id)) {
        $route->setDefault('_controller', 'Drupal\webp\Controller\FileDownloadController::download');
      }
    }
  }

}
