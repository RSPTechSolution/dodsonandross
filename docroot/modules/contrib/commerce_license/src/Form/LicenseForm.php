<?php

namespace Drupal\commerce_license\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Form controller for License edit forms.
 *
 * @ingroup commerce_license
 */
class LicenseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;
    // By default an expiration type is preselected on the add form
    // because the field is required.
    // Select an empty value instead, to force the user to choose.
    $user_input = $form_state->getUserInput();
    if ($this->operation === 'add' &&
      $this->entity->get('expiration_type')->isEmpty()) {
      if (!empty($form['expiration_type']['widget'][0]['target_plugin_id'])) {
        $form['expiration_type']['widget'][0]['target_plugin_id']['#empty_value'] = '';
        if (empty($user_input['expiration_type'][0]['target_plugin_id'])) {
          $form['expiration_type']['widget'][0]['target_plugin_id']['#default_value'] = '';
          unset($form['expiration_type']['widget'][0]['target_plugin_configuration']);
        }
      }
    }

    // Remove the anonymous and authenticated roles from the role options.
    if (isset($form['license_role'])) {
      $roles_to_remove = [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID];
      $form['license_role']['widget']['#options'] = array_diff_key($form['license_role']['widget']['#options'], array_combine($roles_to_remove, $roles_to_remove));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label License.', [
          '%label' => $entity->label(),
        ]));

        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label License.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.commerce_license.canonical', ['commerce_license' => $entity->id()]);
  }

}
