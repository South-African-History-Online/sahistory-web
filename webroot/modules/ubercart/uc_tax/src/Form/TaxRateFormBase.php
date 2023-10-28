<?php

namespace Drupal\uc_tax\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines the tax rate add/edit form.
 */
class TaxRateFormBase extends EntityForm {

  /**
   * Returns a Url to redirect to if the current operation is cancelled.
   *
   * @return \Drupal\Core\Url
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.uc_tax_rate.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $rate = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('This name will appear to the customer when this tax is applied to an order.'),
      '#default_value' => $rate->label(),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $rate->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ),
//      '#disabled' => !$this->isNew(),
    );

    $form['rate'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Rate'),
      '#description' => $this->t('The tax rate as a percent or decimal. Examples: 6%, .06'),
      '#size' => 15,
      '#default_value' => (float) $rate->getRate() * 100.0 . '%',
      '#required' => TRUE,
    );

    $form['shippable'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Taxed products'),
      '#options' => array(
        0 => $this->t('Apply tax to any product regardless of its shippability.'),
        1 => $this->t('Apply tax to shippable products only.'),
      ),
      '#default_value' => (int) $rate->isForShippable(),
    );

    // TODO: Remove the need for a special case for product kit module.
    $options = array();
    foreach (node_type_get_names() as $type => $name) {
      if ($type != 'product_kit' && uc_product_is_product($type)) {
        $options[$type] = $name;
      }
    }
    $options['blank-line'] = $this->t('"Blank line" product');

    $form['product_types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Taxed product types'),
      '#description' => $this->t('Apply taxes to the specified product types/classes.'),
      '#default_value' => $rate->getProductTypes(),
      '#options' => $options,
    );

    $options = array();
    $definitions = \Drupal::service('plugin.manager.uc_order.line_item')->getDefinitions();
    foreach ($definitions as $id => $line_item) {
      if (!in_array($id, ['subtotal', 'tax_subtotal', 'total', 'tax_display'])) {
        $options[$id] = $line_item['title'];
      }
    }

    $form['line_item_types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Taxed line items'),
      '#description' => $this->t('Adds the checked line item types to the total before applying this tax.'),
      '#default_value' => $rate->getLineItemTypes(),
      '#options' => $options,
    );

    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#description' => $this->t('Taxes are sorted by weight and then applied to the order sequentially. This value is important when taxes need to include other tax line items.'),
      '#default_value' => $rate->getWeight(),
    );

    $form['display_include'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include this tax when displaying product prices.'),
      '#default_value' => $rate->isIncludedInPrice(),
    );

    $form['inclusion_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tax inclusion text'),
      '#description' => $this->t('This text will be displayed near the price to indicate that it includes tax.'),
      '#default_value' => $rate->getInclusionText(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
    $rate = $form_state->getValue('rate');
    $rate = trim($rate);
    // @TODO Would be nice to better validate rate, maybe with preg_match
    if (floatval($rate) < 0) {
      $form_state->setErrorByName('rate', $this->t('Rate must be a positive number. No commas and only one decimal point.'));
    }
    // Save trimmed rate back to form if it passes validation.
    $form_state->setValue('rate', $rate);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $tax_rate = $this->getEntity();

    // Save rate.
    $rate = $form_state->getValue('rate');
    if (substr($rate, -1) == '%') {
      // Rate given in percentage, so convert it to a fraction for storage.
      $tax_rate->setRate(floatval($rate) / 100.0);
    }

    // Save machine names of product types and line item types.
    $tax_rate->setProductTypes(array_filter($form_state->getValue('product_types')));
    $tax_rate->setLineItemTypes(array_filter($form_state->getValue('line_item_types')));

    // @TODO When Rules is working in D8 ..
    // Update the name of the associated conditions.
    // $conditions = rules_config_load('uc_tax_' . $form_state->getValue('id'));
    // if ($conditions) {
    //   $conditions->label = $form_state->getVolue('name');
    //   $conditions->save();
    // }

    $status = $tax_rate->save();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $tax_rate->toUrl())->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      drupal_set_message($this->t('Tax rate %label has been updated.', ['%label' => $tax_rate->label()]));
      $this->logger('uc_tax')->notice('Tax rate %label has been updated.', ['%label' => $tax_rate->label(), 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      drupal_set_message($this->t('Tax rate %label has been added.', ['%label' => $tax_rate->label()]));
      $this->logger('uc_tax')->notice('Tax rate %label has been added.', ['%label' => $tax_rate->label(), 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.uc_tax_rate.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');
    $actions['submit']['#suffix'] = Link::fromTextAndUrl($this->t('Cancel'), $this->getCancelUrl())->toString();

    return $actions;
  }

}
