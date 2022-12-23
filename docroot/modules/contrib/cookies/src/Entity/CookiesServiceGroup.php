<?php

namespace Drupal\cookies\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Cookie service group entity.
 *
 * @ConfigEntityType(
 *   id = "cookies_service_group",
 *   label = @Translation("COOKiES service group"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cookies\CookiesServiceGroupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\cookies\Form\CookiesServiceGroupForm",
 *       "edit" = "Drupal\cookies\Form\CookiesServiceGroupForm",
 *       "delete" = "Drupal\cookies\Form\CookiesServiceGroupDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\cookies\CookiesRouteProvider",
 *     },
 *   },
 *   config_prefix = "cookies_service_group",
 *   admin_permission = "configure cookies widget",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "title" = "title",
 *     "details" = "details",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "langcode",
 *     "id",
 *     "label",
 *     "status",
 *     "dependencies",
 *     "weight",
 *     "title",
 *     "details"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/cookies_service_group/{cookies_service_group}",
 *     "add-form" = "/admin/structure/cookies_service_group/add",
 *     "edit-form" = "/admin/structure/cookies_service_group/{cookies_service_group}/edit",
 *     "delete-form" = "/admin/structure/cookies_service_group/{cookies_service_group}/delete",
 *     "collection" = "/admin/structure/cookies_service_group"
 *   }
 * )
 */
class CookiesServiceGroup extends ConfigEntityBase implements CookiesServiceGroupInterface {

  /**
   * The Cookie service group ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Cookie service group label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Cookie service group label.
   *
   * @var int
   */
  protected $weight;

  /**
   * The Cookie service group title.
   *
   * @var string
   */
  protected $title;

  /**
   * The Cookie service group details or description.
   *
   * @var string
   */
  protected $details;

}
