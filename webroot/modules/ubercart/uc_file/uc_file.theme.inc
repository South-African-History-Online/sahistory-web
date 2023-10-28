<?php

/**
 * @file
 * Theme functions for the uc_file module.
 */

use Drupal\Core\Render\Element;

/**
 * Themes the download table at the user account page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_file_hook_user_file_downloads(array $variables) {
  $form = $variables['form'];

  $header = array(
    array('data' => t('Remove')),
    array('data' => t('Filename')),
    array('data' => t('Expiration')),
    array('data' => t('Downloads')),
    array('data' => t('Addresses')),
  );
  $rows = array();

  foreach (Element::children($form['file_download']) as $key) {

    if (!isset($form['file_download'][$key]['addresses_in'])) {
      continue;
    }

    $file_download = &$form['file_download'][$key];

    $rows[] = array(
      'data' => array(
        array('data' => $file_download['remove']),
        array('data' => $file_download['filename']),
        array(
          'data' => array(
            '#type' => 'container',
            '#prefix' => drupal_render($file_download['expires']) . ' <br />',
            'time' => array(
              'polarity' => $file_download['time_polarity'],
              'quantity' => $file_download['time_quantity'],
              'granularity' => $file_download['time_granularity'],
            ),
          ),
          'class' => array('duration'),
        ),
        array(
          'data' => array(
            '#type' => 'container',
            '#markup' => drupal_render($file_download['downloads_in']) . '/',
            'limit' =>  $file_download['download_limit'],
          ),
          'class' => array('download-table-index'),
        ),
        array(
          'data' => array(
            '#type' => 'container',
            '#markup' => drupal_render($file_download['addresses_in']) . '/',
            'limit' => $file_download['address_limit'],
          ),
          'class' => array('download-table-index'),
        ),
      ),
      'class' => array('download-table-row'),
    );
  }

  $build = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('id' => 'download-table'),
    '#empty' => t('No files can be downloaded by this user.'),
  );

  return drupal_render($build);
}
