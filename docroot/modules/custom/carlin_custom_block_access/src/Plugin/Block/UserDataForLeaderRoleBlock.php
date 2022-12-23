<?php

namespace Drupal\carlin_custom_block_access\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'article' block.
 *
 * @Block(
 *   id = "user_data_for_leader_role",
 *   admin_label = @Translation("User Data For Leader Role"),
 *   category = @Translation("User Data For Leader Role")
 * )
 */

class UserDataForLeaderRoleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */

    public function build() {
    	$account = \Drupal::currentUser();
    	$role = $account->getRoles();
    	if(in_array('body_sex_leader', $role)) {
	    	$query = \Drupal::database()->select('node_field_data', 'n');
	        $query->join('vapn', 'va', 'va.nid = n.nid');
	        $query->fields('n', ['nid', 'title', 'type']);
	        $query->fields('va', ['rid']);
	        $orGroup1 = $query->orConditionGroup()
	  					->condition('n.type', 'audio_podcast', '=')
	  					->condition('n.type', 'premium_videos', '=')
	  					->condition('n.type', 'page', '=');
	        $query->condition($orGroup1);

	        $query->condition('va.rid', 'body_sex_leader', '=');
	        $records = $query->execute()->fetchAll();
        // echo '<pre>';print_r($records);die;
        
        	$markup = '';
	        $markup .= '<ul>';
	        foreach ($records as $key => $value) {
	        	if($value->type == 'audio_podcast') {
	        		$markup .= '<li><img src="/themes/custom/dodson/images/Podcast.svg"><a href="/node/'.$value->nid.'">'.$value->title.'</a></li>';
	        	}elseif($value->type == 'premium_videos') {
	        		$markup .= '<li><img src="/themes/custom/dodson/images/Videos.svg"><a href="/node/'.$value->nid.'">'.$value->title.'</a></li>';
	        	}elseif($value->type == 'page') {
	        		$markup .= '<li><img src="/themes/custom/dodson/images/Page.svg"><a href="/node/'.$value->nid.'">'.$value->title.'</a></li>';
	        	}
	        	// $markup .= '<li><a href="/node/'.$value->nid.'">'.$value->title.'</a></li>';
	        }
	        $markup .= '</ul>';
	        // echo "<pre>";print_r($markup);die;
		    return array(
		      '#type' => 'markup',
		      '#markup' => $markup,
		    );
        }
        
  	}
}

?>