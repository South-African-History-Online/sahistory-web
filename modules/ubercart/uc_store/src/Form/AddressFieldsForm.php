<?php

namespace Drupal\uc_store\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure address field settings for this store.
 */
class AddressFieldsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_store_address_fields_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_store.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uc_store.settings')
      ->get('address_fields');

    $form['fields'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Field'), $this->t('Required'), $this->t('List position')),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'uc-store-address-fields-weight',
        ),
      ),
    );

    $fields = array(
      'first_name' => $this->t('First name'),
      'last_name' => $this->t('Last name'),
      'company' => $this->t('Company'),
      'street1' => $this->t('Street address 1'),
      'street2' => $this->t('Street address 2'),
      'city' => $this->t('City'),
      'zone' => $this->t('State/Province'),
      'country' => $this->t('Country'),
      'postal_code' => $this->t('Postal code'),
      'phone' => $this->t('Phone number'),
    );

    foreach ($fields as $field => $label) {
      $form['fields'][$field]['#attributes']['class'][] = 'draggable';
      $form['fields'][$field]['#weight'] = $config[$field]['weight'];
      $form['fields'][$field]['status'] = array(
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => $config[$field]['status'],
      );
      $form['fields'][$field]['required'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('@title is required', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#default_value' => $config[$field]['required'],
      );
      $form['fields'][$field]['weight'] = array(
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#default_value' => $config[$field]['weight'],
        '#attributes' => array('class' => array('uc-store-address-fields-weight')),
      );
    }
    uasort($form['fields'], 'Drupal\Component\Utility\SortArray::sortByWeightProperty');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('uc_store.settings')
      ->set('address_fields', $form_state->getValue('fields'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
