<?php

namespace Drupal\commerce_license\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for admin routes for commerce license non-entity bundles.
 */
class LicenseTypesAdminController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a LicenseTypesAdminController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Callback for the admin overview route.
   */
  public function adminPage() {
    $entity_type = $this->entityTypeManager->getDefinition('commerce_license');
    $entity_bundle_info = $this->entityTypeBundleInfo->getBundleInfo('commerce_license');

    $build = [];

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Operations'),
      ],
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', [
        '@label' => $entity_type->getPluralLabel(),
      ]),
    ];

    foreach ($entity_bundle_info as $bundle_name => $bundle_info) {
      $build['table']['#rows'][$bundle_name] = [
        'name' => ['data' => $bundle_info['label']],
        'description' => ['data' => $bundle_info['description']],
        'operations' => ['data' => $this->buildOperations($bundle_name)],
      ];
    }

    return $build;
  }

  /**
   * Callback for the field UI base route.
   */
  public function bundlePage($bundle = NULL) {
    $entity_bundle_info = $this->entityTypeBundleInfo->getBundleInfo('commerce_license');

    return [
      '#markup' => $this->t('The @bundle-label bundle has no settings.', [
        '@bundle-label' => $entity_bundle_info[$bundle]['label'],
      ]),
    ];
  }

  /**
   * Builds a renderable list of operation links for the bundle.
   *
   * @return array
   *   A renderable array of operation links.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::buildRow()
   */
  public function buildOperations($bundle_name) {
    $operations = [];

    if ($this->currentUser->hasPermission('administer commerce_license fields')) {
      $operations['manage-fields'] = [
        'title' => t('Manage fields'),
        'weight' => 15,
        'url' => Url::fromRoute("entity.commerce_license.field_ui_fields", [
          'bundle' => $bundle_name,
        ]),
      ];
    }
    if ($this->currentUser->hasPermission('administer commerce_license form display')) {
      $operations['manage-form-display'] = [
        'title' => t('Manage form display'),
        'weight' => 20,
        'url' => Url::fromRoute("entity.entity_form_display.commerce_license.default", [
          'bundle' => $bundle_name,
        ]),
      ];
    }
    if ($this->currentUser->hasPermission('administer commerce_license display')) {
      $operations['manage-display'] = [
        'title' => t('Manage display'),
        'weight' => 25,
        'url' => Url::fromRoute("entity.entity_view_display.commerce_license.default", [
          'bundle' => $bundle_name,
        ]),
      ];
    }

    return [
      '#type' => 'operations',
      '#links' => $operations,
    ];
  }

}
