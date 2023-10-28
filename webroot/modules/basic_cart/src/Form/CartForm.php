<?php

namespace Drupal\basic_cart\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\basic_cart\Utility;
use Drupal\Core\Url;

/**
 * Cart page form.
 */
class CartForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'basic_cart_cart_form';

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('basic_cart.settings');
    $form['#theme'] = 'basic_cart_cart_form';
    $cart = Utility::getCart();
    $config = Utility::cartSettings();
    //$langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    //$price = Utility::getTotalPrice();
    //$total = Utility::formatPrice($price->total);
    //$vat_is_enabled = (int) $config->get('vat_state');
   // $vat_value = !empty($vat_is_enabled) && $vat_is_enabled ? Utility::formatPrice($price->vat) : 0;

    // And now the form.
    $form['cartcontents'] = array(
      // Make the returned array come back in tree form.
      '#tree' => TRUE,
    );
    // Cart elements.
    foreach ($cart['cart_quantity'] as $nid => $quantity) {
      $variable = Utility::quantityPrefixData($nid);
      $form['cartcontents'][$nid] = array(
        '#type' => $config->get('quantity_status') ? 'textfield' : 'markup',
        '#size' => 2,
        '#quantity_id'  => $nid,
        "#suffix" => Utility::render('basic_cart_quantity_suffix', $variable),
        "#prefix" => Utility::render('basic_cart_quantity_prefix', $variable),
        '#default_value' => $quantity,
      );
    }
    $form['total_price'] = array(
      '#markup' => Utility::render('basic_cart_total_price_markup', Utility::getTotalPriceMarkupData()),
    );

    // Buttons.
    $form['buttons'] = array(
      '#tree' => TRUE,
    );

    $form['buttons']['update'] = array(
      '#type' => 'submit',
      '#value' => t($config->get('cart_update_button')),
      '#name' => "update",
    );

    if ($config->get('order_status')) {
      $form['buttons']['checkout'] = array(
        '#type' => 'submit',
        '#value' =>(trim($config->get('cart_button_name'))!="" ?$config->get('cart_button_name'):'Checkout'),
        '#name' => "checkout",
      );
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = Utility::cartSettings();
    if ($config->get('quantity_status')) {
      foreach ($form_state->getValue('cartcontents') as $nid => $value) {
        $quantity = (int) $value;
        if ($quantity > 0) {
          $_SESSION['basic_cart']['cart_quantity'][$nid] = $quantity;
        }
        // If the quantity is zero, we just remove the node from the cart.
        elseif ($quantity == 0) {
          unset($_SESSION['basic_cart']['cart'][$nid]);
          unset($_SESSION['basic_cart']['cart_quantity'][$nid]);
        }
      }
      Utility::cartUpdatedMessage();
    }
    $config = Utility::cartSettings();
    if ($config->get('order_status') && $form_state->getValue('checkout')) {
      $url = new Url('basic_cart.checkout');
      $form_state->setRedirectUrl($url);
    }
  }

}
