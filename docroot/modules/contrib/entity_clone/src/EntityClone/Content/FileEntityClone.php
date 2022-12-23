<?php

namespace Drupal\entity_clone\EntityClone\Content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class ContentEntityCloneBase.
 */
class FileEntityClone extends ContentEntityCloneBase {

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, array $properties = [], array &$already_cloned = []) {
    /** @var \Drupal\file\FileInterface $cloned_entity */
    $cloned_file = file_copy($cloned_entity, $cloned_entity->getFileUri(), FileSystemInterface::EXISTS_RENAME);
    if (isset($properties['take_ownership']) && $properties['take_ownership'] === 1) {
      $cloned_file->setOwnerId(\Drupal::currentUser()->id());
    }
    return parent::cloneEntity($entity, $cloned_file, $properties);
  }

}
