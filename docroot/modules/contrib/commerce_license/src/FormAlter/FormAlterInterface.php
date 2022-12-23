<?php

namespace Drupal\commerce_license\FormAlter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for the product variation form alteration service.
 */
interface FormAlterInterface {

  /**
   * Alters the form.
   *
   * @param array $form
   *   Nested array of form elements that comprises the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   String representing the name of the form itself. Typically this is the
   *   name of the function that generated the form.
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL);

}
