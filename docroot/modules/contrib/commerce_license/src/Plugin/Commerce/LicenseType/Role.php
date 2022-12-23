<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\commerce\EntityHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role as UserRole;
use Drupal\user\UserInterface;
use Drupal\user\RoleInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\ExistingRights\ExistingRightsResult;

/**
 * Provides a license type which grants one or more roles.
 *
 * @CommerceLicenseType(
 *   id = "role",
 *   label = @Translation("Role"),
 * )
 */
class Role extends LicenseTypeBase implements ExistingRightsFromConfigurationCheckingInterface, GrantedEntityLockingInterface {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    $args = [
      '@role' => $license->license_role->entity->label(),
    ];
    return $this->t('@role role license', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'license_role' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    // Get the role ID that this license grants.
    $role_id = $license->license_role->target_id;

    // Get the owner of the license and grant them the role.
    $owner = $license->getOwner();
    if (!$owner->isAnonymous()) {
      $owner->addRole($role_id);
      $owner->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
    // Get the role ID that this license grants.
    $role_id = $license->license_role->first()->target_id;

    // Get the owner of the license and remove that role.
    $owner = $license->getOwner();
    if (!$owner->isAnonymous()) {
      $owner->removeRole($role_id);
      $owner->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkUserHasExistingRights(UserInterface $user) {
    $role_id = $this->configuration['license_role'];
    $role = \Drupal::service('entity_type.manager')->getStorage('user_role')->load($role_id);

    return ExistingRightsResult::rightsExistIf(
      $user->hasRole($role_id),
      $this->t("You already have the @role-label role.", [
        '@role-label' => $role->label(),
      ]),
      $this->t("User @user already has the @role-label role.", [
        '@user' => $user->getDisplayName(),
        '@role-label' => $role->label(),
      ])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterEntityOwnerForm(array &$form, FormStateInterface $form_state, $form_id, LicenseInterface $license, EntityInterface $form_entity) {
    if ($form_entity->getEntityTypeId() != 'user') {
      // Only act on a user form.
      return;
    }

    $licensed_role_id = $license->license_role->target_id;

    $form['account']['roles'][$licensed_role_id]['#disabled'] = TRUE;
    $form['account']['roles'][$licensed_role_id]['#default_value'] = TRUE;
    $form['account']['roles'][$licensed_role_id]['#description'] = t("This role is granted by a license. It cannot be removed manually.");
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $roles = UserRole::loadMultiple();

    // Skip the built-in roles.
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    // Remove the admin role if it exists.
    // @todo consider removing any role that has "is_admin" set.
    unset($roles['administrator']);

    // If no licensable roles exist, display an error message.
    // A radios element without options will result in an
    // "Illegal choice detected" error.
    if (!$roles) {
      $form['error'] = [
        '#markup' => $this->t('No licensable roles can be configured, please review your configuration.'),
      ];

      return $form;
    }

    $options = EntityHelper::extractLabels($roles);

    $form['license_role'] = [
      '#type' => 'radios',
      '#title' => $this->t('Licensed role'),
      '#options' => EntityHelper::extractLabels($roles),
      '#default_value' => isset($options[$this->configuration['license_role']]) ? $this->configuration['license_role'] : key($options),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    if (!empty($values['license_role'])) {
      $this->configuration['license_role'] = $values['license_role'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['license_role'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Role'))
      ->setDescription(t('The role this product grants access to.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user_role')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 1,
        'settings' => [
          'link' => TRUE,
        ],
      ]);

    return $fields;
  }

}
