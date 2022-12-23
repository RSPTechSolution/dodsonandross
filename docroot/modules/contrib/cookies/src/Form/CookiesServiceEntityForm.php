<?php

namespace Drupal\cookies\Form;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add and edit an CookiesServiceEntity entity.
 */
class CookiesServiceEntityForm extends EntityForm {


  /**
   * The famous Drupal Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The famous Drupal Cache Tags Invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheTagsInvalidator;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidator $cache_tags_invalidator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $cookie_service_entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $cookie_service_entity->label(),
      '#description' => $this->t("Label for the Cookie service entity."),
      '#required' => TRUE,
      '#weight' => 10,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $cookie_service_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\cookies\Entity\CookiesServiceEntity::load',
      ],
      '#disabled' => !$cookie_service_entity->isNew(),
      '#weight' => 10,
    ];

    $form['entity'] = [
      '#type' => 'details',
      '#title' => $this->t('Properties'),
      '#open' => TRUE,
      '#weight' => 20,
    ];
    $groups = $this->entityTypeManager->getStorage('cookies_service_group')->loadMultiple();
    $group_options = [];
    foreach ($groups as $id => $entity) {
      $group_options[$id] = $entity->label();
    }

    $form['entity']['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Service group'),
      '#options' => $group_options,
      '#default_value' => $cookie_service_entity->get('group'),
      '#description' => $this->t("Group the service belongs to e.g. 'tracking'."),
      '#required' => TRUE,
    ];

    $cookie_service_entity_info = $cookie_service_entity->get('info');
    $form['entity']['info'] = [
      '#type' => 'text_format',
      '#format' => $cookie_service_entity_info['format'] ?? NULL,
      '#title' => $this->t('Documentation'),
      '#default_value' => $cookie_service_entity_info['value'] ?? '',
      '#description' => $this->t('Local documentation for cookie details from this provider.'),
    ];

    $form['entity']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Docs url'),
      '#maxlength' => 255,
      '#default_value' => $cookie_service_entity->get('url'),
      '#description' => $this->t("External documentation for third-party resource."),
      '#required' => FALSE,
    ];

    $form['entity']['consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Consent required'),
      '#default_value' => $cookie_service_entity->get('consent'),
      '#description' => $this->t("If service needs an active user consent."),
    ];

    $form['entity']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $cookie_service_entity->get('status'),
      '#description' => $this->t("If checkbox is enabled, entity is shown in the user consent management widget."),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $cookie_service_entity = $this->entity;
    $status = $cookie_service_entity->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Cookie service entity.', [
          '%label' => $cookie_service_entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Cookie service entity.', [
          '%label' => $cookie_service_entity->label(),
        ]));
    }

    $this->cacheTagsInvalidator->invalidateTags(['config:cookies.cookies_service']);
    $form_state->setRedirectUrl($cookie_service_entity->toUrl('collection'));
  }

}
