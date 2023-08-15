<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Manage the order's shipping address and contact information.
 *
 * @UbercartOrderPane(
 *   id = "delivery",
 *   title = @Translation("Ship to"),
 *   weight = 1,
 * )
 */
class ShipTo extends AddressPaneBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode != 'customer' || $order->isShippable()) {
      return parent::view($order, $view_mode);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($order, $form, $form_state);
    $form['copy-address-image']['#attributes'] = array('id' => 'copy-billing-to-shipping');
    $form['copy-address-image']['#title'] = $this->t('Copy billing information.');
    $form['copy-address-image']['#alt'] = $this->t('Copy billing information.');

    return $form;
  }

}
