<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines the attribute add/edit edit form.
 */
abstract class AttributeFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_attribute_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('The name of the attribute used in administrative forms'),
      '#default_value' => '',
      '#required' => TRUE,
    );
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t("Enter a label that customers will see instead of the attribute name. Use &lt;none&gt; if you don't want a title to appear at all."),
      '#default_value' => '',
      '#maxlength' => 255,
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Help text'),
      '#description' => $this->t('<b>Optional.</b> Enter the help text that will display beneath the attribute on product add to cart forms.'),
      '#default_value' => '',
      '#maxlength' => 255,
    );
    $form['required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Make this attribute required, forcing the customer to choose an option.'),
      '#description' => $this->t('Selecting this for an attribute will disregard any default option you specify.<br />May be overridden at the product level.'),
      '#default_value' => 0,
    );
    $form['display'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display type'),
      '#description' => $this->t('This specifies how the options for this attribute will be presented.<br />May be overridden at the product level.'),
      '#options' => _uc_attribute_display_types(),
      '#default_value' => 1,
    );
    $form['ordering'] = array(
      '#type' => 'weight',
      '#delta' => 25,
      '#title' => $this->t('List position'),
      '#description' => $this->t('Multiple attributes on an add to cart form are sorted by this value and then by their name.<br />May be overridden at the product level.'),
      '#default_value' => 0,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('uc_attribute.overview'),
    );

    return $form;
  }

}
