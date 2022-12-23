<?php

namespace Drupal\custom_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\Order;

/**
 * @CommerceCheckoutPane(
 *  id = "custom_completion_message",
 *  label = @Translation("Custom completion message"),
 *  admin_label = @Translation("Custom completion message"),
 * )
 */
class CustomCompletionMessage extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // $markup = "Thanks for your order";
    $order_number = getOrderNumber($complete_form);
    if ($order_number > -1) {
      // $markup = 'Your order number is :' . $order_number;
      $order = Order::load($order_number);
      //ksm($order);
      if(isset($order) && !empty($order)){
        $uid = \Drupal::currentUser()->id();
        if ($order->bundle() == 'license_content_order') {
          $host = 'https://' . \Drupal::request()->getHost();
          $markup .= 'Your order number is :' . $order_number.'<br>Thanks for your donation, the videos are available in the following <a href="https://www.dodsonandross.com/videos">link</a>';
        }elseif($order->bundle() == 'default'){
          $markup .= '<div class="subscription-row"><h2>Your order number is :' . $order_number.'</h2><p>Thanks for your Subscription, the podcasts are available in the following link</p> <a href="/podcast">Podcasts</a></div>';
        }elseif($order->bundle() == 'subscription'){
          $markup .= '<div class="thanks-msg-wrap"><div class="media-wrap"><img src="/themes/custom/dodson/images/flower-media.png"></div><h1>Thank You!</h1><p>Thanks for subscribing and becoming the member of the Bodysex Leader family, now you can access all of the content.</p><a href="/user/'.$uid.'/orders">My Plan</a></div>';
        }else {
          $markup .= '<br>Thanks for your donation.';
        }
      }else {
        $markup .= '<br>Thanks for your donation.';
      }
    }

    $pane_form['message'] = [
      '#markup' => ($markup),
    ];
    return $pane_form;
  }

}

/**
 * getOrderNumber function to get the order number from the complete form
 * @params array
 * return integer
 */
function getOrderNumber(array $complete_form) {
  $order_number = -1;
  if (isset($complete_form['#cache']['tags'][0])) {
    if (!empty($complete_form['#cache']['tags'][0])) {
      $order_number = preg_replace("/[^0-9]/", '', $complete_form['#cache']['tags'][0]);

      if (is_numeric($order_number) && $order_number > -1) {
        return $order_number;
      }
    }
  }
  return $order_number;
}
