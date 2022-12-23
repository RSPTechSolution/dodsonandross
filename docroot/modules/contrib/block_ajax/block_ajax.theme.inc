<?php

/**
 * @file
 * Theme for block_ajax blocks.
 */

/**
 * Prepares variables for block_ajax_block template.
 *
 * @param array $variables
 *   An associative array.
 */
function template_preprocess_block_ajax_block(array &$variables) {
  if (!empty($variables['block_ajax_contextual_links'])) {
    $variables['attributes']['class'][] = 'contextual-region';
  }
}
