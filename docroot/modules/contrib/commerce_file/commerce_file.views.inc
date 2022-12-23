<?php

/**
 * @file
 * Provide views data for commerce_file.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function commerce_file_views_data() {
  $data['commerce_license']['download_limit'] = [
    'title' => t('Download limit'),
    'help' => t('Displays the download limit for a file.'),
    'field' => [
      'id' => 'download_limit',
    ],
  ];

  return $data;
}
