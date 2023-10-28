<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Manage the order's billing address and contact information..
 *
 * @UbercartOrderPane(
 *   id = "billing",
 *   title = @Translation("Bill to"),
 *   weight = 1,
 * )
 */
class BillTo extends AddressPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($order, $form, $form_state);
    $form['copy-address-image']['#attributes'] = array('id' => 'copy-shipping-to-billing');
    $form['copy-address-image']['#title'] = $this->t('Copy shipping information.');
    $form['copy-address-image']['#alt'] = $this->t('Copy shipping information.');

    return $form;
  }

}
