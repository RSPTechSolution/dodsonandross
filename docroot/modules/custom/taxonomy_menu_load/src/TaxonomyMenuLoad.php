<?php

namespace Drupal\taxonomy_menu_load;

/**
 * Loads taxonomy terms in a menu
 */
class TaxonomyMenuLoad
{

  function taxonomy_menu_load_contextual_links_alter(array &$links, $group, array $route_parameters)
  {
    if ($group == 'menu') {

    // Dynamically use the menu name for the title of the menu_edit contextual
    // link.
      $menu = \Drupal::entityManager()
        ->getStorage('menu')
        ->load($route_parameters['menu']);
      $links['menu_edit']['title'] = t('Edit menu: @label', array(
        '@label' => $menu
          ->label(),
      ));
    }
  }
}