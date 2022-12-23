<?php

namespace Drupal\entity_clone\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\entity_clone\Services\EntityCloneServiceProvider;
use Drupal\entity_clone\EntityCloneSettingsManager;
use Drupal\entity_clone\Event\EntityCloneEvent;
use Drupal\entity_clone\Event\EntityCloneEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Implements an entity Clone form.
 */
class EntityCloneForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity ready to clone.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity type définition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityTypeDefinition;

  /**
   * The string translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslationManager;

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity clone settings manager service.
   *
   * @var \Drupal\entity_clone\EntityCloneSettingsManager
   */
  protected $entityCloneSettingsManager;

  /**
   * The Service Provider that verifies if entity has ownership.
   *
   * @var \Drupal\entity_clone\Services\EntityCloneServiceProvider
   */
  protected $serviceProvider;

  /**
   * Constructs a new Entity Clone form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The string translation manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\entity_clone\EntityCloneSettingsManager $entity_clone_settings_manager
   *   The entity clone settings manager.
   * @param \Drupal\entity_clone\Services\EntityCloneServiceProvider $service_provider
   *   The Service Provider that verifies if entity has ownership.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, TranslationManager $string_translation, EventDispatcherInterface $eventDispatcher, Messenger $messenger, AccountProxyInterface $currentUser, EntityCloneSettingsManager $entity_clone_settings_manager, EntityCloneServiceProvider $service_provider) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslationManager = $string_translation;
    $this->eventDispatcher = $eventDispatcher;
    $this->messenger = $messenger;

    $parameter_name = $route_match->getRouteObject()->getOption('_entity_clone_entity_type_id');
    $this->entity = $route_match->getParameter($parameter_name);

    $this->entityTypeDefinition = $entity_type_manager->getDefinition($this->entity->getEntityTypeId());
    $this->currentUser = $currentUser;
    $this->entityCloneSettingsManager = $entity_clone_settings_manager;
    $this->serviceProvider = $service_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('string_translation'),
      $container->get('event_dispatcher'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_clone.settings.manager'),
      $container->get('entity_clone.service_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_clone_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->entity && $this->entityTypeDefinition->hasHandlerClass('entity_clone')) {

      /** @var \Drupal\entity_clone\EntityClone\EntityCloneFormInterface $entity_clone_handler */
      if ($this->entityTypeManager->hasHandler($this->entityTypeDefinition->id(), 'entity_clone_form')) {
        $entity_clone_form_handler = $this->entityTypeManager->getHandler($this->entityTypeDefinition->id(), 'entity_clone_form');
        $form = array_merge($form, $entity_clone_form_handler->formElement($this->entity));
      }
      $entityType = $this->getEntity()->getEntityTypeId();
      if ($this->serviceProvider->entityTypeHasOwnerTrait($this->getEntity()->getEntityType()) && $this->currentUser->hasPermission('take_ownership_on_clone ' . $entityType . ' entity')) {
        $form['take_ownership'] = [
          '#type' => 'checkbox',
          '#title' => $this->stringTranslationManager->translate('Take ownership'),
          '#default_value' => $this->entityCloneSettingsManager->getTakeOwnershipSetting(),
          '#description' => $this->stringTranslationManager->translate('Take ownership of the newly created cloned entity.'),
        ];
      }

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['clone'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->stringTranslationManager->translate('Clone'),
      ];

      $form['actions']['abort'] = [
        '#type' => 'submit',
        '#value' => $this->stringTranslationManager->translate('Cancel'),
        '#submit' => ['::cancelForm'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_clone\EntityClone\EntityCloneInterface $entity_clone_handler */
    $entity_clone_handler = $this->entityTypeManager->getHandler($this->entityTypeDefinition->id(), 'entity_clone');
    if ($this->entityTypeManager->hasHandler($this->entityTypeDefinition->id(), 'entity_clone_form')) {
      $entity_clone_form_handler = $this->entityTypeManager->getHandler($this->entityTypeDefinition->id(), 'entity_clone_form');
    }

    $properties = [];
    if (isset($entity_clone_form_handler) && $entity_clone_form_handler) {
      $properties = $entity_clone_form_handler->getValues($form_state);
    }

    $duplicate = $this->entity->createDuplicate();

    $this->eventDispatcher->dispatch(EntityCloneEvents::PRE_CLONE, new EntityCloneEvent($this->entity, $duplicate, $properties));
    $cloned_entity = $entity_clone_handler->cloneEntity($this->entity, $duplicate, $properties);
    $this->eventDispatcher->dispatch(EntityCloneEvents::POST_CLONE, new EntityCloneEvent($this->entity, $duplicate, $properties));

    $this->messenger->addMessage($this->stringTranslationManager->translate('The entity <em>@entity (@entity_id)</em> of type <em>@type</em> was cloned.', [
      '@entity' => $this->entity->label(),
      '@entity_id' => $this->entity->id(),
      '@type' => $this->entity->getEntityTypeId(),
    ]));

    $this->formSetRedirect($form_state, $cloned_entity);
  }

  /**
   * Cancel form handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $this->formSetRedirect($form_state, $this->entity);
  }

  /**
   * Sets a redirect on form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The cloned entity.
   */
  protected function formSetRedirect(FormStateInterface $form_state, EntityInterface $entity) {
    if ($entity && $entity->hasLinkTemplate('canonical')) {
      $form_state->setRedirect($entity->toUrl('canonical')->getRouteName(), $entity->toUrl('canonical')->getRouteParameters());
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

  /**
   * Gets the entity of this form.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

}
