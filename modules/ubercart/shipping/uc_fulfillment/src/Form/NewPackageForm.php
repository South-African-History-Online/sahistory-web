<?php

namespace Drupal\uc_fulfillment\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_order\OrderInterface;

/**
 * Puts ordered products into a package.
 */
class NewPackageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_new_package';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'uc_fulfillment/uc_fulfillment.scripts';
    $shipping_types_products = array();
    foreach ($uc_order->products as $product) {
      if (uc_order_product_is_shippable($product)) {
        $shipping_type = uc_product_get_shipping_type($product);
        $shipping_types_products[$shipping_type][] = $product;
      }
    }

    $quote_config = \Drupal::config('uc_quote.settings');
    $shipping_type_weights = $quote_config->get('type_weight');

    $result = db_query('SELECT op.order_product_id, SUM(pp.qty) AS quantity FROM {uc_packaged_products} pp LEFT JOIN {uc_packages} p ON pp.package_id = p.package_id LEFT JOIN {uc_order_products} op ON op.order_product_id = pp.order_product_id WHERE p.order_id = :id GROUP BY op.order_product_id', [':id' => $uc_order->id()]);
    $packaged_products = $result->fetchAllKeyed();

    $form['shipping_types'] = array();
    $header = array(
      // Fake out tableselect JavaScript into operating on our table.
      array('data' => '', 'class' => array('select-all')),
      'model' => $this->t('SKU'),
      'name' => $this->t('Title'),
      'qty' => $this->t('Quantity'),
      'package' => $this->t('Package'),
    );

    $shipping_type_options = uc_quote_shipping_type_options();
    foreach ($shipping_types_products as $shipping_type => $products) {
      $form['shipping_types'][$shipping_type] = array(
        '#type' => 'fieldset',
        '#title' => isset($shipping_type_options[$shipping_type]) ?
                          $shipping_type_options[$shipping_type]        :
                          Unicode::ucwords(str_replace('_', ' ', $shipping_type)),
        '#weight' => isset($shipping_type_weights[$shipping_type]) ? $shipping_type_weights[$shipping_type] : 0,
      );

      $form['shipping_types'][$shipping_type]['table'] = array(
        '#type' => 'table',
        '#header' => $header,
        '#empty' => $this->t('There are no products available for this type of package.'),
      );

      foreach ($products as $product) {
        $unboxed_qty = $product->qty->value;
        if (isset($packaged_products[$product->order_product_id->value])) {
          $unboxed_qty -= $packaged_products[$product->order_product_id->value];
        }

        if ($unboxed_qty > 0) {
          $row = array();
          $row['checked'] = array(
            '#type' => 'checkbox',
            '#default_value' => 0,
          );
          $row['model'] = array(
            '#plain_text' => $product->model->value,
          );
          $row['name'] = array(
            '#markup' => $product->title->value,
          );
          $range = range(1, $unboxed_qty);
          $row['qty'] = array(
            '#type' => 'select',
            '#title' => $this->t('Quantity'),
            '#title_display' => 'invisible',
            '#options' => array_combine($range, $range),
            '#default_value' => $unboxed_qty,
          );

          $range = range(0, count($uc_order->products));
          $options = array_combine($range, $range);
          $options[0] = $this->t('Sep.');
          $row['package'] = array(
            '#type' => 'select',
            '#title' => $this->t('Package'),
            '#title_display' => 'invisible',
            '#options' => $options,
            '#default_value' => 0,
          );
          $form['shipping_types'][$shipping_type]['table'][$product->order_product_id->value] = $row;
        }
      }
    }

    $form['order_id'] = array(
      '#type'  => 'hidden',
      '#value' => $uc_order->id(),
    );

    $form['actions'] = array('#type'  => 'actions');
    $form['actions']['create'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Make packages'),
    );
    $form['actions']['combine'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Create one package'),
    );
    $form['actions']['cancel'] = array(
      '#type'  => 'submit',
      '#value' => $this->t('Cancel'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->t('Cancel') != (string) $form_state->getValue('op')) {
      // See if any products have been checked.
      foreach ($form_state->getValue('shipping_types') as $shipping_type => $products) {
        foreach ($products['table'] as $id => $product) {
          if ($product['checked']) {
            // At least one has been checked, that's all we need.
            return;
          }
        }
      }
      // If nothing is checked, set error.
      $form_state->setErrorByName($shipping_type, $this->t('Packages must contain at least one product.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->t('Cancel') != (string) $form_state->getValue('op')) {
      // Package 0 is a temporary array, all other elements are Package objects.
      $packages = array(0 => array());

      foreach ($form_state->getValue('shipping_types') as $shipping_type => $products) {
        foreach ($products['table'] as $id => $product) {
          if ($product['checked']) {
            if ($this->t('Create one package') == (string) $form_state->getValue('op')) {
              $product['package'] = 1;
            }

            if ($product['package'] != 0) {
              if (empty($packages[$product['package']])) {
                // Create an empty package.
                $packages[$product['package']] = Package::create();
              }
              $packages[$product['package']]->addProducts([$id => (object) $product]);
              if (!$packages[$product['package']]->getShippingType()) {
                $packages[$product['package']]->setShippingType($shipping_type);
              }
            }
            else {
              $packages[0][$shipping_type][$id] = (object) $product;
            }
          }
        }
        if (isset($packages[0][$shipping_type])) {
          // We reach here if some packages were checked and marked "Separate".
          // That can only happen when "Make packages" button was pushed.
          foreach ($packages[0][$shipping_type] as $id => $product) {
            $qty = $product->qty;
            $product->qty = 1;
            // Create a package for each product.
            for ($i = 0; $i < $qty; $i++) {
              $packages[] = Package::create(['products' => [$id => $product], 'shipping_type' => $shipping_type]);
            }
          }
        }
        // "Separate" packaging is now finished.
        unset($packages[0][$shipping_type]);
      }

      if (empty($packages[0])) {
        // This should always be true?
        unset($packages[0]);
      }

      foreach ($packages as $package) {
        $package->setOrderId($form_state->getValue('order_id'));
        $package->save();
      }

      $form_state->setRedirect('uc_fulfillment.packages', ['uc_order' => $form_state->getValue('order_id')]);
    }
    else {
      // Fall through, if user chose "Cancel".
      $form_state->setRedirect('entity.uc_order.canonical', ['uc_order' => $form_state->getValue('order_id')]);
    }
  }

}
