<?php

namespace Drupal\block_ajax\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Refresh Ajax Block Command.
 */
class AjaxBlockRefreshCommand implements CommandInterface {

  /**
   * The block selector.
   *
   * @var string
   */
  protected string $selector;

  /**
   * Refresh Ajax Block Command Constructor.
   *
   * @param string $selector
   *   The selector.
   */
  public function __construct(string $selector) {
    $this->selector = $selector;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'AjaxBlockRefreshCommand',
      'selector' => $this->selector,
    ];
  }

}
