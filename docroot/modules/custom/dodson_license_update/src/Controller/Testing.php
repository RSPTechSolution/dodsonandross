<?php

namespace Drupal\dodson_license_update\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use \Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * 
 */
class Testing extends ControllerBase {
	
	public function index() {
		$current_user = \Drupal::currentUser();
        $current_user_uid = $current_user->id();

        // echo '<pre>';var_dump($current_user);die;

        // $query = \Drupal::database()->select('commerce_license', 'cl');
        //     // $query->join('vapn', 'va', 'va.nid = n.nid');
        //     $query->fields('cl');
        //     // $query->fields('va', ['rid']);
        //     // $orGroup1 = $query->orConditionGroup()
        //     //             ->condition('n.type', 'audio_podcast', '=')
        //     //             ->condition('n.type', 'premium_videos', '=')
        //     //             ->condition('n.type', 'page', '=');
        //     // $query->condition($orGroup1);

        //     $query->condition('cl.uid', $current_user_uid, '=');
        //     $query->condition('cl.state', 'active', '=');
        //     $query->orderBy('created', 'DESC');
        //     $query->range(0, 1);
        //     $records = $query->execute()->fetchAll();
        //     echo '<pre>';print_r($records);
            // if(!empty($records)) {
            	$query1 = \Drupal::database()->select('commerce_order', 'co');
	            $query1->fields('co');
	            $query1->condition('co.uid', $current_user_uid, '=');
	            // $query1->condition('co.state', 'fulfillment', '=');
	            // $query1->orderBy('created', 'DESC');
	            // $query1->range(0, 1);
	            $record1 = $query1->execute()->fetchAll();
	            // echo '<pre>';print_r($record1);die;
	            if(!empty($record1)) {
	            	$license_order_items = [];
	            	$order_items = [];
	            	foreach ($record1 as $orders) {
	            		$order = Order::load($orders->order_id);
	            		// $license_order_items[] = $this->getLicensableOrderItems($order);

	            		foreach ($order->getItems() as $order_item) {
	            			if(!$order_item->hasField('license')) {
						    	continue;
						    }

						    $purchased_entity = $order_item->getPurchasedEntity();
						    if(!$purchased_entity->hasField('license_type') || $purchased_entity->get('license_type')->isEmpty()) {
						        continue;
						    }

						    $order_items[] = $order_item;
	            		}
	            		
	         //    		foreach ($license_order_items as $order_item) {
		        //     		$license = $order_item->get('license')->entity;
		        //     		// echo '<pre>';print_r($license);die;
		        //     		if(!$license) {
						    // 	continue;
						    // }
						    // $license->delete();

					    	// $order->delete();
	         //    		}

	            	}
	            	$line_items = count($order_items) - 1;
	            	if(!empty($order_items)) {
	            		foreach ($order_items as $key => $value) {
	            			if($key != $line_items) {
	            				$license = $value->get('license')->entity;
		            			$order_id = $value->get('order_id')->getValue()[0]['target_id'];
		            			$order2 = Order::load($order_id);
		            			if(!$license) {
							    	continue;
							    }
							    $license->delete();
							    $order2->delete();
	            			}
	            			
	            		}
	            	}
	            	
	            		echo '<pre>';var_dump($order_items[0]->get('order_id')->getValue());die;
	            	
	            	// echo '<pre>';print_r($order);die;
	            	
	            	// $items = $order->getItems();
	            	// echo '<pre>';print_r($license_order_items);die;
	            	
	            	if(count($license_order_items) > 0) {
	            		$obj = ['success fully deleted'];
		            	\Drupal::messenger()->addMessage(t('success fully deleted'));
		            	return $obj;
	            	}else {
	            		$obj = ['data does not exists'];
            			\Drupal::messenger()->addMessage(t('data does not exists'));
            			return $obj;
	            	}
	            	
	            }
            // }
            // else{
            // 	$obj = ['data does not exists'];
            // 	\Drupal::messenger()->addMessage(t('data does not exists'));
            // 	return $obj;die;
            // }

            // echo '<pre>';print_r($records);die;
	}

	  protected function getLicensableOrderItems(OrderInterface $order) {
	    $order_items = [];

	    foreach ($order->getItems() as $order_item) {
	      // Skip order items that do not have a license reference field.
	      // We check order items rather than the purchased entity to allow products
	      // with licenses to be purchased without the checkout flow triggering
	      // our synchronization. This is for cases such as recurring orders, where
	      // the license entity should not be put through the normal workflow.
	      // Checking the order item's bundle for our entity trait is expensive, as
	      // it requires loading the bundle entity to call hasTrait() on it.
	      // For now, just check whether the order item has our trait's field on it.
	      // @see https://www.drupal.org/node/2894805
	      if (!$order_item->hasField('license')) {
	        continue;
	      }
	      $purchased_entity = $order_item->getPurchasedEntity();

	      // This order item isn't "licensable" if the purchased entity it
	      // references isn't properly configured.
	      if (!$purchased_entity->hasField('license_type') || $purchased_entity->get('license_type')->isEmpty()) {
	        continue;
	      }

	      $order_items[] = $order_item;
	    }

	    return $order_items;
	  }
}

?>