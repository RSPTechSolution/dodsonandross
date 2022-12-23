<?php

/**
 * @file
 * Post update functions for Commerce License.
 */

/**
 * Configure the view display for subscription licenses.
 */
// function commerce_license_post_update_1() {
//   // Skip the post update if commerce_recurring is not installed.
//   if (!\Drupal::moduleHandler()->moduleExists('commerce_recurring')) {
//     return;
//   }
//   /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
//   $config_updater = \Drupal::service('commerce.config_updater');
//   $result = $config_updater->import([
//     'core.entity_view_display.commerce_subscription.license.default',
//   ]);
//   $message = implode('<br>', $result->getFailed());

//   return $message;
// }
