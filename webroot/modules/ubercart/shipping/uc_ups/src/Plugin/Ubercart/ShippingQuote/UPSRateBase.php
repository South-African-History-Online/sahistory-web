<?php

namespace Drupal\uc_ups\Plugin\Ubercart\ShippingQuote;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_quote\ShippingQuotePluginBase;

/**
 * Common functionality for UPS shipping quotes plugins.
 */
abstract class UPSRateBase extends ShippingQuotePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'base_rate' => 0,
      'product_rate' => 0,
      'field' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $fields = ['' => $this->t('- None -')];
    $result = \Drupal::entityQuery('field_config')
      ->condition('field_type', 'number')
      ->execute();
    foreach (FieldConfig::loadMultiple($result) as $field) {
      $fields[$field->getName()] = $field->label();
    }

    $form['base_rate'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Base price'),
      '#description' => $this->t('The starting price for shipping costs.'),
      '#default_value' => $this->configuration['base_rate'],
      '#required' => TRUE,
    );
    $form['product_rate'] = array(
      '#type' => 'number',
      '#title' => $this->t('Default product shipping rate'),
      '#min' => 0,
      '#step' => 'any',
      '#description' => $this->t('The percentage of the item price to add to the shipping cost for an item.'),
      '#default_value' => $this->configuration['product_rate'],
      '#field_suffix' => $this->t('% (percent)'),
      '#required' => TRUE,
    );
    $form['field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Product shipping rate override field'),
      '#description' => $this->t('Overrides the default shipping rate per product for this percentage rate shipping method, when the field is attached to a product content type and has a value.'),
      '#options' => $fields,
      '#default_value' => $this->configuration['field'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['base_rate'] = $form_state->getValue('base_rate');
    $this->configuration['product_rate'] = $form_state->getValue('product_rate');
    $this->configuration['field'] = $form_state->getValue('field');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('UPS');
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    // Start with logo as required by the UPS terms of service.
    $build['image'] = array(
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'uc_ups') . '/images/uc_ups_logo.jpg',
      '#alt' => $this->t('UPS logo'),
      '#attributes' => array('class' => array('ups-logo')),
    );
    // Add the UPS service name.
    $build['label'] = array(
      '#plain_text' => $this->t('@service Rate', ['@service' => $label]),
    );
    // Add package information.
    $build['packages'] = array(
      '#plain_text' => ' (' . \Drupal::translation()->formatPlural(count($packages), '1 package', '@count packages') . ')',
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuotes(OrderInterface $order) {
    $rate = $this->configuration['base_rate'];
    $field = $this->configuration['field'];

    foreach ($order->products as $product) {
      if (isset($product->nid->entity->$field->value)) {
        $product_rate = $product->nid->entity->$field->value * $product->qty->value;
      }
      else {
        $product_rate = $this->configuration['product_rate'] * $product->qty->value;
      }

      $rate += $product->price->value * floatval($product_rate) / 100;
    }


    return [$rate];
  }

  /**
   * Organizes products into packages for shipment.
   *
   * @param OrderProduct[] $products
   *   An array of product objects as they are represented in the cart or order.
   * @param Address[] $addresses
   *   Reference to an array of addresses which are the pickup locations of each
   *   package. They are determined from the shipping addresses of their
   *   component products.
   *
   * @return array
   *   Array of packaged products. Packages are separated by shipping address and
   *   weight or quantity limits imposed by the shipping method or the products.
   */
  protected function packageProducts(array $products, array $addresses) {
    $last_key = 0;
    $packages = array();
    $ups_config = \Drupal::config('uc_ups.settings');
    if ($ups_config->get('all_in_one') && count($products) > 1) {
      // "All in one" packaging strategy.
      // Only need to do this if more than one product line item in order.
      $packages[$last_key] = array(0 => $this->newPackage());
      foreach ($products as $product) {
        if ($product->nid->value) {
          // Packages are grouped by the address from which they will be
          // shipped. We will keep track of the different addresses in an array
          // and use their keys for the array of packages.

          $key = NULL;
          $address = uc_quote_get_default_shipping_address($product->nid->value);
          foreach ($addresses as $index => $value) {
            if ($address->isSamePhysicalLocation($value)) {
              // This is an existing address.
              $key = $index;
              break;
            }
          }

          if (!isset($key)) {
            // This is a new address. Increment the address counter $last_key
            // instead of using [] so that it can be used in $packages and
            // $addresses.
            $addresses[++$last_key] = $address;
            $key = $last_key;
            $packages[$key] = array(0 => $this->newPackage());
          }
        }

        // Grab some product properties directly from the (cached) product
        // data. They are not normally available here because the $product
        // object is being read out of the $order object rather than from
        // the database, and the $order object only carries around a limited
        // number of product properties.
        $temp = node_load($product->nid->value);
        $product->length = $temp->length->value;
        $product->width = $temp->width->value;
        $product->height = $temp->height->value;
        $product->length_units = $temp->length_units;
        $product->ups['container'] = isset($temp->ups['container']) ? $temp->ups['container'] : 'VARIABLE';

        $packages[$key][0]->price += $product->price * $product->qty;
        $packages[$key][0]->weight += $product->weight * $product->qty * uc_weight_conversion($product->weight_units, 'lb');
      }
      foreach ($packages as $key => $package) {
        $packages[$key][0]->pounds = floor($package[0]->weight);
        $packages[$key][0]->ounces = LB_TO_OZ * ($package[0]->weight - $packages[$key][0]->pounds);
        $packages[$key][0]->container = 'VARIABLE';
        $packages[$key][0]->size = 'REGULAR';
        // Packages are "machinable" if heavier than 6oz. and less than 35lbs.
        $packages[$key][0]->machinable = (
          ($packages[$key][0]->pounds == 0 ? $packages[$key][0]->ounces >= 6 : TRUE) &&
          $packages[$key][0]->pounds <= 35 &&
          ($packages[$key][0]->pounds == 35 ? $packages[$key][0]->ounces == 0 : TRUE)
        );
        $packages[$key][0]->qty = 1;
      }
    }
    else {
      // !$ups_config->get('all_in_one') || count($products) = 1
      // "Each in own" packaging strategy, or only one product line item in order.
      foreach ($products as $product) {
        if ($product->nid) {
          $address = uc_quote_get_default_shipping_address($product->nid);
          if (in_array($address, $addresses)) {
            // This is an existing address.
            foreach ($addresses as $index => $value) {
              if ($address == $value) {
                $key = $index;
                break;
              }
            }
          }
          else {
            // This is a new address.
            $addresses[++$last_key] = $address;
            $key = $last_key;
          }
        }
        if (!isset($product->pkg_qty) || !$product->pkg_qty) {
          $product->pkg_qty = 1;
        }
        $num_of_pkgs = (int)($product->qty / $product->pkg_qty);
        if ($num_of_pkgs) {
          $package = clone $product;
          $package->description = $product->model;
          $weight = $product->weight * $product->pkg_qty;
          switch ($product->weight_units) {
            case 'g':
              // Convert to kg and fall through.
              $weight = $weight * G_TO_KG;
            case 'kg':
              // Convert to lb and fall through.
              $weight = $weight * KG_TO_LB;
            case 'lb':
              $package->pounds = floor($weight);
              $package->ounces = LB_TO_OZ * ($weight - $package->pounds);
              break;
            case 'oz':
              $package->pounds = floor($weight * OZ_TO_LB);
              $package->ounces = $weight - $package->pounds * LB_TO_OZ;
              break;
          }

          // Grab some product properties directly from the (cached) product
          // data. They are not normally available here because the $product
          // object is being read out of the $order object rather than from
          // the database, and the $order object only carries around a limited
          // number of product properties.
          $temp = node_load($product->nid);
          $product->length = $temp->length;
          $product->width = $temp->width;
          $product->height = $temp->height;
          $product->length_units = $temp->length_units;
          $product->ups['container'] = isset($temp->ups['container']) ? $temp->ups['container'] : 'VARIABLE';

          $package->container = $product->ups['container'];
          $length_conversion = uc_length_conversion($product->length_units, 'in');
          $package->length = max($product->length, $product->width) * $length_conversion;
          $package->width = min($product->length, $product->width) * $length_conversion;
          $package->height = $product->height * $length_conversion;
          if ($package->length < $package->height) {
            list($package->length, $package->height) = array($package->height, $package->length);
          }
          $package->girth = 2 * $package->width + 2 * $package->height;
          $package->size = $package->length <= 12 ? 'REGULAR' : 'LARGE';
          $package->machinable = (
            $package->length >= 6 && $package->length <= 34 &&
            $package->width >= 0.25 && $package->width <= 17 &&
            $package->height >= 3.5 && $package->height <= 17 &&
            ($package->pounds == 0 ? $package->ounces >= 6 : TRUE) &&
            $package->pounds <= 35 &&
            ($package->pounds == 35 ? $package->ounces == 0 : TRUE)
          );
          $package->price = $product->price * $product->pkg_qty;
          $package->qty = $num_of_pkgs;
          $packages[$key][] = $package;
        }
        $remaining_qty = $product->qty % $product->pkg_qty;
        if ($remaining_qty) {
          $package = clone $product;
          $package->description = $product->model;
          $weight = $product->weight * $remaining_qty;
          switch ($product->weight_units) {
            case 'g':
              // Convert to kg and fall through.
              $weight = $weight * G_TO_KG;
            case 'kg':
              // Convert to lb and fall through.
              $weight = $weight * KG_TO_LB;
            case 'lb':
              $package->pounds = floor($weight);
              $package->ounces = LB_TO_OZ * ($weight - $package->pounds);
              break;
            case 'oz':
              $package->pounds = floor($weight * OZ_TO_LB);
              $package->ounces = $weight - $package->pounds * LB_TO_OZ;
              break;
          }
          $package->container = $product->ups['container'];
          $length_conversion = uc_length_conversion($product->length_units, 'in');
          $package->length = max($product->length, $product->width) * $length_conversion;
          $package->width = min($product->length, $product->width) * $length_conversion;
          $package->height = $product->height * $length_conversion;
          if ($package->length < $package->height) {
            list($package->length, $package->height) = array($package->height, $package->length);
          }
          $package->girth = 2 * $package->width + 2 * $package->height;
          $package->size = $package->length <= 12 ? 'REGULAR' : 'LARGE';
          $package->machinable = (
            $package->length >= 6 && $package->length <= 34 &&
            $package->width >= 0.25 && $package->width <= 17 &&
            $package->height >= 3.5 && $package->height <= 17 &&
            ($package->pounds == 0 ? $package->ounces >= 6 : TRUE) &&
            $package->pounds <= 35 &&
          ($package->pounds == 35 ? $package->ounces == 0 : TRUE)
          );
          $package->price = $product->price * $remaining_qty;
          $package->qty = 1;
          $packages[$key][] = $package;
        }
      }
    }
    return $packages;
  }

  /**
   * Pseudo-constructor to set default values of a package.
   */
  protected function newPackage() {
    $package = new stdClass();

    $package->price = 0;
    $package->qty = 1;
    $package->pkg_type = '02';

    $package->weight = 0;
    $package->weight_units = 'lb';

    $package->length = 0;
    $package->width = 0;
    $package->height = 0;
    $package->length_units = 'in';

    return $package;
  }

  /**
   * Modifies the rate received from UPS before displaying to the customer.
   *
   * @param $rate
   *   Shipping rate without any rate markup.
   *
   * @return
   *   Shipping rate after markup.
   */
  protected function rateMarkup($rate) {
    $ups_config = \Drupal::config('uc_ups.settings');
    $markup = trim($ups_config->get('rate_markup'));
    $type   = $ups_config->get('rate_markup_type');

    if (is_numeric($markup)) {
      switch ($type) {
        case 'percentage':
          return $rate + $rate * floatval($markup) / 100;
        case 'multiplier':
          return $rate * floatval($markup);
        case 'currency':
          return $rate + floatval($markup);
      }
    }
    else {
      return $rate;
    }
  }

  /**
   * Modifies the weight of shipment before sending to UPS for a quote.
   *
   * @param $weight
   *   Shipping weight without any weight markup.
   *
   * @return
   *   Shipping weight after markup.
   */
  protected function weightMarkup($weight) {
    $ups_config = \Drupal::config('uc_ups.settings');
    $markup = trim($ups_config->get('weight_markup'));
    $type   = $ups_config->get('weight_markup_type');

    if (is_numeric($markup)) {
      switch ($type) {
        case 'percentage':
          return $weight + $weight * floatval($markup) / 100;

        case 'multiplier':
          return $weight * floatval($markup);

        case 'mass':
          return $weight + floatval($markup);
      }
    }
    else {
      return $weight;
    }
  }

  //public function getQuotes(OrderInterface $order) {
}
