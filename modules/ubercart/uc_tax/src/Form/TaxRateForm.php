<?php

namespace Drupal\uc_tax\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Builds the form to edit tax rate entities.
 */
class TaxRateForm extends EntityForm {

  /**
   * The tax rate entity.
   *
   * @var \Drupal\uc_tax\TaxRateInterface
   */
  protected $entity;

  /**
   * The tax rate plugin.
   *
   * @var \Drupal\uc_tax\TaxRatePluginInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->plugin = $this->entity->getPlugin();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $definition = $this->plugin->getPluginDefinition();
    $form['type'] = array(
      '#type' => 'item',
      '#title' => $this->t('Type'),
      '#markup' => $definition['label'],
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The tax rate name shown to the customer at checkout.'),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\uc_tax\Entity\TaxRate::load',
      ),
      '#disabled' => !$this->entity->isNew(),
    );

    $form['settings'] = $this->plugin->buildConfigurationForm([], $form_state);
    $form['settings']['#tree'] = TRUE;

    $form['shippable'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Taxed products'),
      '#options' => array(
        0 => $this->t('Apply tax to any product regardless of its shippability.'),
        1 => $this->t('Apply tax to shippable products only.'),
      ),
      '#default_value' => (int) $this->entity->isForShippable(),
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
      '#default_value' => $this->entity->getProductTypes(),
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
      '#default_value' => $this->entity->getLineItemTypes(),
      '#options' => $options,
    );

    $form['display_include'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include this tax when displaying product prices.'),
      '#default_value' => $this->entity->isIncludedInPrice(),
    );

    $form['inclusion_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tax inclusion text'),
      '#description' => $this->t('This text will be displayed near the price to indicate that it includes tax.'),
      '#default_value' => $this->entity->getInclusionText(),
    );

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $this->plugin->validateConfigurationForm($form['settings'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->plugin->submitConfigurationForm($form['settings'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Modify submit button text.
    $actions['submit']['#value'] = $this->t('Save tax rate');
    // Add a cancel link to take us back to the list builder.
    $actions['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.uc_tax_rate.collection'),
    );

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    // Save machine names of product types and line item types.
    $this->entity->setProductTypes(array_filter($form_state->getValue('product_types')));
    $this->entity->setLineItemTypes(array_filter($form_state->getValue('line_item_types')));

    $status = $this->entity->save();

    // Create an edit link for the logger message.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $this->entity->toUrl())->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity.
      drupal_set_message($this->t('Tax rate %label has been updated.', ['%label' => $this->entity->label()]));
      $this->logger('uc_tax')->notice('Tax rate %label has been updated.', ['%label' => $this->entity->label(), 'link' => $edit_link]);
    }
    else {
      // If we created a new entity.
      drupal_set_message($this->t('Tax rate %label has been added.', ['%label' => $this->entity->label()]));
      $this->logger('uc_tax')->notice('Tax rate %label has been added.', ['%label' => $this->entity->label(), 'link' => $edit_link]);
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
