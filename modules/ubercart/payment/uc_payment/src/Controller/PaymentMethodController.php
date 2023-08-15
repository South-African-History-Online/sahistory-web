<?php

namespace Drupal\uc_payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_payment\PaymentMethodInterface;

/**
 * Route controller for payment methods.
 */
class PaymentMethodController extends ControllerBase {

  /**
   * Build the payment method instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the payment method.
   *
   * @return array
   *   The payment method instance edit form.
   */
  public function addForm($plugin_id) {
    $entity = $this->entityTypeManager()->getStorage('uc_payment_method')->create(array('plugin' => $plugin_id));

    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Performs an operation on the payment method entity.
   *
   * @param \Drupal\uc_payment\PaymentMethodInterface $uc_payment_method
   *   The payment method entity.
   * @param string $op
   *   The operation to perform, usually 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the payment method listing page.
   */
  public function performOperation(PaymentMethodInterface $uc_payment_method, $op) {
    $uc_payment_method->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label payment method has been enabled.', ['%label' => $uc_payment_method->label()]));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label payment method has been disabled.', ['%label' => $uc_payment_method->label()]));
    }

    $url = $uc_payment_method->toUrl('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

}
