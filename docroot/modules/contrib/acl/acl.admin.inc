<?php

/**
 * @file
 * Implementations of administration functions for the acl module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Implementation of acl_edit_form().
 */
function _acl_edit_form($acl_id, $label = NULL, $new_acl = FALSE) {
  $database = \Drupal::database();

  $accounts = array();
  if (!$new_acl) {
    // Ensure the ACL in question even exists.
    if (!($record = $database->query("SELECT name, figure FROM {acl} WHERE acl_id = :acl_id", [
      'acl_id' => $acl_id,
    ])->fetchAssoc())) {
      return array();
    }

    $result = $database->query("SELECT u.uid, ud.name FROM {users} u INNER JOIN {users_field_data} ud ON u.uid = ud.uid LEFT JOIN {acl_user} aclu ON aclu.uid = u.uid WHERE acl_id = :acl_id", [
      'acl_id' => $acl_id,
    ]);

    foreach ($result as $account) {
      $accounts[$account->uid] = $account->name;
    }
  }

  if (!isset($label)) {
    if (isset($record['name'])) {
      $label = $record['name'];
    }
    elseif (isset($record['figure'])) {
      $label = $record['figure'];
    }
    else {
      $label = $acl_id;
    }
  }

  $form = array(
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#title' => Html::escape($label),
    '#tree' => TRUE,
  );

  $form['acl_id'] = array(
    '#type' => 'value',
    '#value' => $acl_id,
  );

  $form['deletions'] = array(
    '#type' => 'checkboxes',
    '#options' => array(),
  );
  $form['delete_button'] = array(
    '#type' => 'button',
    '#name' => 'acl_' . $acl_id,
    '#value' => t('Remove Checked'),
    '#submit' => array(),
  );

  $form['add'] = array(
    '#type' => 'entity_autocomplete',
    '#target_type' => 'user',
    '#title' => t('Add user'),
    '#maxlength' => 60,
    '#size' => 40,
    '#selection_settings' => ['include_anonymous' => FALSE],
  );
  $form['add_button'] = array(
    '#type' => 'button',
    '#name' => 'acl_' . $acl_id,
    '#value' => t('Add User'),
    '#submit' => array(),
  );

  $form['user_list'] = array(
    '#type' => 'hidden',
    '#default_value' => serialize($accounts),
  );

  $form['#after_build'] = ['_acl_edit_form_after_build'];

  return $form;
}

/**
 * Process a form that had our buttons on it.
 */
function _acl_edit_form_after_build($form, FormStateInterface $form_state) {
  // We can't use the form values because it's the entire structure
  // and we have no clue where our values actually are. That's
  // ok tho cause #value still works for us.
  $user_list = acl_edit_form_get_user_list($form);
  $button_name = 'acl_' . $form['acl_id']['#value'];

  $triggering_element = $form_state->getTriggeringElement();

  if (!empty($triggering_element) && $triggering_element['#value'] == $form['delete_button']['#value']) {
    $deletions = $form['deletions']['#value'];
    foreach ($deletions as $uid) {
      unset($user_list[$uid]);
      unset($form['deletions']['#value'][$uid]);
    }
  }
  elseif (!empty($triggering_element['#value']) && $triggering_element['#value'] == $form['add_button']['#value'] && !empty($form['add']['#value'])) {
    $value = $form['add']['#value'];
    $match = EntityAutocomplete::extractEntityIdFromAutocompleteInput($value);
    if ($match === NULL) {
      $user = \Drupal::database()->query("SELECT u.uid, ud.name FROM {users} u INNER JOIN {users_field_data} ud ON u.uid = ud.uid WHERE ud.name = :name", [
        'name' => $value,
      ])->fetchObject();
    }
    else {
      $user = \Drupal::database()->query("SELECT u.uid, ud.name FROM {users} u INNER JOIN {users_field_data} ud ON u.uid = ud.uid WHERE u.uid = :id", [
        'id' => $match,
      ])->fetchObject();
    }
    if (!$user) {
      $form_state->setError($form['add'], t("Invalid user specified."));
    }
    else {
      $user_list[$user->uid] = $user->name;
      $form['add']['#value'] = NULL;
    }
  }

  if (count($user_list) != 0) {
    $form['deletions']['#type'] = 'checkboxes';
    $form['deletions']['#title'] = t("Current users");
    $form['deletions']['#options'] = $user_list;
    $form['deletions']['#value'] = array(); // don't carry value through.
    $form['deletions'] = \Drupal::formBuilder()->doBuildForm(!empty($form['#post']) ? $form['#post']['form_id'] : 'acl_form', $form['deletions'], $form_state);
  }
  else {
    $form['delete_button']['#type'] = 'value';
  }
  $form['user_list']['#value'] = serialize($user_list);

  return $form;
}

/**
 * Write the results of a form.
 *
 * The module that embedded our form must call this function!
 */
function acl_save_form($form, $priority = NULL) {
  $database = \Drupal::database();

  $users = acl_edit_form_get_user_list($form);
  $database->delete('acl_user')
    ->condition('acl_id', $form['acl_id'])
    ->execute();

  $insert = $database->insert('acl_user')->fields(['acl_id', 'uid']);
  foreach ($users as $uid => $name) {
    $insert->values([
      'acl_id' => $form['acl_id'],
      'uid' => $uid,
    ]);
  }
  $insert->execute();

  if (isset($priority)) {
    $database->update('acl_node')
      ->fields([
        'priority' => $priority,
      ])
      ->condition('acl_id', $form['acl_id'])
      ->execute();
  }
}

/**
 * Decode and return the list of users.
 *
 * @param array $form
 *   The ACL form or form_state array.
 * @param bool $get_default
 *   (optional) In the case of a form array, whether to return the
 *   '#default_value' (or the '#value').
 *
 * @return array
 *   An array of $uid => $username.
 */
function acl_edit_form_get_user_list($form, $get_default = FALSE) {
  if (is_array($form['user_list'])) {
    return unserialize($form['user_list'][$get_default ? '#default_value' : '#value']);
  }
  return unserialize($form['user_list']);
}

