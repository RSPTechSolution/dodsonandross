<?php

namespace Drupal\commerce_file\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the file download checkout pane.
 *
 * @CommerceCheckoutPane(
 *   id = "commerce_file_download",
 *   label = @Translation("Files download"),
 *   default_step = "complete",
 * )
 */
class DownloadFile extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = [];
    /** @var \Drupal\commerce_license\LicenseStorageInterface $license_storage */
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    $license_ids = $license_storage->getQuery()
      ->condition('originating_order', $this->order->id())
      ->condition('state', 'active')
      ->accessCheck(FALSE)
      ->execute();

    if ($license_ids) {
      $pane_form['files'] = [
        '#type' => 'view',
        '#name' => 'commerce_file_my_files',
        '#display_id' => 'checkout_complete',
        '#arguments' => [implode('+', $license_ids)],
        '#embed' => TRUE,
      ];
    }

    return $pane_form;
  }

}
