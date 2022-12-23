<?php

namespace Drupal\entity_clone\EntityClone\Config;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class MenuEntityClone.
 */
class MenuEntityClone extends ConfigEntityCloneBase {

  /**
   * {@inheritDoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, array $properties = []) {
    /** @var \Drupal\system\Entity\Menu */
    $cloned_entity->set('locked', FALSE);
    return parent::cloneEntity($entity, $cloned_entity, $properties);
  }

}
