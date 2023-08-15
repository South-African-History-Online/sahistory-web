<?php

namespace Drupal\uc_file\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Creates or edits a file feature for a product.
 */
class UserForm extends FormBase {

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_file_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $account = NULL) {
    $this->account = $account;
    $form['file'] = array(
      '#type' => 'details',
      '#title' => $this->t('Administration'),
    );

    // Drop out early if we don't even have any files uploaded.
    if (!db_query_range('SELECT 1 FROM {uc_files}', 0, 1)->fetchField()) {
      $form['file']['file_message'] = array(
        '#prefix' => '<p>',
        '#markup' => $this->t('You must add files at the <a href=":url">Ubercart file download administration page</a> in order to attach them to a user.', [':url' => Url::fromRoute('uc_file.downloads', [], ['query' => ['destination' => 'user/' . $account->id() . '/edit']])->toString()]),
        '#suffix' => '</p>',
      );

      return $form;
    }

    // Table displaying current downloadable files and limits.
    $form['file']['download']['#theme'] = 'uc_file_hook_user_file_downloads';
    $form['file']['download']['file_download']['#tree'] = TRUE;

    $form['#attached']['library'][] = 'uc_file/uc_file.scripts';
    $form['#attached']['library'][] = 'uc_file/uc_file.styles';

    $downloadable_files = array();
    $file_downloads = db_query('SELECT * FROM {uc_file_users} ufu INNER JOIN {uc_files} uf ON ufu.fid = uf.fid WHERE ufu.uid = :uid ORDER BY uf.filename ASC', [':uid' => $account->id()]);
    $behavior = 0;
    foreach ($file_downloads as $file_download) {

      // Store a flat array so we can array_diff the ones already allowed when
      // building the list of which can be attached.
      $downloadable_files[$file_download->fid] = $file_download->filename;

      $form['file']['download']['file_download'][$file_download->fid] = array(
        'fuid'       => array('#type' => 'value', '#value' => $file_download->fuid),
        'expiration' => array('#type' => 'value', '#value' => $file_download->expiration),
        'remove'     => array('#type' => 'checkbox'),
        'filename'   => array('#markup' => $file_download->filename),
        'expires'    => array(
          '#markup' => $file_download->expiration ?
                       \Drupal::service('date.formatter')->format($file_download->expiration, 'short') :
                       $this->t('Never')
        ),
        'time_polarity' => array(
          '#type' => 'select',
          '#default_value' => '+',
          '#options' => array(
            '+' => '+',
            '-' => '-',
          ),
        ),
        'time_quantity' => array(
          '#type' => 'textfield',
          '#size' => 2,
          '#maxlength' => 2,
        ),
        'time_granularity' => array(
          '#type' => 'select',
          '#default_value' => 'day',
          '#options' => array(
            'never' => $this->t('never'),
            'day' => $this->t('day(s)'),
            'week' => $this->t('week(s)'),
            'month' => $this->t('month(s)'),
            'year' => $this->t('year(s)'),
          ),
        ),

        'downloads_in' => array('#markup' => $file_download->accessed),
        'download_limit' => array(
          '#type' => 'textfield',
          '#maxlength' => 3,
          '#size' => 3,
          '#default_value' => $file_download->download_limit ? $file_download->download_limit : NULL
        ),

        'addresses_in' => array('#markup' => count(unserialize($file_download->addresses))),
        'address_limit' => array(
          '#type' => 'textfield',
          '#maxlength' => 2,
          '#size' => 2,
          '#default_value' => $file_download->address_limit ? $file_download->address_limit : NULL
        ),
      );

      // Incrementally add behaviors.
      // @todo: _uc_file_download_table_behavior($behavior++, $file_download->fid);
      $form['#attached']['drupalSettings']['behavior'][$behavior++] = $file_download->fid;

      // Store old values for comparing to see if we actually made any changes.
      $less_reading = &$form['file']['download']['file_download'][$file_download->fid];

      $less_reading['download_limit_old'] = array('#type' => 'value', '#value' => $less_reading['download_limit']['#default_value']);
      $less_reading['address_limit_old'] = array('#type' => 'value', '#value' => $less_reading['address_limit']['#default_value']);
      $less_reading['expiration_old'] = array('#type' => 'value', '#value' => $less_reading['expiration']['#value']);
    }

    // Create the list of files able to be attached to this user.
    $available_files = array();

    $files = db_query('SELECT * FROM {uc_files} ORDER BY filename ASC');
    foreach ($files as $file) {
      if (substr($file->filename, -1) != '/' && substr($file->filename, -1) != '\\') {
        $available_files[$file->fid] = $file->filename;
      }
    }

    // Dialog for uploading new files.
    $available_files = array_diff($available_files, $downloadable_files);
    if (count($available_files)) {
      $form['file']['file_add'] = array(
        '#type' => 'select',
        '#multiple' => TRUE,
        '#size' => 6,
        '#title' => $this->t('Add file'),
        '#description' => array('#markup' => $this->t('Select a file to add as a download. Newly added files will inherit the settings at the <a href=":url">Ubercart products settings page</a>.', [':url' => Url::fromRoute('uc_product.settings')->toString()])),
        '#options' => $available_files,
        '#tree' => TRUE,
      );
    }

    $form['file']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $edit = $form_state->getValues();

    // Determine if any downloads were modified.
    if (isset($edit['file_download'])) {

      foreach ((array)$edit['file_download'] as $key => $download_modification) {
        // We don't care... it's about to be deleted.
        if ($download_modification['remove']) {
          continue;
        }

        if ($download_modification['download_limit'] < 0) {
          $form_state->setErrorByName('file_download][' . $key . '][download_limit', $this->t('A negative download limit does not make sense. Please enter a positive integer, or leave empty for no limit.'));
        }

        if ($download_modification['address_limit'] < 0) {
          $form_state->setErrorByName('file_download][' . $key . '][address_limit', $this->t('A negative address limit does not make sense. Please enter a positive integer, or leave empty for no limit.'));
        }

        // Some expirations don't need any validation...
        if ($download_modification['time_granularity'] == 'never' || !$download_modification['time_quantity']) {
          continue;
        }

        // Either use the current expiration, or if there's none,
        // start from right now.
        $new_expiration = _uc_file_expiration_date($download_modification, $download_modification['expiration']);

        if ($new_expiration <= REQUEST_TIME) {
          $form_state->setErrorByName('file_download][' . $key . '][time_quantity', $this->t('The date %date has already occurred.', ['%date' => \Drupal::service('date.formatter')->format($new_expiration, 'short')]));
        }

        if ($download_modification['time_quantity'] < 0) {
          $form_state->setErrorByName('file_download][' . $key . '][time_quantity', $this->t('A negative expiration quantity does not make sense. Use the polarity control to determine if the time should be added or subtracted.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $edit = $form_state->getValues();

    // Check out if any downloads were modified.
    if (isset($edit['file_download'])) {
      foreach ((array)$edit['file_download'] as $fid => $download_modification) {
        // Remove this user download?
        if ($download_modification['remove']) {
          uc_file_remove_user_file_by_id($this->account, $fid);
        }

        // Update the modified downloads.
        else {
          // Calculate the new expiration.
          $download_modification['expiration'] = _uc_file_expiration_date($download_modification, $download_modification['expiration']);

          // Don't touch anything if everything's the same.
          if ($download_modification['download_limit'] == $download_modification['download_limit_old'] &&
              $download_modification['address_limit'] == $download_modification['address_limit_old'] &&
              $download_modification['expiration'] == $download_modification['expiration_old']) {
            continue;
          }

          // Renew. (Explicit overwrite.)
          uc_file_user_renew($fid, $this->account, NULL, $download_modification, TRUE);
        }
      }
    }

    // Check out if any downloads were added. We pass NULL to file_user_renew,
    // because this shouldn't be associated with a random product.
    if (isset($edit['file_add'])) {
      $file_config = $this->config('uc_file.settings');
      foreach ((array) $edit['file_add'] as $fid => $data) {
        $download_modification['download_limit'] = $file_config->get('download_limit_number');
        $download_modification['address_limit'] = $file_config->get('download_limit_addresses');

        $download_modification['expiration'] = _uc_file_expiration_date(array(
          'time_polarity' => '+',
          'time_quantity' => $file_config->get('duration_qty'),
          'time_granularity' => $file_config->get('duration_granularity'),
        ), REQUEST_TIME);

        // Renew. (Explicit overwrite.)
        uc_file_user_renew($fid, $this->account, NULL, $download_modification, TRUE);
      }
    }
  }

}

