<?php

namespace Drupal\basic_cart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\basic_cart\Utility;
use \Drupal\node\Entity\NodeType;

/**
 * Configure basic_cart settings for this site.
 */
class CartSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basic_cart_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'basic_cart.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('basic_cart.settings');
    $node_types = NodeType::loadMultiple();
    if (empty($node_types)) {
      return NULL;
    }

    if(trim($config->get('placeorder_button_name'))==""){  
    
                $this->config('basic_cart.settings')
      ->set('placeorder_button_name','Place Order')->save();
    }
    if($config->get('cart_button_name')==""){
     $this->config('basic_cart.settings')
      ->set('cart_button_name','Checkout')->save();
        
    }

    
    $options = array();
    foreach ($node_types as $node_type => $type) {
      if ($node_type == 'basic_cart_order' || $node_type == 'basic_cart_connect') {
        continue;
      }
      $options[$node_type] = $type->get('name');
    }

    $form['content_type'] = array(
      '#title' => t('Content type selection'),
      '#type' => 'fieldset',
      '#description' => t('Please select the content types for which you wish to have the "Add to cart" option.'),
    );

    $form['content_type']['basic_cart_content_types'] = array(
      '#title' => t('Content types'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('content_type'),
    );

    $form['content_type']['basic_cart_all_content_types'] = array(
      '#type' => 'hidden',
      '#default_value' => $config->get('content_type'),
    );

    $form['table'] = array(
      '#title' => t('Store cart data in database table'),
      '#type' => 'fieldset',
      '#description' => t('Enable cart to store the data in database instead of session. Data will persist only when user logged in'),
    );

    $form['table']['basic_cart_use_cart_table'] = array(
      '#title' => t('Persist cart data'),
      '#type' => 'checkbox',
      '#description' => t('This option will enable to persist cart data even the user is logged out and logging in again'),
      '#default_value' => $config->get('use_cart_table'),
    );

    $form['currency'] = array(
      '#title' => t('Currency and price'),
      '#type' => 'fieldset',
      '#description' => t('Please select the currency in which the prices will be calculated.'),
    );

    $form['currency']['basic_cart_currency_status'] = array(
      '#title' => t('Enable Currency'),
      '#type' => 'checkbox',
      '#description' => t('Enable Currency for your cart price,this will available only if price is enabled'),
      '#default_value' => $config->get('currency_status'),
    );

    $form['currency']['basic_cart_currency'] = array(
      '#title' => t('Currency'),
      '#type' => 'textfield',
      '#description' => t("Please choose the currency."),
      '#default_value' => $config->get('currency'),
    );

    $form['currency']['basic_cart_price_format'] = array(
      '#title' => t('Price format'),
      '#type' => 'select',
      '#options' => Utility::listPriceFormats(),
      '#description' => t("Please choose the format in which the price will be shown."),
      '#default_value' => $config->get('price_format'),
    );

    $form['currency']['basic_cart_quantity_status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable quantity'),
      '#default_value' => $config->get('quantity_status'),
      '#description' => t('Enable quantity  for your cart, if quantity not enabled you can add to a cart without quantity'),
    );

    $form['currency']['basic_cart_price_status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable price'),
      '#default_value' => $config->get('price_status'),
      '#description' => t('Enable price for your cart, if price not enabled you can add to a cart without price'),
    );

    $form['currency']['basic_cart_total_price_status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable total price'),
      '#default_value' => $config->get('total_price_status'),
      '#description' => t('Enable total price for your cart, if total price is not enabled your cart would not have total price calcutaion'),
    );

    $form['vat'] = array(
      '#title' => t('VAT'),
      '#type' => 'fieldset',
    );

    $form['vat']['basic_cart_vat_state'] = array(
      '#title' => t('Check if you want to apply the VAT tax on the total amount in the checkout process.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('vat_state'),
    );

    $form['vat']['basic_cart_vat_value'] = array(
      '#title' => t('VAT value'),
      '#type' => 'textfield',
      '#description' => t("Please enter VAT value."),
      '#field_suffix' => '%',
      '#size' => 10,
      '#default_value' => $config->get('vat_value'),
    );

    $form['order'] = array(
      '#title' => t('Basic Cart Order'),
      '#type' => 'fieldset',
    );

    $form['order']['basic_cart_order_status'] = array(
      '#title' => t('Check if you want to create order for the cart.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('order_status'),
    );

    $form['redirect'] = array(
      '#title' => t('Redirect user after adding an item to the shopping cart'),
      '#type' => 'fieldset',
    );

    $form['redirect']['basic_cart_add_to_cart_redirect'] = array(
      '#title' => t('Add to cart redirect'),
      '#type' => 'textfield',
      '#description' => t("Enter the page you wish to redirect the customer to when an item is added to the cart, or &lt;none&gt; for no redirect."),
      '#default_value' => $config->get('add_to_cart_redirect'),
    );

    $form['configure'] = array(
      '#title' => t('Configure texts'),
      '#type' => 'fieldset',
    // '#description' => t('Please configure text to be shown in your cart'),.
    );

    $form['configure']['basic_cart_cart_page_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => $config->get('cart_page_title'),
      '#description' => t('Please configure page title to be shown in your cart page'),
    );

    $form['configure']['basic_cart_empty_cart'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Empty Cart'),
      '#default_value' => $config->get('empty_cart'),
      '#description' => t('Please configure a text when your cart is empty'),
    );

    $form['configure']['basic_cart_cart_block_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Block Title'),
      '#default_value' => $config->get('cart_block_title'),
      '#description' => t('Please configure your cart block title'),
    );

    $form['configure']['basic_cart_view_cart_button'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('View cart'),
      '#default_value' => $config->get('view_cart_button'),
      '#description' => t('Please configure your text on view cart button'),
    );

    $form['configure']['basic_cart_cart_update_button'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Update cart button'),
      '#default_value' => $config->get('cart_update_button'),
      '#description' => t('Please configure your text on update cart button'),
    );

    $form['configure']['basic_cart_cart_updated_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cart updated message'),
      '#default_value' => $config->get('cart_updated_message'),
      '#description' => t('Please configure message to show after the cart updated'),
    );

    $form['configure']['basic_cart_quantity_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Quantity label'),
      '#default_value' => $config->get('quantity_label'),
      '#description' => t('Please configure your text for quantity label,this will available only if quantity is enabled'),
    );

    $form['configure']['basic_cart_price_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Price label'),
      '#default_value' => $config->get('price_label'),
      '#description' => t('Please configure your text for price label,this will available only if price is enabled'),
    );

    $form['configure']['basic_cart_total_price_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Total price label'),
      '#default_value' => $config->get('total_price_label'),
      '#description' => t('Please configure your text for total price label,this will available only if total price is enabled'),
    );

    $form['configure']['basic_cart_add_to_cart_button'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Add to Cart'),
      '#default_value' => $config->get('add_to_cart_button'),
      '#description' => t('Please configure your text on update cart button'),
    );

    $form['configure']['basic_cart_added_to_cart_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Added to Cart'),
      '#default_value' => $config->get('added_to_cart_message'),
      '#description' => t('Please configure your text on to appear after the entity is added to cart'),
    );

    $form['configure']['basic_cart_cart_button_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Basic Cart Button Name'),
      '#default_value' => $config->get('cart_button_name'),
      '#description' => t('Please configure your text on to appear On Checkout Form'),
    );

    $form['configure']['basic_cart_placeorder_button_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Basic Cart Placeorder Button Name'),
      '#default_value' => $config->get('placeorder_button_name'),
      '#description' => t('Please configure your text on to appear On Create Order Form'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('basic_cart.settings')
      ->set('cart_page_title', $form_state->getValue('basic_cart_cart_page_title'))
      ->set('empty_cart', $form_state->getValue('basic_cart_empty_cart'))
      ->set('cart_block_title', $form_state->getValue('basic_cart_cart_block_title'))
      ->set('view_cart_button', $form_state->getValue('basic_cart_view_cart_button'))
      ->set('cart_update_button', $form_state->getValue('basic_cart_cart_update_button'))
      ->set('cart_updated_message', $form_state->getValue('basic_cart_cart_updated_message'))
      ->set('quantity_status', $form_state->getValue('basic_cart_quantity_status'))
      ->set('quantity_label', $form_state->getValue('basic_cart_quantity_label'))
      ->set('price_status', $form_state->getValue('basic_cart_price_status'))
      ->set('price_label', $form_state->getValue('basic_cart_price_label'))
      ->set('price_format', $form_state->getValue('basic_cart_price_format'))
      ->set('total_price_status', $form_state->getValue('basic_cart_total_price_status'))
      ->set('total_price_label', $form_state->getValue('basic_cart_total_price_label'))
      ->set('currency_status', $form_state->getValue('basic_cart_currency_status'))
      ->set('currency', $form_state->getValue('basic_cart_currency'))
      ->set('vat_state', $form_state->getValue('basic_cart_vat_state'))
      ->set('vat_value', $form_state->getValue('basic_cart_vat_value'))
      ->set('add_to_cart_button', $form_state->getValue('basic_cart_add_to_cart_button'))
      ->set('added_to_cart_message', $form_state->getValue('basic_cart_added_to_cart_message'))
      ->set('add_to_cart_redirect', $form_state->getValue('basic_cart_add_to_cart_redirect'))
      ->set('content_type', $form_state->getValue('basic_cart_content_types'))
      ->set('order_status', $form_state->getValue('basic_cart_order_status'))
      ->set('use_cart_table', $form_state->getValue('basic_cart_use_cart_table'))
      ->set('cart_button_name', $form_state->getValue('basic_cart_cart_button_name'))
      ->set('placeorder_button_name', $form_state->getValue('basic_cart_placeorder_button_name'))
      ->save();

    $no_content_types = TRUE;

    foreach ($form_state->getValue('basic_cart_content_types') as $key => $value) {
      if (!empty($value)) {
        $no_content_types = FALSE;
      }
    }

    if ($no_content_types) {
      Utility::removeFields();
    }
    else {
      Utility::createFields();
      Utility::orderConnectFields();
    }

    parent::submitForm($form, $form_state);
  }

}
