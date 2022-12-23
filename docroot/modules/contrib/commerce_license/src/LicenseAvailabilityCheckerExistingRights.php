<?php

namespace Drupal\commerce_license;

use Drupal\commerce_order\AvailabilityResult;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\commerce\Context;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\ExistingRightsFromConfigurationCheckingInterface;
use Drupal\commerce_order\AvailabilityCheckerInterface;

/**
 * Prevents purchase of a license that grants rights the user already has.
 *
 * This does not check existing licenses, but checks the granted features
 * directly. For example, for a role license, this checks whether the user has
 * the role the license grants, rather than whether they have a license for
 * that role.
 *
 * Using an availability checker rather than an order processor, even though
 * they currently ultimately do the same thing (as availability checkers are
 * processed by AvailabilityOrderProcessor, which is itself an order processor),
 * because eventually availability checkers should deal with hiding the 'add to
 * cart' form -- see https://www.drupal.org/node/2710107.
 *
 * @see \Drupal\commerce_license\LicenseOrderProcessorMultiples
 */
class LicenseAvailabilityCheckerExistingRights implements AvailabilityCheckerInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LicenseAvailabilityCheckerExistingRights object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderItemInterface $order_item) {
    $purchased_entity = $order_item->getPurchasedEntity();

    // This applies only to product variations which have our license trait on
    // them. Check for the field the trait provides, as checking for the trait
    // on the bundle is expensive -- see https://www.drupal.org/node/2894805.
    if (!$purchased_entity->hasField('license_type') || $purchased_entity->get('license_type')->isEmpty()) {
      return FALSE;
    }

    // Don't do an availability check on recurring orders.
    if ($order_item->getOrder() && $order_item->getOrder()->bundle() === 'recurring') {
      return FALSE;
    }

    // This applies only to license types that implement the interface.
    $license_type_plugin = $purchased_entity->license_type->first()->getTargetInstance();
    if ($license_type_plugin instanceof ExistingRightsFromConfigurationCheckingInterface) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function check(OrderItemInterface $order_item, Context $context) {
    // Hand over to the license type plugin configured in the product variation,
    // to let it determine whether the user already has what the license would
    // grant.
    $customer = $context->getCustomer();
    $purchased_entity = $order_item->getPurchasedEntity();
    $license_type_plugin = $purchased_entity->license_type->first()->getTargetInstance();

    // Load the full user entity for the plugin.
    $user = $this->entityTypeManager->getStorage('user')->load($customer->id());
    $existing_rights_result = $license_type_plugin->checkUserHasExistingRights($user);

    if (!$existing_rights_result->hasExistingRights()) {
      return AvailabilityResult::neutral();
    }

    // Show a message that includes the reason from the rights check.
    if ($user->id() == $this->currentUser->id()) {
      $rights_check_message = $existing_rights_result->getOwnerUserMessage();
    }
    else {
      $rights_check_message = $existing_rights_result->getOtherUserMessage();
    }
    $message = $rights_check_message . ' ' . t("You may not purchase the @product-label product.", [
      '@product-label' => $purchased_entity->label(),
    ]);

    return AvailabilityResult::unavailable($message);
  }

}
