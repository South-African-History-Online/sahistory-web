<?php

namespace Drupal\simple_gse_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a settings form for administering Simple CSE search.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_gse_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['simple_gse_search.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_gse_search.settings');
    $form['cx'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google Custom Search Engine ID'),
      '#description' => $this->t('Get your custom search engine ID from <a href=":url" target="_blank">https://www.google.com/cse</a>.', [
        ':url' => 'https://www.google.com/cse',
      ]),
      '#default_value' => $config->get('cx'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $gse_settings = $this->config('simple_gse_search.settings');

    $gse_settings->set('cx', $form_state->getValue('cx'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
