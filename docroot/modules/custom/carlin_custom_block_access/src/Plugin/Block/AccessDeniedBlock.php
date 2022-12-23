<?php

namespace Drupal\carlin_custom_block_access\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'article' block.
 *
 * @Block(
 *   id = "access_denied_block",
 *   admin_label = @Translation("Content On Access Denied Pages"),
 *   category = @Translation("Content On Access Denied Pages")
 * )
 */

class AccessDeniedBlock extends BlockBase {

	public function build() {
		$route_name = \Drupal::routeMatch()->getRouteName();

		// echo '<pre>';print_r($route_name);die;
		if($route_name == 'system.403') {
			
	        
	     	$markup = '<p>Upgrade Your Subscription To Get Access.</p><br><a href="/pricing" class="btn btn-primary">Click Here</a>';
	        // echo "<pre>";print_r($markup);die;
		    return array(
		      '#type' => 'markup',
		      '#markup' => $markup,
		    );
		}
	}
}

?>