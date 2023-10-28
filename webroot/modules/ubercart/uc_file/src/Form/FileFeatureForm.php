<?php

namespace Drupal\uc_file\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Creates or edits a file feature for a product.
 */
class FileFeatureForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_file_feature_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $feature = NULL) {
    $file_config = $this->config('uc_file.settings');
    if (!is_dir($file_config->get('base_dir'))) {
      drupal_set_message($this->t('A file directory needs to be configured in <a href=":url">product settings</a> under the file download settings tab before a file can be selected.', [':url' => Url::fromRoute('uc_product.settings')->toString()]), 'warning');

      return $form;
    }

    // Rescan the file directory to populate {uc_files} with the current list
    // because files uploaded via any method other than the Upload button
    // (e.g. by FTP) won'b be in {uc_files} yet.
    uc_file_refresh();

    if (!db_query_range('SELECT 1 FROM {uc_files}', 0, 1)->fetchField()) {
      $form['file']['file_message'] = array(
        '#markup' => $this->t('You must add files at the <a href=":url">Ubercart file download administration page</a> in order to attach them to a model.', [':url' => Url::fromRoute('uc_file.downloads', [], ['query' => ['destination' => 'node/' . $node->id() . '/edit/features/file/add']])->toString()]
        ),
      );

      return $form;
    }

    // Grab all the models on this product.
    $models = uc_product_get_models($node->id());

    // Use the feature's values to fill the form, if they exist.
    if (!empty($feature)) {
      $file_product        = db_query('SELECT * FROM {uc_file_products} p LEFT JOIN {uc_files} f ON p.fid = f.fid WHERE pfid = :pfid', [':pfid' => $feature['pfid']])->fetchObject();

      $default_feature     = $feature['pfid'];

      $default_model       = $file_product->model;
      $default_filename    = $file_product->filename;
      $default_description = $file_product->description;
      $default_shippable   = $file_product->shippable;

      $download_status     = $file_product->download_limit != UC_FILE_LIMIT_SENTINEL;
      $download_value      = $download_status ? $file_product->download_limit : NULL;

      $address_status      = $file_product->address_limit != UC_FILE_LIMIT_SENTINEL;
      $address_value       = $address_status ? $file_product->address_limit : NULL;

      $time_status         = $file_product->time_granularity != UC_FILE_LIMIT_SENTINEL;
      $quantity_value      = $time_status ? $file_product->time_quantity : NULL;
      $granularity_value   = $time_status ? $file_product->time_granularity : 'never';
    }
    else {
      $default_feature   = NULL;

      $default_model       = '';
      $default_filename    = '';
      $default_description = '';
      $default_shippable   = $node->shippable->value;

      $download_status     = FALSE;
      $download_value      = NULL;

      $address_status      = FALSE;
      $address_value       = NULL;

      $time_status         = FALSE;
      $quantity_value      = NULL;
      $granularity_value   = 'never';
    }

    $form['#attached']['library'][] = 'uc_file/uc_file.styles';
    $form['nid'] = array(
      '#type' => 'value',
      '#value' => $node->id(),
    );
    $form['pfid'] = array(
      '#type' => 'value',
      '#value' => $default_feature,
    );
    $form['uc_file_model'] = array(
      '#type' => 'select',
      '#title' => $this->t('SKU'),
      '#default_value' => $default_model,
      '#description' => $this->t('This is the SKU that will need to be purchased to obtain the file download.'),
      '#options' => $models,
    );
    $form['uc_file_filename'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('File download'),
      '#default_value' => $default_filename,
      '#autocomplete_route_name' => 'uc_file.autocomplete_filename',
      '#description' => $this->t('The file that can be downloaded when product is purchased (enter a path relative to the %dir directory).', ['%dir' => $file_config->get('base_dir')]),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['uc_file_description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $default_description,
      '#maxlength' => 255,
      '#description' => $this->t('A description of the download associated with the product.'),
    );
    $form['uc_file_shippable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Shippable product'),
      '#default_value' => $default_shippable,
      '#description' => $this->t('Check if this product model/SKU file download is also associated with a shippable product.'),
    );

    $form['uc_file_limits'] = array(
      '#type' => 'fieldset',
      '#description' => $this->t('Use these options to override any global download limits set at the :url.', [':url' => Link::createFromRoute($this->t('Ubercart product settings page'), 'uc_product.settings', [], ['query' => ['destination' => 'node/' . $node->id() . '/edit/features/file/add']])->toString()]),
      '#title' => $this->t('File limitations'),
    );

    $form['uc_file_limits']['download_override'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Override download limit'),
      '#default_value' => $download_status,
    );
    $form['uc_file_limits']['download_limit_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Downloads'),
      '#default_value' => $download_value,
      '#description' => $this->t('The number of times this file can be downloaded.'),
      '#maxlength' => 4,
      '#size' => 4,
      '#states' => array(
        'visible' => array('input[name="download_override"]' => array('checked' => TRUE)),
      ),
    );

    $form['uc_file_limits']['location_override'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Override IP address limit'),
      '#default_value' => $address_status,
    );
    $form['uc_file_limits']['download_limit_addresses'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('IP addresses'),
      '#default_value' => $address_value,
      '#description' => $this->t('The number of unique IPs that a file can be downloaded from.'),
      '#maxlength' => 4,
      '#size' => 4,
      '#states' => array(
        'visible' => array('input[name="location_override"]' => array('checked' => TRUE)),
      ),
    );

    $form['uc_file_limits']['time_override'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Override time limit'),
      '#default_value' => $time_status,
    );

    $form['uc_file_limits']['download_limit_duration'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('duration')),
    );
    $form['uc_file_limits']['download_limit_duration']['duration_qty'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Time'),
      '#default_value' => $quantity_value,
      '#size' => 4,
      '#maxlength' => 4,
      '#states' => array(
        'disabled' => array('select[name="duration_granularity"]' => array('value' => 'never')),
        'visible' => array('input[name="time_override"]' => array('checked' => TRUE)),
      ),
    );
    $form['uc_file_limits']['download_limit_duration']['duration_granularity'] = array(
      '#type' => 'select',
      '#default_value' => $granularity_value,
      '#options' => array(
        'never' => $this->t('never'),
        'day' => $this->t('day(s)'),
        'week' => $this->t('week(s)'),
        'month' => $this->t('month(s)'),
        'year' => $this->t('year(s)')
      ),
      '#description' => $this->t('How long after this product has been purchased until this file download expires.'),
      '#states' => array(
        'visible' => array('input[name="time_override"]' => array('checked' => TRUE)),
      ),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save feature'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure this is actually a file we control...
    if (!db_query('SELECT fid FROM {uc_files} WHERE filename = :name', [':name' => $form_state->getValue('uc_file_filename')])->fetchField()) {
      $form_state->setErrorByName('uc_file_filename', $this->t('%file is not a valid file or directory inside file download directory.', ['%file' => $form_state->getValue('uc_file_filename')]));
    }

    // If any of our overrides are set, then we make sure they make sense.
    if ($form_state->getValue('download_override') &&
        $form_state->getValue('download_limit_number') < 0) {
      $form_state->setErrorByName('download_limit_number', $this->t('A negative download limit does not make sense. Please enter a positive integer, or leave empty for no limit.'));
    }
    if ($form_state->getValue('location_override') &&
        $form_state->getValue('download_limit_addresses') < 0) {
      $form_state->setErrorByName('download_limit_addresses', $this->t('A negative IP address limit does not make sense. Please enter a positive integer, or leave empty for no limit.'));
    }
    if ($form_state->getValue('time_override') &&
        $form_state->getValue('duration_granularity') != 'never' &&
        $form_state->getValue('duration_qty') < 1) {
      $form_state->setErrorByName('duration_qty', $this->t('You set the granularity (%gran), but you did not set how many. Please enter a positive non-zero integer.', ['%gran' => $form_state->getValue('duration_granularity') . '(s)']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the file_product object from the form values.
    $file = uc_file_get_by_name($form_state->getValue('uc_file_filename'));
    $file_product = array(
      'fid'         => $file->fid,
      'filename'    => $file->filename,
      'pfid'        => $form_state->getValue('pfid'),
      'model'       => $form_state->getValue('uc_file_model'),
      'description' => $form_state->getValue('uc_file_description'),
      'shippable'   => $form_state->getValue('uc_file_shippable'),

      // Local limitations... set them if there's an override.
      'download_limit'   => $form_state->getValue('download_limit_number') ?: UC_FILE_LIMIT_SENTINEL,
      'address_limit'    => $form_state->getValue('download_limit_addresses') ?: UC_FILE_LIMIT_SENTINEL,
      'time_granularity' => $form_state->getValue('duration_granularity') ?: UC_FILE_LIMIT_SENTINEL,
      'time_quantity'    => $form_state->getValue('duration_qty') ?: UC_FILE_LIMIT_SENTINEL,
    );

    // Build product feature descriptions.
    $file_config = $this->config('uc_file.settings');
    $description = $this->t('<strong>SKU:</strong> @sku<br />', ['@sku' => empty($file_product['model']) ? 'Any' : $file_product['model']]);
    if (is_dir($file_config->get('base_dir') . '/' . $file_product['filename'])) {
      $description .= $this->t('<strong>Directory:</strong> @dir<br />', ['@dir' => $file_product['filename']]);
    }
    else {
      $description .= $this->t('<strong>File:</strong> @file<br />', ['@file' => \Drupal::service('file_system')->basename($file_product['filename'])]);
    }
    $description .= $file_product['shippable'] ? $this->t('<strong>Shippable:</strong> Yes') : $this->t('<strong>Shippable:</strong> No');

    $data = array(
      'pfid' => $file_product['pfid'],
      'nid' => $form_state->getValue('nid'),
      'fid' => 'file',
      'description' => $description,
    );

    uc_product_feature_save($data);
    $file_product['pfid'] = $data['pfid'];
    unset($file_product['filename']);

    $key = NULL;
    if ($fpid = _uc_file_get_fpid($file_product['pfid'])) {
      $key = $fpid;
    }

    // Insert or update (if $key is already in table) uc_file_products table.
    if (empty($key)) {
      $key = db_insert('uc_file_products')
        ->fields($file_product)
        ->execute();
    }
    else {
      db_update('uc_file_products')
        ->fields($file_product)
        ->condition(['fpid' => $key])
        ->execute();
    }

    $form_state->setRedirect('uc_product.features', ['node' => $data['nid']]);
  }

}
