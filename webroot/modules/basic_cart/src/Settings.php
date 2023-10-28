<?php

namespace Drupal\basic_cart;

/**
 * Settings file for basic cart.
 */
class Settings {
  protected $checkoutSettings;
  protected $cartSettings;

  const FIELD_ADDTOCART    = 'addtocart';
  const FIELD_ORDERCONNECT = 'orderconnect';
  const BASICCART_ORDER    = 'basic_cart_order';

  /**
   * Force Extending class to define this method.
   */
  public  function checkoutSettings() {
    $return = \Drupal::config('basic_cart.checkout');
    return $return;
  }

  /**
   * Get the cart configuration.
   *
   * @return object
   *   Array of settings
   */
  public static function cartSettings() {
    $return = \Drupal::config('basic_cart.settings');
    return $return;
  }

  /**
   * Return Cart updated message.
   */
  public static  function cartUpdatedMessage() {
    $config = static::cartSettings();
    \Drupal::messenger()->addStatus(t($config->get('cart_updated_message')));
  }

  /**
   * Formats the input $price in the desired format.
   *
   * @param float $price
   *   The price in the raw format.
   *
   * @return price
   *   The price in the custom format.
   */
  public static function formatPrice($price) {
    $config = self::cartSettings();
    $format = $config->get('price_format');
    $currency = $config->get('currency');

    $price = (float) $price;
    switch ($format) {
      case 0:
        $price = number_format($price, 2, ',', ' ') . ' ' . $currency;
        break;

      case 1:
        $price = number_format($price, 2, '.', ' ') . ' ' . $currency;
        break;

      case 2:
        $price = number_format($price, 2, '.', ',') . ' ' . $currency;
        break;

      case 3:
        $price = number_format($price, 2, ',', '.') . ' ' . $currency;
        break;

      case 4:
        $price = $currency . ' ' . number_format($price, 2, ',', ' ');
        break;

      case 5:
        $price = $currency . ' ' . number_format($price, 2, '.', ' ');
        break;

      case 6:
        $price = $currency . ' ' . number_format($price, 2, '.', ',');
        break;

      case 7:
        $price = $currency . ' ' . number_format($price, 2, ',', '.');
        break;

      default:
        $price = number_format($price, 2, ',', ' ') . ' ' . $currency;
        break;
    }
    return $price;
  }

  /**
   * Returns the available price formats.
   *
   * @return formats
   *   A list with the available price formats.
   */
  public static function listPriceFormats() {
    $config = self::cartSettings();
    $currency = $config->get('currency');
    return array(
      0 => t('1 234,00 @currency', array('@currency' => $currency)),
      1 => t('1 234.00 @currency', array('@currency' => $currency)),
      2 => t('1,234.00 @currency', array('@currency' => $currency)),
      3 => t('1.234,00 @currency', array('@currency' => $currency)),

      4 => t('@currency 1 234,00', array('@currency' => $currency)),
      5 => t('@currency 1 234.00', array('@currency' => $currency)),
      6 => t('@currency 1,234.00', array('@currency' => $currency)),
      7 => t('@currency 1.234,00', array('@currency' => $currency)),
    );
  }

  /**
   * Returns the final price for the shopping cart.
   *
   * @return mixed
   *   The total price for the shopping cart.
   */
  public static function getTotalPrice() {

    $config = self::cartSettings();
    // Building the return array.
    $return = array(
      'price' => 0,
      'vat' => 0,
      'total' => 0,
    );
    $cart = static::getCart();

    if (empty($cart)) {
      return (object) $return;
    }

    $total_price = 0;
    foreach ($cart['cart'] as $nid => $node) {
      $langcode = $node->language()->getId();
      if ($node->isTranslatable()) {
        $node = $node->getTranslation($langcode);
      }
      $value = $node->get('add_to_cart_price')->getValue();
      if (isset($cart['cart_quantity'][$nid]) && isset($value[0]['value'])) {
        $total_price += $cart['cart_quantity'][$nid] * $value[0]['value'];
      }
      $value = 0;
    }

    $return['price'] = $total_price;

    // Checking whether to apply the VAT or not.
    $vat_is_enabled = (int) $config->get('vat_state');
    if (!empty($vat_is_enabled) && $vat_is_enabled) {
      $vat_value = (float) $config->get('vat_value');
      $vat_value = ($total_price * $vat_value) / 100;
      $total_price += $vat_value;
      // Adding VAT and total price to the return array.
      $return['vat'] = $vat_value;
    }

    $return['total'] = $total_price;
    return (object) $return;
  }

}
