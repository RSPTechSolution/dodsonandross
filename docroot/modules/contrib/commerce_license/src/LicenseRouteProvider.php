<?php

namespace Drupal\commerce_license;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the License entity.
 */
class LicenseRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCanonicalRoute($entity_type);

    // Zap the existing requirements which DefaultHtmlRouteProvider has set
    // using just the normal entity view access.
    $route->setRequirements([]);
    // Require the admin permission, rather than just a 'view' permission for
    // the admin canonical route, as this is in the admin area.
    $route->setRequirement('_permission', $entity_type->getAdminPermission());

    return $route;
  }

}
