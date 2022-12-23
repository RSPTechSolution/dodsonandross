<?php

namespace Drupal\s3fs\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Ensure that ImageStyleDownloadController does not process s3fs images.
 *
 * Due to the core ImageStyleDownloadController() class having no access
 * controls built in for any system except private://, any controller path
 * that utilizes ImageStyleDownloadController() and a wildcard {scheme} in
 * route parameter path must have all s3fs managed paths overridden with a
 * path that denys access.
 */
class S3fsAlterCoreImageStyleRoutesSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // We ignore private:// as it is protected in the core class.
    $s3fs_schemes = ['s3'];
    if (Settings::get('s3fs.use_s3_for_public', FALSE)) {
      array_push($s3fs_schemes, 'public');
    }

    foreach ($collection->getIterator() as $route_name => $route) {
      $path = $route->getPath();

      if ($path === '/s3/files/styles/{image_style}/{scheme}') {
        // Skip our own image style route.
        continue;
      }

      if (preg_match('{scheme}', $path)) {
        $controller = $route->getDefault('_controller');
        if (!is_string($controller)) {
          continue;
        }

        // We only want the class name, not class and method.
        $controller = preg_replace('/::.*/', '', $controller);

        // We can not trust any route that has a scheme parameter that inherits
        // the core ImageStyleDownloadController class.
        if (is_a($controller, 'Drupal\image\Controller\ImageStyleDownloadController', TRUE)) {
          foreach ($s3fs_schemes as $scheme) {
            $new_route = new Route(
              preg_replace('/{scheme}/', $scheme, $path),
              [],
              [
                '_access' => 'FALSE',
              ]
            );
            $collection->add($route_name . '_s3fs_lockdown_' . $scheme, $new_route);
          }
        }
      }
    }
  }

}
