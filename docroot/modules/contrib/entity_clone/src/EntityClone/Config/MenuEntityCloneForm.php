<?php

namespace Drupal\entity_clone\EntityClone\Config;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class MenuEntityCloneForm.
 */
class MenuEntityCloneForm extends ConfigEntityCloneFormBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(EntityInterface $entity, $parent = TRUE) {
    $form = parent::formElement($entity, $parent);

    // Menu entities require special replace pattern.
    $form['id']['#machine_name'] += [
      'replace_pattern' => '[^a-z0-9-]+',
      'replace' => '-',
    ];
    return $form;
 }

}
