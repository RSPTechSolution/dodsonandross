<?php

namespace Drupal\entity_clone\EntityClone\Content;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_clone\EntityClone\EntityCloneInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentEntityCloneBase.
 */
class ContentEntityCloneBase implements EntityHandlerInterface, EntityCloneInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * A service for obtaining the system's time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ContentEntityCloneBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Drupal\Component\Datetime\TimeInterface $time_service
   *   A service for obtaining the system's time.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $entity_type_id, TimeInterface $time_service, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeId = $entity_type_id;
    $this->timeService = $time_service;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $entity_type->id(),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, array $properties = [], array &$already_cloned = []) {
    if (isset($properties['take_ownership']) && $properties['take_ownership'] === 1) {
      $cloned_entity->setOwnerId($this->currentUser->id());
    }
    // Clone referenced entities.
    $already_cloned[$entity->getEntityTypeId()][$entity->id()] = $cloned_entity;
    if ($cloned_entity instanceof FieldableEntityInterface && $entity instanceof FieldableEntityInterface) {
      foreach ($cloned_entity->getFieldDefinitions() as $field_id => $field_definition) {
        if ($this->fieldIsClonable($field_definition)) {
          $field = $entity->get($field_id);
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $value */
          if ($field->count() > 0) {
            $cloned_entity->set($field_id, $this->cloneReferencedEntities($field, $field_definition, $properties, $already_cloned));
          }
        }
      }
    }

    $this->setClonedEntityLabel($entity, $cloned_entity);
    $this->setCreatedAndChangedDates($cloned_entity);

    if ($this->hasTranslatableModerationState($cloned_entity)) {
      // If we are using moderation state, ensure that each translation gets
      // the same moderation state BEFORE we save so that upon save, each
      // translation gets its publishing status updated according to the
      // moderation state. After the entity is saved, we kick in the creation
      // of translations of created moderation state entity.
      foreach ($cloned_entity->getTranslationLanguages(TRUE) as $language) {
        $translation = $cloned_entity->getTranslation($language->getId());
        $translation->set('moderation_state', $cloned_entity->get('moderation_state')->value);
      }
    }

    $cloned_entity->save();

    // If we are using content moderation, make sure the moderation state
    // entity gets translated to reflect the available translations on the
    // source entity. Thus, we call this after the save because we need the
    // original moderation state entity to have been created.
    if ($this->hasTranslatableModerationState($cloned_entity)) {
      $this->setTranslationModerationState($entity, $cloned_entity);
    }

    return $cloned_entity;
  }

  /**
   * Determines if a field is clonable.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   TRUE if the field is clonable; FALSE otherwise.
   */
  protected function fieldIsClonable(FieldDefinitionInterface $field_definition) {
    $clonable_field_types = [
      'entity_reference',
      'entity_reference_revisions',
    ];

    $type_is_clonable = in_array($field_definition->getType(), $clonable_field_types, TRUE);
    if (($field_definition instanceof FieldConfigInterface) && $type_is_clonable) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Sets the cloned entity's label.
   *
   * @param \Drupal\Core\Entity\EntityInterface $original_entity
   *   The original entity.
   * @param \Drupal\Core\Entity\EntityInterface $cloned_entity
   *   The entity cloned from the original.
   */
  protected function setClonedEntityLabel(EntityInterface $original_entity, EntityInterface $cloned_entity) {
    $label_key = $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('label');
    if ($label_key && $cloned_entity->hasField($label_key)) {
      $cloned_entity->set($label_key, $original_entity->label() . ' - Cloned');
    }
  }

  /**
   * Clones referenced entities.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item.
   * @param \Drupal\Core\Field\FieldConfigInterface $field_definition
   *   The field definition.
   * @param array $properties
   *   All new properties to replace old.
   * @param array $already_cloned
   *   List of all already cloned entities, used for circular references.
   *
   * @return array
   *   Referenced entities.
   */
  protected function cloneReferencedEntities(FieldItemListInterface $field, FieldConfigInterface $field_definition, array $properties, array &$already_cloned) {
    $referenced_entities = [];
    foreach ($field as $value) {
      // Check if we're not dealing with an entity
      // that has been deleted in the meantime.
      if (!$referenced_entity = $value->get('entity')->getTarget()) {
        continue;
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $referenced_entity */
      $referenced_entity = $value->get('entity')->getTarget()->getValue();
      $child_properties = $this->getChildProperties($properties, $field_definition, $referenced_entity);
      if (!empty($child_properties['clone'])) {

        $cloned_reference = $referenced_entity->createDuplicate();
        /** @var \Drupal\entity_clone\EntityClone\EntityCloneInterface $entity_clone_handler */
        $entity_clone_handler = $this->entityTypeManager->getHandler($referenced_entity->getEntityTypeId(), 'entity_clone');
        $entity_clone_handler->cloneEntity($referenced_entity, $cloned_reference, $child_properties['children'], $already_cloned);

        $referenced_entities[] = $cloned_reference;
      }
      elseif (!empty($child_properties['is_circular'])) {
        if (!empty($already_cloned[$referenced_entity->getEntityTypeId()][$referenced_entity->id()])) {
          $referenced_entities[] = $already_cloned[$referenced_entity->getEntityTypeId()][$referenced_entity->id()];
        }
        else {
          $referenced_entities[] = $referenced_entity;
        }
      }
      else {
        $referenced_entities[] = $referenced_entity;
      }
    }

    return $referenced_entities;
  }

  /**
   * Fetches the properties of a child entity.
   *
   * @param array $properties
   *   Properties of the clone operation.
   * @param \Drupal\Core\Field\FieldConfigInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Entity\EntityInterface $referenced_entity
   *   The field's target entity.
   *
   * @return array
   *   Child properties.
   */
  protected function getChildProperties(array $properties, FieldConfigInterface $field_definition, EntityInterface $referenced_entity) {
    $child_properties = [];
    if (isset($properties['recursive'][$field_definition->id()]['references'][$referenced_entity->id()])) {
      $child_properties = $properties['recursive'][$field_definition->id()]['references'][$referenced_entity->id()];
    }
    if (!isset($child_properties['children'])) {
      $child_properties['children'] = [];
    }

    return $child_properties;
  }

  /**
   * Create moderation_state translations for the cloned entities.
   *
   * When a new translation is saved, content moderation creates a corresponding
   * translation to the moderation_state entity as well. However, for this to
   * happen, the translation itself needs to be saved. When we clone, this
   * doesn't happen as the original entity gets cloned together with the
   * translations and a save is called on the original language being cloned. So
   * we have to do this manually.
   *
   * This is doing essentially what
   * Drupal\content_moderation\EntityOperations::updateOrCreateFromEntity but
   * we had to replicate it because if a user clones a node translation
   * directly, updateOrCreateFromEntity() would not create a translation for
   * the original language but would override the language when passing the
   * original entity translation.
   */
  protected function setTranslationModerationState(ContentEntityInterface $entity, ContentEntityInterface $cloned_entity) {
    $languages = $cloned_entity->getTranslationLanguages();

    // Load the existing moderation state entity for the cloned entity. This
    // should exist and have only 1 translation.
    $needs_save = FALSE;
    $moderation_state = ContentModerationState::loadFromModeratedEntity($cloned_entity);
    $original_translation = $cloned_entity->getUntranslated();
    if ($moderation_state->language()->getId() !== $original_translation->language()->getId()) {
      // If we are cloning a node while not being in the original translation
      // language, Drupal core will set the default language of the moderation
      // state to that language whereas the node is simply duplicated and will
      // keep the original default language. So we need to change it to that
      // also in the moderation state to keep things consistent.
      $moderation_state->set($moderation_state->getEntityType()->getKey('langcode'), $original_translation->language()->getId());
      $needs_save = TRUE;
    }

    foreach ($languages as $language) {
      $translation = $cloned_entity->getTranslation($language->getId());
      if (!$moderation_state->hasTranslation($translation->language()->getId())) {
        // We make a 1 to 1 copy of the moderation state entity from the
        // original created already by the content_moderation module. This is ok
        // because even if translations can be in different moderation states,
        // when cloning, the moderation state is reset to whatever the workflow
        // default is configured to be. So we anyway should end up with the
        // same state across all languages.
        $moderation_state->addTranslation($translation->language()->getId(), $moderation_state->toArray());
        $needs_save = TRUE;
      }
    }

    if ($needs_save) {
      ContentModerationState::updateOrCreateFromEntity($moderation_state);
    }
  }

  /**
   * Checks if the entity has the moderation state field and can be moderated.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   Whether it can be moderated.
   */
  protected function hasTranslatableModerationState(ContentEntityInterface $entity): bool {
    if (!$entity->hasField('moderation_state') || !$entity->get('moderation_state') instanceof ModerationStateFieldItemList) {
      return FALSE;
    }

    return !empty($entity->getTranslationLanguages(FALSE));
  }

  /**
   * Resets the created and changed dates on the cloned entity.
   *
   * Since we don't want the cloned entity to have the old dates (as a new
   * entity is being created), we need to reset the created and changed dates
   * for those entities that support it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The cloned entity.
   * @param bool $is_translation
   *   Whether we are recursing over a translation.
   */
  protected function setCreatedAndChangedDates(EntityInterface $entity, bool $is_translation = FALSE) {
    $created_time = $this->timeService->getRequestTime();

    // For now, check that the cloned entity has a 'setCreatedTime' method, and
    // if so, try to call it. This condition can be replaced with a more-robust
    // check whether $cloned_entity is an instance of
    // Drupal\Core\Entity\EntityCreatedInterface once
    // https://www.drupal.org/project/drupal/issues/2833378 lands.
    if (method_exists($entity, 'setCreatedTime')) {
      $entity->setCreatedTime($created_time);
    }

    // If the entity has a changed time field, we should update it to the
    // created time we set above as it cannot possibly be before.
    if ($entity instanceof EntityChangedInterface) {
      $entity->setChangedTime($created_time);
    }

    if ($is_translation) {
      return;
    }

    if ($entity instanceof TranslatableInterface) {
      foreach ($entity->getTranslationLanguages(FALSE) as $language) {
        $translation = $entity->getTranslation($language->getId());
        $this->setCreatedAndChangedDates($translation, TRUE);
      }
    }
  }

}
