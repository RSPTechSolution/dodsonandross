INTRODUCTION
------------

The Commerce License allows the creation of products that sell access to some
aspect of the site. This could be a role, publication of a node, and so on.

This access is controlled by a License entity, which is created for the user
when the product is purchased.

The nature of what a License entity grants is handled by License type plugins.
Each License entity will have one License type plugin associated with it.

A product variation that sells a License will have a configured License type
plugin field value. This acts as template to create the License when a user
purchases that product variation.


REQUIREMENTS
------------

This module requires the following modules:

 * Commerce (https://drupal.org/project/commerce)
 * Recurring Period (https://drupal.org/project/recurring_period)
 * Advanced Queue (https://drupal.org/project/advancedqueue)

This module also integrates with Commerce Recurring
(https://drupal.org/project/commerce_recurring) to provide licenses that
automatically renew with a subscription.

The following patches are required for core versions prior to 9.3.x:

 * https://www.drupal.org/project/drupal/issues/2911473#comment-12676912
  Selected yet disabled individual options from checkboxes element don't persist
  through save.

The following patches are recommended:

 * https://www.drupal.org/project/commerce/issues/2930979: Don't show the
  'added to your cart' message if the item quantity is unchanged.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules for
further information.

CONFIGURATION
-------------

To create products that grant licenses that expire:

1 Configure or create a checkout flow which does not allow anonymous checkout.
2 Configure or create an Order Type to use the checkout flow.
3 Configure or create an Order Item Type to use the Order Type, and work with
  Licenses.
4 Configure or create a Product Variation Type to use the Order Item Type, and
  provide Licenses.
5 Configure or create a Product Type that uses the Product Variation Type.
6 Create one or more products that provide licenses. In the product variation,
  configure:
  - The license type
  - The expiration.

To create products that grant licenses that renew with a subscription:

1 Configure or create a checkout flow which does not allow anonymous checkout.
2 Configure or create an Order Type to use the checkout flow.
3 Configure or create an Order Item Type to use the Order Type, and work with
  Licenses.
4 Configure or create a Product Variation Type to use the Order Item Type, and
  provide both Licenses and Subscriptions.
5 Configure or create a Product Type that uses the Product Variation Type.
6 Create one or more products that provide licenses and subscriptions. In the
  product variation, configure:
  - The license type
  - The expiration should be 'Unlimited', as the subscription controls this.
  - Set the subscription type to 'License'.
  - Select the billing schedule.

KNOWN ISSUES AND LIMITATIONS
----------------------------

This module is still incomplete, and has the following limitations:

- The admin forms to create/edit licenses are not yet complete. They should only
  be used by developers who know what they are doing. Changing values here can
  break things!
