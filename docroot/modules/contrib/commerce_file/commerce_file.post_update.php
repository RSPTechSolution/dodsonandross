<?php

/**
 * @file
 * Post update functions for Commerce File.
 */

/**
 * Revert the licensed files view.
 */
function commerce_file_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $result = $config_updater->revert([
    'views.view.commerce_file_my_files',
  ], FALSE);
  $message = implode('<br>', $result->getFailed());

  return $message;
}
