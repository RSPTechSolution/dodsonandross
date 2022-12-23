<?php

/**
* @file
* Contains \Drupal\carlin_subscription_updates\OrderCompleteSubscriber\.
*/

namespace Drupal\carlin_subscription_updates\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;
use \Drupal\commerce_order\Entity\Order;
use \Drupal\user\Entity\User;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_order\Entity\OrderInterface;


    /**
    * Class OrderCompleteSubscriber.
    *
    * @package Drupal\mymodule
    */

class OrderCompleteSubscriber implements EventSubscriberInterface {

	static function getSubscribedEvents() {
     $events['commerce_order.place.post_transition'] =    ['orderCompleteHandler'];
     // $events['commerce_order.cancel.pre_transition'] = 'orderCancelHandler';
     $events[CartEvents::CART_ENTITY_ADD] = ['onProductAdded'];

		return $events;
	}



   public function orderCompleteHandler(WorkflowTransitionEvent $event) {
        // $order = $event->getEntity();
       
        // $items = $order->getItems();
        // // $orders = $items[0]->getOrder();
        // $purchasedEntity = $items[0]->getPurchasedEntity();

        $current_user = \Drupal::currentUser();
        $current_user_uid = $current_user->id();
        $roles = $current_user->getRoles();

        // if(in_array('body_sex_client', $roles) || in_array('body_sex_leader', $roles) || in_array('basic_user', $roles)) {


            $query1 = \Drupal::database()->select('commerce_order', 'co');
                $query1->fields('co');
                $query1->condition('co.uid', $current_user_uid, '=');
                // $query1->condition('co.state', 'fulfillment', '=');
                // $query1->orderBy('created', 'DESC');
                $record1 = $query1->execute()->fetchAll();
                if(!empty($record1)) {


                    $order_items = [];
                    foreach ($record1 as $orders) {
                        $order = Order::load($orders->order_id);
                        // $license_order_items[] = $this->getLicensableOrderItems($order);

                        foreach ($order->getItems() as $order_item) {
                            if(!$order_item->hasField('license')) {
                                continue;
                            }
                            $purchased_entity = $order_item->getPurchasedEntity();
                            if(empty($purchased_entity)){
                                continue;  
                            }
                            if(!$purchased_entity->hasField('license_type')){
                                continue;
                            }
                            if(!$purchased_entity->get('license_type')->isEmpty()) {
                                continue;
                            }
                            $order_items[] = $order_item;
                        }
                        
             
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


                }
            // }



                 // foreach ($record1 as $orders) {
                    //     $order1 = Order::load($orders->order_id);
                    //     $license_order_items = $this->getLicensableOrderItems($order1);
                    //     $line_items = count($license_order_items) - 1;
                    //     foreach ($license_order_items as $item_key => $order_item) {

                    //         if($item_key == $line_items) {
                    //             $license = $order_item->get('license')->entity;
                    //         // echo '<pre>';print_r($license);die;
                    //             if(!$license) {
                    //                 continue;
                    //             }
                    //             $license->delete();

                    //             $order1->delete();
                    //         }
                            
                    //     }

                    // }












                
        
            // $query = \Drupal::database()->select('commerce_license', 'cl');
            //     $query->fields('cl');
            //     $query->condition('cl.uid', $current_user_uid, '=');
            //     $query->orderBy('created', 'DESC');
            //     $query->range(1, 1);
            //     $records = $query->execute()->fetchAll();
            //     // echo '<pre>';print_r($records);

            //     if(!empty($records)) {
            //         $query1 = \Drupal::database()->select('commerce_order', 'co');
            //         $query1->fields('co');
            //         $query1->condition('co.uid', $current_user_uid, '=');
            //         // $query1->condition('co.state', 'fulfillment', '=');
            //         $query1->orderBy('created', 'DESC');
            //         $query1->range(1, 1);
            //         $record1 = $query1->execute()->fetchAssoc();
            //         // echo '<pre>';print_r($record1);die;
                    
            //         if(!empty($record1)) {
            //             $order = Order::load($record1['order_id']);
                        
            //             $license_order_items = $this->getLicensableOrderItems($order);
            //             foreach ($license_order_items as $order_item) {
            //                 $license = $order_item->get('license')->entity;
            //                 if(!$license) {
            //                     continue;
            //                 }
            //                 $license->delete(); 
            //             }
            //             $order->delete();
            //         }
            //     }
    		
        
        // if($order->getState()->getId() == 'completed') {

            


            // $product = $purchasedEntity->getProduct();
            // $product_type = $product->bundle();
        
            // if($product_type == 'recurring_products' || $product_type == 'podcast'){
            //     $product_id = $product->id();
            //     $customer = $order->getCustomer();
            //     $user_id = $customer->id();
            //     $user = User::load($user_id);
            //     if($product_id == 51 || $product_id == 61){
            //         $user->addRole('access_audio_podcast');
            //     }
            //     $user->save();
            // }
        // } elseif($order->getState()->getId() == 'canceled') {
        //     $user = User::load($order->getCustomerId());
        //     $roles = $user->getRoles();
           
        //     if(in_array("access_audio_podcast", $roles)) {
        //       $user->removeRole('access_audio_podcast');
        //       $user->save();
        //     }
            
        // } elseif($order->getState()->getId() == 'needs_payment') {
        //     $user = User::load($order->getCustomerId());
        //     $roles = $user->getRoles();
           
        //     if(in_array("access_audio_podcast", $roles)) {
        //       $user->removeRole('access_audio_podcast');
        //       $user->save();
        //     }
        // }   
        
	}


    // public function orderCancelHandler(WorkflowTransitionEvent $event) {
    //     $order = $event->getEntity();
    //     $items = $order->getItems();
        
    //     $user = User::load($order->getCustomerId());

    //     $roles = $user->getRoles();
        
    //     if(in_array("access_audio_podcast", $roles)) {
    //         $user->removeRole('access_audio_podcast');
    //         $user->save();
    //     } 
        


    // }

    public function onProductAdded(CartEntityAddEvent $event) {
        $cart = $event->getCart();
        $added_order_item = $event->getOrderItem();
        $cart_items = $cart->getItems();
        foreach ($cart_items as $cart_item) {
            if ($cart_item->id() != $added_order_item->id()) {
                $cart->removeItem($cart_item);
                $cart_item->delete();
            }
        }

        $quantity = $cart_items[0]->getQuantity();
        if ($quantity > 1) {
            $cart_items[0]->setQuantity(1);
        }

        $cart->save();
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