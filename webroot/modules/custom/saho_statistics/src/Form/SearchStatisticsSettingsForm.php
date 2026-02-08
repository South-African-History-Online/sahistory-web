<?php

namespace Drupal\saho_statistics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure search statistics tracking settings.
 */
class SearchStatisticsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saho_statistics_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['saho_statistics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saho_statistics.settings');

    $form['tracking'] = [
      '#type' => 'details',
      '#title' => $this->t('Search Query Tracking'),
      '#open' => TRUE,
    ];

    $form['tracking']['track_searches'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable search query tracking'),
      '#description' => $this->t('When enabled, all search queries will be logged for analytics purposes. IP addresses are hashed and session IDs are anonymized to protect user privacy.'),
      '#default_value' => $config->get('track_searches') ?? FALSE,
    ];

    $form['tracking']['search_query_retention'] = [
      '#type' => 'number',
      '#title' => $this->t('Data retention period (days)'),
      '#description' => $this->t('Search queries older than this many days will be automatically deleted during cron runs. Default is 90 days.'),
      '#default_value' => $config->get('search_query_retention') ?? 90,
      '#min' => 1,
      '#max' => 365,
      '#states' => [
        'visible' => [
          ':input[name="track_searches"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Display current statistics.
    $database = \Drupal::database();
    $total_queries = $database->select('saho_search_queries', 's')
      ->countQuery()
      ->execute()
      ->fetchField();

    $form['tracking']['statistics'] = [
      '#type' => 'item',
      '#title' => $this->t('Current statistics'),
      '#markup' => $this->t('Total search queries logged: <strong>@count</strong>', [
        '@count' => number_format($total_queries),
      ]),
    ];

    $form['privacy'] = [
      '#type' => 'details',
      '#title' => $this->t('Privacy & Security'),
      '#open' => FALSE,
    ];

    $form['privacy']['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('This module implements privacy-focused tracking:') . '</p>'
      . '<ul>'
      . '<li>' . $this->t('IP addresses are hashed using SHA-256 with site UUID as salt (cannot be reversed)') . '</li>'
      . '<li>' . $this->t('Session IDs are anonymized (only 8 characters of hash stored)') . '</li>'
      . '<li>' . $this->t('No user agent or referrer information is collected') . '</li>'
      . '<li>' . $this->t('Data is automatically purged after the retention period') . '</li>'
      . '<li>' . $this->t('GDPR compliant - no personally identifiable information (PII) is stored') . '</li>'
      . '</ul>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('saho_statistics.settings')
      ->set('track_searches', $form_state->getValue('track_searches'))
      ->set('search_query_retention', $form_state->getValue('search_query_retention'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
