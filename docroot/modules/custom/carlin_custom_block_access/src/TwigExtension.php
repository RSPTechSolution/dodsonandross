<?php

namespace Drupal\carlin_custom_block_access;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Drupal\Core\Render\Markup;
// use Drupal\views\Plugin\views\field\Markup;
use Drupal\Component\Render\MarkupInterface;

/**
 * 
 */
class TwigExtension extends AbstractExtension {
	
	public function getName() {
    	return 'carlin_custom_block_access';
  	}

	public function getFunctions(){
		return [
			new TwigFunction('current_plan', [$this, 'currentplan']),
		];
	}

	public function currentplan($index) {
		$current_user = \Drupal::currentUser();
    	$roles = $current_user->getRoles();

    	if(in_array('basic_user', $roles) && $index == 0) {
    		$class = ' current-plan';
    	}
    	if(in_array('body_sex_client', $roles) && $index == 1) {
    		$class = ' current-plan';
    	}
    	if(in_array('body_sex_leader', $roles) && $index == 2) {
    		$class = ' current-plan';
    	}
		
        return $class;
	}


}


?>