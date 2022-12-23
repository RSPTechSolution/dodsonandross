<?php

/**
 * @file
 * Hooks provided by the Lazyloader module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters asset lazyloading.
 *
 * Settings $vars['lazyloader_ignore'] = TRUE will skip lazyloading for that
 * asset.
 *
 * @param array $vars
 *   The variables passed in a theme_[responsive_]image() call.
 */
function hook_lazyloader_alter(array &$vars) {
  // Skip lazyloading based on the result of some_condition().
  if (some_condition($vars)) {
    $vars['lazyloader_ignore'] = TRUE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
