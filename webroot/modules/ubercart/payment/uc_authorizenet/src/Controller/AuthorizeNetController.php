<?php

namespace Drupal\uc_authorizenet\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for PayPal routes.
 */
class AuthorizeNetController extends ControllerBase {

  /**
   * Page callback for Authorize.Net's Silent POST feature.
   * Receives a payment notification and handles it appropriately.
   */
  public function silentPost() {
    // Determine if this is an ARB notification or not
    $arb = (isset($_POST['x_subscription_id']) and isset($_POST['x_subscription_paynum']));

    // Log ARB payment notification, if enabled.
    if (variable_get('uc_authnet_report_arb_post', FALSE)) {
      $args = array(
        '@arb' => $arb ? 'ARB ' : '',
        '@order_id' => $_POST['x_invoice_num'],
        '@post' => print_r($_POST, TRUE),
      );
      \Drupal::logger('uc_authorizenet')->notice('@arbSilent POST received for order @order_id: <pre>@post</pre>', $args);
    }

    // Decrypt the Auth.Net API login data.
    $login_data = _uc_authorizenet_login_data();

    // TODO: Modify the MD5 hash to accommodate differences from AIM to ARB.

    // This is an ARB notification.
    if ($arb) {

      // Compare our expected MD5 Hash against what was received.
      $md5 = strtoupper(md5($login_data['md5_hash'] . $_POST['x_trans_id'] . $_POST['x_amount']));

      // Post an error message if the MD5 hash does not validate.
      if ($_POST['x_MD5_Hash'] != $md5) {
        \Drupal::logger('uc_authorizenet')->error('Invalid ARB payment notification received.');
      }
      // Otherwise, let other modules act on the data.
      else {
        \Drupal::moduleHandler()->invokeAll('uc_auth_arb_payment', array($_POST));
      }
    }

    exit();
  }

}
