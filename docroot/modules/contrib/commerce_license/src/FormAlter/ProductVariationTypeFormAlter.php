<?php

namespace Drupal\commerce_license\FormAlter;

use Drupal\commerce_license\LicenseTypeManager;
use Drupal\Core\Form\FormStateInterface;

/**
 * Alters the product variation type form.
 *
 * - Adds a form element for our third-party setting for available license
 *   types.
 * - Provides validation in the product variation type admin UI to ensure that
 *   everything joins up when a license trait is used.
 *
 * @see commerce_license_field_widget_form_alter()
 */
class ProductVariationTypeFormAlter implements FormAlterInterface {

  /**
   * The license type manager.
   *
   * @var \Drupal\commerce_license\LicenseTypeManager
   */
  protected $licenseTypeManager;

  /**
   * Constructs a new ProductVariationTypeFormAlter object.
   *
   * @param \Drupal\commerce_license\LicenseTypeManager $license_type_manager
   *   The license type manager.
   */
  public function __construct(LicenseTypeManager $license_type_manager) {
    $this->licenseTypeManager = $license_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $product_variation_type */
    $product_variation_type = $form_state->getFormObject()->getEntity();
    // Create our form elements which we insert into the form at the end.
    $our_form = [];

    $our_form['license'] = [
      '#type' => 'details',
      '#title' => t('License settings'),
      '#open' => TRUE,
      // Only show this if the license trait is set on the product variation
      // type.
      '#states' => [
        'visible' => [
          ':input[name="traits[commerce_license]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add checkboxes to the product variation type form to select the license
    // types that product variations of this type may use.
    $options = array_column($this->licenseTypeManager->getDefinitions(), 'label', 'id');
    $our_form['license']['license_types'] = [
      '#type' => 'checkboxes',
      '#title' => t("Available license types"),
      '#description' => t("Limit the license types that can be used on product variations of this type. All types will be allowed if none are selected."),
      '#options' => $options,
      '#default_value' => $product_variation_type->getThirdPartySetting('commerce_license', 'license_types', []),
    ];
    // TODO: consider whether to lock this once the product variation type is
    // created or has product variation entities, or at least lock the enabled
    // license types.
    $our_form['license']['activate_on_place'] = [
      '#type' => 'checkbox',
      '#title' => t("Activate license when order is placed"),
      '#description' => t(
        "Activates the license as soon as the customer completes checkout, rather than waiting for payment to be taken. " .
        "If payment subsequently fails, canceling the order will cancel the license. " .
        "This only has an effect with order types that use validation or fulfilment states and payment gateways that are asynchronous."
      ),
      '#default_value' => $product_variation_type->getThirdPartySetting('commerce_license', 'activate_on_place', FALSE),
    ];

    // Insert our form elements into the form after the 'traits' element.
    // The form elements don't have their weight set, so we can't use that.
    $traits_element_form_array_index = array_search('traits', array_keys($form));

    $form = array_merge(
      array_slice($form, 0, $traits_element_form_array_index + 1),
      $our_form,
      array_slice($form, $traits_element_form_array_index + 1)
    );

    // Add our validate handler, which ensures that all the various config
    // entities join up properly.
    $form['#validate'][] = [get_class($this), 'formValidate'];

    // Add our submit handler, which saves our third-party settings.
    $form['actions']['submit']['#submit'][] = [get_class($this), 'formSubmit'];
  }

  /**
   * Form validation callback.
   *
   * Ensures that everything joins up when a license trait is used.
   */
  public static function formValidate($form, FormStateInterface $form_state) {
    $traits = $form_state->getValue('traits');
    $original_traits = $form_state->getValue('original_traits');

    // Only validate if our trait is in use. Need to check both new traits and
    // existing traits values, as the 'traits' form value won't have a value for
    // a checkbox that's disabled because an existing trait can't be removed.
    if (empty($traits['commerce_license']) && !in_array('commerce_license', $original_traits)) {
      return;
    }

    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
    $order_item_type_id = $form_state->getValue('orderItemType');
    $order_item_type = $entity_type_manager->getStorage('commerce_order_item_type')->load($order_item_type_id);
    // The checkout flow may not allow anonymous checkout.
    $order_type_id = $order_item_type->getOrderTypeId();

    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $entity_type_manager->getStorage('commerce_order_type')->load($order_type_id);
    $checkout_flow_id = $order_type->getThirdPartySetting('commerce_checkout', 'checkout_flow');
    if ($checkout_flow_id) {
      $checkout_flow = $entity_type_manager->getStorage('commerce_checkout_flow')->load($checkout_flow_id);
      $login_pane_configuration = $checkout_flow->get('configuration')['panes']['login'];
      if ($login_pane_configuration['step'] != '_disabled') {
        if ($login_pane_configuration['allow_guest_checkout']) {
          $form_state->setError($form['orderItemType'], t(
            "The License trait requires a checkout flow that does not allow guest checkout. " .
            'This product variation is set to use the @order-item-type-label order item type, ' .
            'which is set to use the @order-type-label order type, ' .
            'which is set to use the @flow-label checkout flow. ' .
            'You must either change this, or <a href="@url-checkout-flow">edit the checkout flow</a>.',
            [
              '@order-item-type-label' => $order_item_type->label(),
              '@order-type-label' => $order_type->label(),
              '@flow-label' => $checkout_flow->label(),
              '@url-checkout-flow' => $checkout_flow->toUrl('edit-form')->toString(),
            ]
          ));
        }
      }
    }
  }

  /**
   * Form submit handler.
   *
   * Saves our third-party settings into the product variation type.
   */
  public static function formSubmit($form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = $form_state->getFormObject()->getEntity();
    $save_variation_type = FALSE;
    if (!$variation_type->hasTrait('commerce_license')) {
      $license_settings = $variation_type->getThirdPartySettings('commerce_license');
      if (!empty($license_settings)) {
        $save_variation_type = TRUE;
        $variation_type->unsetThirdPartySetting('commerce_license', 'license_types');
        $variation_type->unsetThirdPartySetting('commerce_license', 'activate_on_place');
      }
    }
    else {
      $save_variation_type = TRUE;
      $license_types = array_filter($form_state->getValue('license_types'));
      $variation_type->setThirdPartySetting('commerce_license', 'license_types', $license_types);

      $activate_on_place = $form_state->getValue('activate_on_place');
      $variation_type->setThirdPartySetting('commerce_license', 'activate_on_place', $activate_on_place);

      $order_item_type_id = $form_state->getValue('orderItemType');

      /** @var \Drupal\commerce_order\Entity\OrderItemType $order_item_type */
      $order_item_type = \Drupal::entityTypeManager()->getStorage('commerce_order_item_type')->load($order_item_type_id);
      $traits = $order_item_type->getTraits();

      // If the license trait for the selected order item type isn't selected,
      // automatically install it.
      if (!in_array('commerce_license_order_item_type', $traits)) {
        /** @var \Drupal\commerce\EntityTraitManager $trait_manager */
        $trait_manager = \Drupal::service('plugin.manager.commerce_entity_trait');
        $trait = $trait_manager->createInstance('commerce_license_order_item_type');
        $trait_manager->installTrait($trait, $order_item_type->getEntityType()->getBundleOf(), $order_item_type->id());
        $traits[] = 'commerce_license_order_item_type';
        $order_item_type->setTraits($traits);
        $order_item_type->save();

        \Drupal::messenger()->addMessage(t('The License trait requires an order item type with the order item license trait, it was automatically installed for your convenience.'));
      }
    }

    if ($save_variation_type) {
      // This is saving it a second time... but Commerce does the same in its form
      // alterations.
      $variation_type->save();
    }
  }

}
