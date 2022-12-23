<?php

namespace Drupal\carlin_custom_block_access\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'article' block.
 *
 * @Block(
 *   id = "my_plan_custom",
 *   admin_label = @Translation("My Plan Custom"),
 *   category = @Translation("My Plan Custom")
 * )
 */


class MyPlanPageCustomBlock extends BlockBase {
	
	public function build() {
		$account = \Drupal::currentUser();
    	$role = $account->getRoles();

    	if(in_array('body_sex_client', $role) || in_array('basic_user', $role)) {

    		$markup = '<p>Upgrade your plan to see more videos, listen more podcast and access custom created content.</p>';
    		$markup .= '<a href="/pricing" class="btn btn-primary">Upgrade Now</a>';

    		return array(
		      '#type' => 'markup',
		      '#markup' => $markup,
		    );
    	}
	}
}


?>