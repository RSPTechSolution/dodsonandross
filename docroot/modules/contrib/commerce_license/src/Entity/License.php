<?php

namespace Drupal\commerce_license\Entity;

use Drupal\commerce\EntityOwnerTrait;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the License entity.
 *
 * @ingroup commerce_license
 *
 * @ContentEntityType(
 *   id = "commerce_license",
 *   label = @Translation("License"),
 *   label_collection = @Translation("Licenses"),
 *   label_singular = @Translation("license"),
 *   label_plural = @Translation("licenses"),
 *   label_count = @PluralTranslation(
 *     singular = "@count license",
 *     plural = "@count licenses",
 *   ),
 *   bundle_label = @Translation("License type"),
 *   bundle_plugin_type = "commerce_license_type",
 *   handlers = {
 *     "access" = "\Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "event" = "Drupal\commerce_license\Event\LicenseEvent",
 *     "permission_provider" = "\Drupal\commerce_license\LicensePermissionProvider",
 *     "list_builder" = "Drupal\commerce_license\LicenseListBuilder",
 *     "storage" = "Drupal\commerce_license\LicenseStorage",
 *     "form" = {
 *       "default" = "Drupal\commerce_license\Form\LicenseForm",
 *       "add" = "Drupal\commerce_license\Form\LicenseForm",
 *       "checkout" = "Drupal\commerce_license\Form\LicenseCheckoutForm",
 *       "edit" = "Drupal\commerce_license\Form\LicenseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\commerce_license\LicenseViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_license\LicenseRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_license",
 *   admin_permission = "administer commerce_license",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "license_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/licenses/{commerce_license}",
 *     "add-page" = "/admin/commerce/licenses/add",
 *     "add-form" = "/admin/commerce/licenses/add/{type}",
 *     "edit-form" = "/admin/commerce/licenses/{commerce_license}/edit",
 *     "delete-form" = "/admin/commerce/licenses/{commerce_license}/delete",
 *     "collection" = "/admin/commerce/licenses",
 *   },
 *   field_ui_base_route = "entity.commerce_license.field_ui_fields",
 * )
 */
class License extends ContentEntityBase implements LicenseInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Get the label for the license from the plugin.
    return $this->getTypePlugin()->buildLabel($this);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Act when the license state changes, or the license is new.
    // (Note that $this->original is not set on new entities.)
    if ((isset($this->original) && $this->getState()->getId() != $this->original->getState()->getId()) || !isset($this->original)) {
      // If the state is being changed to 'active', set the granted and
      // expiration timestamps, and notify the license type plugin. We act on
      // preSave() rather than postSave() so that the license plugin can set
      // values on the license. HOWEVER, this means that if something acts in
      // hook_entity_presave() to prevent saving, by throwing an exception, the
      // license entity will be unsaved, but the license plugin will have
      // granted the license, leaving it in an incorrect state.
      // @todo override doPreSave() in LicenseStorage to catch exceptions and
      // revert the grant if the save is cancelled.
      if ($this->getState()->getId() == 'active') {
        // The state is moved to 'active', or the license was created active:
        // the license activates.
        $this->getTypePlugin()->grantLicense($this);

        // Set timestamps.
        $activation_time = \Drupal::service('datetime.time')->getRequestTime();

        if (empty($this->getGrantedTime())) {
          // The license has not previously been granted, and is therefore being
          // activated for the first time. Set the 'granted' timestamp.
          $this->setGrantedTime($activation_time);
        }
        else {
          // The license has previously been granted, and is therefore being
          // re-activated after a lapse. Set the 'renewed' timestamp.
          $this->setRenewedTime($activation_time);
        }

        // Set the expiry time on a new license, but allow licenses to be
        // created with a set expiry, such as in the case of a migration.
        if (!$this->getExpiresTime()) {
          $this->setExpiresTime($this->calculateExpirationTime($activation_time));
        }
      }

      // The state is being moved away from 'active'.
      if (isset($this->original) && $this->original->getState()->getId() == 'active') {
        // The license is revoked.
        $this->getTypePlugin()->revokeLicense($this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Revoke the license if it is active.
    if ($this->getState()->getId() == 'active') {
      $this->getTypePlugin()->revokeLicense($this);
    }

    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    /** @var \Drupal\commerce_license\LicenseTypeManager $license_type_manager */
    $license_type_manager = \Drupal::service('plugin.manager.commerce_license_type');
    return $license_type_manager->createInstance($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getExpirationPluginType() {
    return $this->get('expiration_type')->target_plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpirationPlugin() {
    return $this->get('expiration_type')->first()->getTargetInstance();
  }

  /**
   * {@inheritdoc}
   */
  public function setValuesFromPlugin(LicenseTypeInterface $license_plugin) {
    $license_plugin->setConfigurationValuesOnLicense($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiresTime() {
    return $this->get('expires')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpiresTime($timestamp) {
    $this->set('expires', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantedTime() {
    return $this->get('granted')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGrantedTime($timestamp) {
    $this->set('granted', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRenewedTime() {
    return $this->get('renewed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRenewedTime($timestamp) {
    $this->set('renewed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * Calculate the expiration time for this license from a start time.
   *
   * @param int $start
   *   The timestamp to calculate the duration from.
   *
   * @return int
   *   The expiry timestamp, or the value
   *   \Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface::UNLIMITED
   *   if the license does not expire.
   */
  protected function calculateExpirationTime($start) {
    /** @var \Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface $expiration_type_plugin */
    $expiration_type_plugin = $this->getExpirationPlugin();

    // The recurring period plugin needs DateTimeImmutable objects in order
    // to handle timezones properly. So we convert the timestamp to a datetime
    // using an appropriate timezone for the user, and then convert the
    // expiration back into a UTC timestamp.
    $start_date = (new \DateTimeImmutable('@' . $start))
      ->setTimezone(new \DateTimeZone(commerce_license_get_user_timezone($this->getOwner())));
    $expiration_date = $expiration_type_plugin->calculateDate($start_date);

    // The returned date is either \DateTimeImmutable or
    // \Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface::UNLIMITED.
    if (is_object($expiration_date)) {
      return $expiration_date->format('U');
    }
    else {
      return $expiration_date;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntity() {
    return $this->get('product_variation')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function getWorkflowId(LicenseInterface $license) {
    return $license->getTypePlugin()->getWorkflowId();
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginatingOrder() {
    return $this->get('originating_order')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginatingOrder(OrderInterface $originating_order) {
    $this->set('originating_order', $originating_order);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginatingOrderId() {
    return $this->get('originating_order')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['type']->setDisplayOptions('view', [
      'label' => 'inline',
      'type' => 'string',
      'weight' => 0,
    ]);

    $fields['uid']
      ->setLabel(t('Owner'))
      ->setDescription(t('The user ID of the license owner.'))
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 2,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The license state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 50,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', [static::class, 'getWorkflowId']);

    $fields['product_variation'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Licensed product variation'))
      ->setDescription(t('The licensed product variation.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_product_variation')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 1,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['expiration_type'] = BaseFieldDefinition::create('commerce_plugin_item:recurring_period')
      ->setLabel(t('Expiration type'))
      ->setDescription(t("The configuration for calculating the license's expiry."))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_plugin_select',
        'weight' => 21,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'commerce_plugin_item_default',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the license was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        // Start date-type weights at 20, to leave plenty of space for
        // license type plugin fields to go before them.
        'weight' => 20,
        'settings' => [
          'date_format' => 'medium',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['granted'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Granted'))
      ->setDescription(t('The time that the license was first granted or activated.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 21,
        'settings' => [
          'date_format' => 'medium',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['renewed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Renewed'))
      ->setDescription(t('The time that the license was most recently renewed.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 22,
        'settings' => [
          'date_format' => 'medium',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the license was last modified.'));

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires'))
      ->setDescription(t('The time that the license will expire, if any.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'commerce_license_expiration',
        'weight' => 26,
        'settings' => [
          'date_format' => 'medium',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      // Default to unlimited.
      ->setDefaultValue(0);

    $fields['originating_order'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Originating order'))
      ->setDescription(t('The order that originated the license creation.'))
      ->setSetting('target_type', 'commerce_order')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
