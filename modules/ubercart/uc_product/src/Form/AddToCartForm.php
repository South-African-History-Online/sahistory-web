<?php

namespace Drupal\uc_product\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Defines a complex form for adding a product to the cart.
 */
class AddToCartForm extends BuyItNowForm {

  /**
   * Constructs an AddToCartForm.
   *
   * @param string $nid
   *   The node ID.
   */
  public function __construct($nid) {
    parent::__construct($nid);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    // Base Form ID allows us to theme all add-to-cart-forms using the same
    // CSS class and twig template, and allows us to hook_form_BASE_ID_ALTER()
    // all add-to-cart-forms, rather than having to target each individual form.
    return 'uc_product_add_to_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Form ID must be unique to the product so that we may have multiple
    // add-to-cart forms on a page (e.g. in a catalog view).
    return 'uc_product_add_to_cart_form_' . $this->nid;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['node'] = array(
      '#type' => 'value',
      '#value' => $form_state->get('variant') ?: $node,
    );

    $form = parent::buildForm($form, $form_state, $node);

    if ($node->default_qty->value > 0) {
      if ($this->config('uc_product.settings')->get('add_to_cart_qty')) {
        $form['qty'] = array(
          '#type' => 'uc_quantity',
          '#title' => $this->t('Quantity'),
          '#default_value' => $node->default_qty->value,
        );
      }
      else {
        $form['qty']['#value'] = $node->default_qty->value;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $data = \Drupal::moduleHandler()->invokeAll('uc_add_to_cart_data', array($form_state->getValues()));
    $form_state->set('variant', uc_product_load_variant($form_state->getValue('nid'), $data));
  }

}
