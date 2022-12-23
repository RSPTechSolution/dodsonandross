<?php

namespace Drupal\dodson_license_update\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use \Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_license\Entity\LicenseInterface;

class UpdateLicense extends FormBase{

	public function getFormId(){
        return 'dodson_license_update';
    }

    public function buildForm(array $form, FormStateInterface $form_state){
    	$form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
        ];
        
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    	$database = \Drupal::database();
		$query = $database->select('commerce_license', 'u');
		$query->condition('u.uid', 0, '<>');
		$query->condition('u.state', 'active', '=');
		$query->fields('u', ['uid']);
		$result = $query->execute()->fetchAll();

		// echo '<pre>';print_r($result);die;



        // $query1 = $database->select('commerce_license', 'cl');
        // $query1->join('commerce_order', 'co', 'cl.uid = co.uid');
        // $query1->condition('cl.uid', 93266, '=');
        // $query1->condition('cl.state', 'active', '=');
        // $query1->condition('co.type', 'license_content_order', '=');
        // $query1->fields('cl', ['license_id', 'uid', 'state', 'product_variation', 'expiration_type__target_plugin_id', 'granted', 'expires']);
        // $query1->fields('co', ['order_id']);
        // $row = $query1->execute()->fetchAll();
        // $order = Order::load(43741);
        // $licensable_order_items = getLicensableOrderItems($order);
        // $product_variation = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->load(216);
        // // echo '<pre>';print_r($licensable_order_items);die;


        // foreach ($licensable_order_items as $order_item) {
        //   /** @var \Drupal\commerce_license\Entity\LicenseInterface $license */
        //   // $license = $order_item->get('license')->entity;
        //   // We don't need to do anything if there is already an active license
        //   // referenced by this order item.
        //   // if ($license && $license->getState()->getId() === 'active') {
        //   //   continue;
        //   // }

        //   // if (!$license) {
        //     $license = createLicenseFromOrderItem($order_item);
        //     $license->setGrantedTime($row[0]->granted);
        //     $license->setExpiresTime($row[0]->expires);
        //   // }
        //   $license->set('state', 'active');
        //   $license->save();
        // }


		$batch = array(
            'operations' => array(),
            'finished' => 'batch_finished',
            'title' => t('Updating Data'),
            'progress_message' => 'Processed @current out of @total reste @percentage',
            'error_message' =>'It has an error.',
        );


        $progress = 0;
        $limit = 10;
        $max = count($result);

        while ($progress <= $max) {
        // $endlimit = $progress + $limit;

            $batch['operations'][] = array('updateLicence', array(array_slice($result, $progress , $limit)) );
        

            $progress = $progress + $limit;
            
        }


        batch_set($batch);
		
    }

}


?>