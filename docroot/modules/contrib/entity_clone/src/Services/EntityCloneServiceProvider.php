<?php

namespace Drupal\entity_clone\Services;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Service Provider Class.
 */
class EntityCloneServiceProvider {

  /**
   * Constructs a new ServiceProvider object.
   */
  public function __construct() {}

  /**
   * Checks if the given entity implements has owner trait.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity
   *   Entity to be tested.
   *
   * @return bool
   *   Returns boolean for the owner trait test.
   */
  public function entityTypeHasOwnerTrait(EntityTypeInterface $entityType) {
    try {
      $reflectionClass = new \ReflectionClass($entityType->getOriginalClass());
    } catch (\ReflectionException $e) {
      return FALSE;
    }
    return in_array(
      EntityOwnerTrait::class,
      array_keys($reflectionClass->getTraits())
    );
  }

}
