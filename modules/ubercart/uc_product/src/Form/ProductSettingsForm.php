<?php

namespace Drupal\uc_product\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure product settings for this site.
 */
class ProductSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_product_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_product.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uc_product.settings');

    $form['product-settings'] = array('#type' => 'vertical_tabs');

    $form['product'] = array(
      '#type' => 'details',
      '#title' => $this->t('Product settings'),
      '#group' => 'product-settings',
      '#weight' => -10,
    );

    $form['product']['empty'] = array(
      '#markup' => $this->t('There are currently no settings choices for your products. When enabled, the Cart module and other modules that provide product features (such as Role assignment and File downloads) will add settings choices here.'),
    );

    $form['#submit'][] = array($this, 'submitForm');

    if (\Drupal::moduleHandler()->moduleExists('uc_cart')) {
      unset($form['product']['empty']);
      $form['product']['uc_product_add_to_cart_qty'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Display an optional quantity field in the <em>Add to Cart</em> form.'),
        '#default_value' => $config->get('add_to_cart_qty'),
      );
      $form['product']['uc_product_update_node_view'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Update product display based on customer selections'),
        '#default_value' => $config->get('update_node_view'),
        '#description' => $this->t('Check this box to dynamically update the display of product information such as display-price or weight based on customer input on the add-to-cart form (e.g. selecting a particular attribute option).'),
      );
    }

    foreach (\Drupal::moduleHandler()->invokeAll('uc_product_feature') as $feature) {
      unset($form['product']['empty']);
      if (isset($feature['settings']) && class_exists($feature['settings'])) {
        $form[$feature['id']] = array(
          '#type' => 'details',
          '#title' => $this->t('@feature settings', ['@feature' => $feature['title']]),
          '#group' => 'product-settings',
        );
        $form[$feature['id']][] = \Drupal::formBuilder()->getForm($feature['settings']);
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('uc_product.settings')
      ->set('add_to_cart_qty', $form_state->getValue('uc_product_add_to_cart_qty'))
      ->set('update_node_view', $form_state->getValue('uc_product_update_node_view'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
