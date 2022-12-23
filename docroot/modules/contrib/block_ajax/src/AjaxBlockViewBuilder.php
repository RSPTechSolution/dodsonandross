<?php

namespace Drupal\block_ajax;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a trusted callback to alter the ajax block.
 *
 * @see block_ajax_block_view_alter()
 */
class AjaxBlockViewBuilder implements TrustedCallbackInterface {

  /**
   * Pre render Ajax block.
   *
   * @param array $build
   *   The build render array.
   *
   * @return array
   *   Returns the modified render array.
   */
  public static function preRender(array $build): array {
    // Make sure we have block and id exists.
    if (isset($build['#block']) && $block_id = $build['#block']->id()) {
      // Set block id.
      $build['#block_ajax_id'] = $block_id;

      // Remove the block entity from the render array, to ensure that blocks
      // can be rendered without the block config entity.
      unset($build['#block']);

      // Add ajax block settings to page.
      $build['#attached']['library'][] = 'block_ajax/ajax_blocks';
      $build['#attached']['drupalSettings']['block_ajax']['blocks'][$block_id] = $build['#block_ajax_settings'];
    }

    // Return build array.
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks(): array {
    return [
      'preRender',
    ];
  }

}
