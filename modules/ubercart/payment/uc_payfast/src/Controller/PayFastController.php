<?php

namespace Drupal\uc_payfast\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\uc_order\Entity\Order;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\uc_order\OrderInterface;
use Drupal\Component\Utility\SafeMarkup;


/**
 * Returns responses for PayFast routes.
 */
class PayFastController extends ControllerBase {

  /**
   * Processes the ITN.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An empty Response with HTTP status code 200.
   */
  public function itn(Request $request) {
    $this->processItn($request->request->all());
    return new Response();
  }

  /**
   * Processes Instant Transfer Notifications from PayFast.
   *
   * @param array $itn
   *   The ITN data.
   */
  public function processItn($itn)
  {
    require_once('payfast_common.inc');

    // Extract order and cart IDs.
    $order_id = $itn['m_payment_id'];
    $order = Order::load($order_id);

    $plugin = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);
    $configuration = $plugin->getConfiguration();
    
    $pfError = false;
    $pfErrMsg = '';
    $pfDone = false;
    $pfData = array();
    $pfHost = $configuration['server'];
    $pfOrderId = '';
    $pfParamString = '';
    pflog('PayFast ITN call received');
    
//// Notify PayFast that information has been received
    if (!$pfError && !$pfDone) {
      header('HTTP/1.0 200 OK');
      flush();
    }
    
//// Get data sent by PayFast
    if (!$pfError && !$pfDone) {
      pflog('Get posted data');
      // Posted variables from ITN
      $pfData = pfGetData();
      pflog('PayFast Data: ' . print_r($pfData, true));
      if ($pfData === false) {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
      }
    }
    
//// Verify security signature
    if (!$pfError && !$pfDone) {
      pflog('Verify security signature');
      
      $passPhrase = $configuration['passphrase'];
      $pfPassPhrase = empty($passPhrase) ? null : $passPhrase;

      
      // If signature different, log for debugging
      if (!pfValidSignature($pfData, $pfParamString, $pfPassPhrase)) {
        $pfError = true;
        $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
      }
    }

//// Verify source IP (If not in debug mode)
    if (!$pfError && !$pfDone) {
      pflog('Verify source IP');
      if (!pfValidIP($_SERVER['REMOTE_ADDR'])) {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
      }
    }

//// Verify data received
    if (!$pfError) {
      pflog('Verify data received');

      $pfValid = pfValidData($pfHost, $pfParamString);
      if (!$pfValid) {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
      }
    }

//// Check amounts
    if( !$pfError && !$pfDone )
    {
      pflog( 'Check amounts');

      if( !pfAmountsEqual( $order->getTotal(), $itn['amount_gross'] ) )
      {
        $pfError = true;
        $pfErrMsg = PF_ERR_AMOUNT_MISMATCH;
      }
    }

    if ($pfError) {
      pflog('Error occurred: ' . $pfErrMsg);
    }
//// Check status and update order
    if (!$pfError && !$pfDone) {
      pflog('Check Status and Update Order');


      $amount = $itn['amount_gross'];
      //  $email = !empty($itn['business']) ? $itn['business'] : $itn['receiver_email'];
      $txn_id = $itn['pf_payment_id'];

      if (!isset($itn['m_payment_id'])) {
        \Drupal::logger('uc_payfast')->error('ITN attempted with invalid order ID.');
        return;
      }

      if (!$order) {
        \Drupal::logger('uc_payfast')->error('ITN attempted for non-existent order @order_id.', ['@order_id' => $order_id]);
        return;
      }

      db_insert('uc_payfast_itn')
          ->fields(array(
              'pf_payment_id' => $itn['pf_payment_id'],
              'merchant_id' => $itn['merchant_id'],
              'email_address' => $itn['email_address'],
              'order_id' => $itn['m_payment_id'],
              'transaction_id' => $itn['pf_payment_id'],
              'amount_gross' => $itn['amount_gross'],
              'amount_fee' => $itn['amount_fee'],
              'payment_status' => $itn['payment_status'],
              'server' => $pfHost,
              'created' => REQUEST_TIME,
              'changed' => REQUEST_TIME,
          ))
          ->execute();

      switch ($itn['payment_status']) {
        case 'COMPLETE':
          $comment = 'PayFast payment ID: ' . $itn['pf_payment_id'];
          if (abs($amount - $order->getTotal()) > 0.01) {
            \Drupal::logger('uc_payfast')->warning('Payment @txn_id for order @order_id did not equal the order total.', ['@txn_id' => $txn_id, '@order_id' => $order->id(), 'link' => Link::createFromRoute($this->t('view'), 'entity.uc_order.canonical', ['uc_order' => $order->id()])->toString()]);
          }

          uc_payment_enter($order_id, 'payfast', $amount, $order->getOwnerId(), NULL, $comment);
          uc_order_comment_save($order_id, 0, $this->t('PayFast ITN reported a payment of @amount @currency.', ['@amount' => uc_currency_format($amount, FALSE), '@currency' => 'ZAR']));
          break;

        case 'Failed':
          uc_order_comment_save($order_id, 0, $this->t("The customer's attempted payment from a bank account failed."), 'admin');
          break;

      }
    }
  }

  public function complete(OrderInterface $uc_order) {
    $session = \Drupal::service('session');
    if (!$session->has('cart_order') || intval($session->get('cart_order')) != $uc_order->id()) {
      drupal_set_message($this->t('Thank you for your order! PayFast will notify us once your payment has been processed.'));
      return $this->redirect('uc_cart.cart');
    }

    // This lets us know it's a legitimate access of the complete page.
    $session = \Drupal::service('session');
    $session->set('uc_checkout_complete_' . $uc_order->id(), TRUE);

    return $this->redirect('uc_cart.checkout_complete');
  }

}
