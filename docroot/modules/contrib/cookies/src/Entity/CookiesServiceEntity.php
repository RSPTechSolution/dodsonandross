<?php

namespace Drupal\cookies\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Cookie service entity entity.
 *
 * @ConfigEntityType(
 *   id = "cookies_service",
 *   label = @Translation("COOKiES service"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cookies\CookiesServiceEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\cookies\Form\CookiesServiceEntityForm",
 *       "edit" = "Drupal\cookies\Form\CookiesServiceEntityForm",
 *       "delete" = "Drupal\cookies\Form\CookiesServiceEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\cookies\CookiesRouteProvider",
 *     },
 *   },
 *   config_prefix = "cookies_service",
 *   admin_permission = "configure cookies widget",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "langcode",
 *     "id",
 *     "label",
 *     "status",
 *     "dependencies",
 *     "group",
 *     "info",
 *     "url",
 *     "consent"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/cookies_service/{cookies_service}",
 *     "add-form" = "/admin/structure/cookies_service/add",
 *     "edit-form" = "/admin/structure/cookies_service/{cookies_service}/edit",
 *     "delete-form" = "/admin/structure/cookies_service/{cookies_service}/delete",
 *     "collection" = "/admin/structure/cookies_service"
 *   }
 * )
 */
class CookiesServiceEntity extends ConfigEntityBase implements CookiesServiceEntityInterface {

  /**
   * The Cookie service entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Cookie service entity label.
   *
   * @var string
   */
  protected $label;


  /**
   * The Cookie service group the service belongs to.
   *
   * @var string
   */
  protected $group;

  /**
   * The Cookie service url.
   *
   * @var string
   */
  protected $url;

  /**
   * The Cookie service consent variable.
   *
   * @var bool
   */
  protected $consent;

  /**
   * The Cookie service info.
   *
   * @var array
   */
  protected $info = [];

}
