<?php

namespace Drupal\uc_paypal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod\PayPalPaymentsStandard;

/**
 * Returns responses for PayPal routes.
 */
class WpsController extends ControllerBase {

  /**
   * Handles a complete PayPal Payments Standard sale.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart or checkout complete page.
   */
  public function wpsComplete(OrderInterface $uc_order) {
    // If the order ID specified in the return URL is not the same as the one in
    // the user's session, we need to assume this is either a spoof or that the
    // user tried to adjust the order on this side while at PayPal. If it was a
    // legitimate checkout, the IPN will still come in from PayPal so the order
    // gets processed correctly. We'll leave an ambiguous message just in case.
    $session = \Drupal::service('session');
    if (!$session->has('cart_order') || intval($session->get('cart_order')) != $uc_order->id()) {
      drupal_set_message($this->t('Thank you for your order! PayPal will notify us once your payment has been processed.'));
      return $this->redirect('uc_cart.cart');
    }

    // Ensure the payment method is PayPal WPS.
    $method = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($uc_order);
    if (!$method instanceof PayPalPaymentsStandard) {
      return $this->redirect('uc_cart.cart');
    }

    // This lets us know it's a legitimate access of the complete page.
    $session = \Drupal::service('session');
    $session->set('uc_checkout_complete_' . $uc_order->id(), TRUE);

    return $this->redirect('uc_cart.checkout_complete');
  }

}
