<?php

namespace Drupal\entity_clone;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_clone\Services\EntityCloneServiceProvider;

/**
 * Provides dynamic permissions of the entity_clone module.
 */
class EntityClonePermissions implements ContainerInjectionInterface {

  /**
   * The entoty type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The string translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * The Service Provider that verifies if entity has ownership.
   *
   * @var \Drupal\entity_clone\Services\EntityCloneServiceProvider
   */
  protected $serviceProvider;

  /**
   * Constructs a new EntityClonePermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The string translation manager.
   * @param \\Drupal\entity_clone\Services\EntityCloneServiceProvider $service_provider
   *   The Service Provider that verifies if entity has ownership.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, TranslationManager $string_translation, EntityCloneServiceProvider $service_provider) {
    $this->entityTypeManager = $entity_manager;
    $this->translationManager = $string_translation;
    $this->serviceProvider = $service_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('entity_clone.service_provider')
    );
  }

  /**
   * Returns an array of entity_clone permissions.
   *
   * @return array
   *   The permission list.
   */
  public function permissions() {
    $permissions = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $permissions['clone ' . $entity_type_id . ' entity'] = $this->translationManager->translate('Clone all <em>@label</em> entities.', [
        '@label' => $entity_type->getLabel(),
      ]);

      if ($this->serviceProvider->entityTypeHasOwnerTrait($entity_type)) {
        $permissions['take_ownership_on_clone ' . $entity_type_id . ' entity'] = $this->translationManager->translate('Allow user to take ownership of  <em>@label</em> cloned entities', [
          '@label' => $entity_type->getLabel(),
        ]);
      }
    }

    return $permissions;
  }

}
