<?php

namespace Drupal\uc_file\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Grants roles upon accepted payment of products.
 *
 * The uc_role module will grant specified roles upon purchase of specified
 * products. Granted roles can be set to have a expiration date. Users can also
 * be notified of the roles they are granted and when the roles will
 * expire/need to be renewed/etc.
 */
class FeatureSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_file_feature_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_file.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $file_config = $this->config('uc_file.settings');
    $form['#attached']['library'][] = 'uc_file/uc_file.styles';
    $form['base_dir'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Files path'),
      '#description' => $this->t('The absolute path (or relative to Drupal root) where files used for file downloads are located. For security reasons, it is recommended to choose a path outside the web root.'),
      '#default_value' => $file_config->get('base_dir'),
      '#required' => TRUE,
    );
    $form['duplicate_warning'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Warn about purchasing duplicate files'),
      '#description' => $this->t('If a customer attempts to purchase a product containing a file download, warn them and notify them that the download limits will be added onto their current limits.'),
      '#default_value' => $file_config->get('duplicate_warning'),
    );
    $form['download_limit'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Default download limits'),
    );
    $form['download_limit']['download_limit_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Downloads'),
      '#description' => $this->t('The number of times a file can be downloaded. Leave empty to set no limit.'),
      '#default_value' => $file_config->get('download_limit_number'),
      '#maxlength' => 4,
      '#size' => 4,
    );
    $form['download_limit']['download_limit_addresses'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('IP addresses'),
      '#description' => $this->t('The number of unique IPs that a file can be downloaded from. Leave empty to set no limit.'),
      '#default_value' => $file_config->get('download_limit_addresses'),
      '#maxlength' => 4,
      '#size' => 4,
    );

    $form['download_limit']['download_limit_duration'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('duration')),
    );
    $form['download_limit']['download_limit_duration']['duration_qty'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Time'),
      '#default_value' => $file_config->get('duration_qty'),
      '#size' => 4,
      '#maxlength' => 4,
      '#states' => array(
        'disabled' => array('select[name="duration_granularity"]' => array('value' => 'never')),
      ),
    );
    $form['download_limit']['download_limit_duration']['duration_granularity'] = array(
      '#type' => 'select',
      '#options' => array(
        'never' => $this->t('never'),
        'day' => $this->t('day(s)'),
        'week' => $this->t('week(s)'),
        'month' => $this->t('month(s)'),
        'year' => $this->t('year(s)')
      ),
      '#default_value' => $file_config->get('duration_granularity'),
      '#description' => $this->t('How long after a product has been purchased until its file download expires.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Make sure our base directory is valid.
    if (!$form_state->isValueEmpty('base_dir') && !is_dir($form_state->getValue('base_dir'))) {
      $form_state->setErrorByName('base_dir', $this->t('%dir is not a valid file or directory', ['%dir' => $form_state->getValue('base_dir')]));
    }

    // If the user selected a granularity, let's make sure they
    // also selected a duration.
    if ($form_state->getValue('duration_granularity') != 'never' &&
        $form_state->getValue('duration_qty') < 1) {
      $form_state->setErrorByName('duration_qty', $this->t('You set the granularity (%gran), but you did not set how many. Please enter a positive non-zero integer.', ['%gran' => $form_state->getValue('duration_granularity') . '(s)']));
    }

    // Make sure the download limit makes sense.
    if ($form_state->getValue('download_limit_number') < 0) {
      $form_state->setErrorByName('download_limit_number', $this->t('A negative download limit does not make sense. Please enter a positive integer, or leave empty for no limit.'));
    }

    // Make sure the address limit makes sense.
    if ($form_state->getValue('download_limit_addresses') < 0) {
      $form_state->setErrorByName('download_limit_addresses', $this->t('A negative IP address limit does not make sense. Please enter a positive integer, or leave empty for no limit.'));
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No directory now; truncate the file list.
    if ($form_state->isValueEmpty('base_dir')) {
      uc_file_empty();
    }
    // Refresh file list since the directory changed.
    else {
      uc_file_refresh();
    }

    $file_config = $this->config('uc_file.settings');
    $file_config
      ->set('base_dir', $form_state->getValue('base_dir'))
      ->set('duplicate_warning', $form_state->getValue('duplicate_warning'))
      ->set('download_limit_number', $form_state->getValue('download_limit_number'))
      ->set('download_limit_addresses', $form_state->getValue('download_limit_addresses'))
      ->set('duration_qty', $form_state->getValue('duration_qty'))
      ->set('duration_granularity', $form_state->getValue('duration_granularity'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
