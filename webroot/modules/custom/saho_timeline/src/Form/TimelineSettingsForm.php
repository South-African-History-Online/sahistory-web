<?php

namespace Drupal\saho_timeline\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for SAHO Timeline settings.
 */
class TimelineSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['saho_timeline.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saho_timeline_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saho_timeline.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    ];

    $form['general']['default_display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Display Mode'),
      '#options' => [
        'default' => $this->t('Default Timeline'),
        'compact' => $this->t('Compact View'),
        'expanded' => $this->t('Expanded View'),
        'horizontal' => $this->t('Horizontal Timeline'),
      ],
      '#default_value' => $config->get('default_display_mode') ?? 'default',
      '#description' => $this->t('The default display mode for timeline views.'),
    ];

    $form['general']['items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Items Per Page'),
      '#default_value' => $config->get('items_per_page') ?? 20,
      '#min' => 5,
      '#max' => 100,
      '#description' => $this->t('Number of events to display per page or infinite scroll load.'),
    ];

    $form['general']['default_grouping'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Event Grouping'),
      '#options' => [
        'none' => $this->t('No Grouping'),
        'year' => $this->t('By Year'),
        'decade' => $this->t('By Decade'),
        'century' => $this->t('By Century'),
      ],
      '#default_value' => $config->get('default_grouping') ?? 'decade',
      '#description' => $this->t('How to group timeline events by default.'),
    ];

    $form['general']['enable_infinite_scroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Infinite Scroll'),
      '#default_value' => $config->get('enable_infinite_scroll') ?? TRUE,
      '#description' => $this->t('Enable infinite scroll for timeline views.'),
    ];

    $form['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Search Settings'),
      '#open' => TRUE,
    ];

    $form['search']['enable_fuzzy_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Fuzzy Search'),
      '#default_value' => $config->get('enable_fuzzy_search') ?? TRUE,
      '#description' => $this->t('Allow spelling variations and typos in search queries.'),
    ];

    $form['search']['search_debounce'] = [
      '#type' => 'number',
      '#title' => $this->t('Search Debounce (ms)'),
      '#default_value' => $config->get('search_debounce') ?? 500,
      '#min' => 100,
      '#max' => 2000,
      '#description' => $this->t('Delay in milliseconds before triggering live search.'),
    ];

    $form['search']['min_search_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Search Length'),
      '#default_value' => $config->get('min_search_length') ?? 3,
      '#min' => 1,
      '#max' => 10,
      '#description' => $this->t('Minimum number of characters required for search.'),
    ];

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Settings'),
      '#open' => TRUE,
    ];

    $form['display']['show_event_images'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Event Images'),
      '#default_value' => $config->get('show_event_images') ?? TRUE,
      '#description' => $this->t('Display images with timeline events.'),
    ];

    $form['display']['event_excerpt_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Excerpt Length'),
      '#default_value' => $config->get('event_excerpt_length') ?? 200,
      '#min' => 50,
      '#max' => 500,
      '#description' => $this->t('Number of characters to show in event excerpts.'),
    ];

    $form['display']['enable_animations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Animations'),
      '#default_value' => $config->get('enable_animations') ?? TRUE,
      '#description' => $this->t('Enable scroll animations and transitions.'),
    ];

    $form['display']['show_period_counts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Period Event Counts'),
      '#default_value' => $config->get('show_period_counts') ?? TRUE,
      '#description' => $this->t('Display the number of events in each period.'),
    ];

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter Settings'),
      '#open' => TRUE,
    ];

    $form['filters']['show_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Filters by Default'),
      '#default_value' => $config->get('show_filters') ?? TRUE,
      '#description' => $this->t('Display filter options on timeline pages.'),
    ];

    $form['filters']['enable_location_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Location Filter'),
      '#default_value' => $config->get('enable_location_filter') ?? TRUE,
      '#description' => $this->t('Allow filtering by geographical location.'),
    ];

    $form['filters']['enable_theme_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Theme Filter'),
      '#default_value' => $config->get('enable_theme_filter') ?? TRUE,
      '#description' => $this->t('Allow filtering by themes and topics.'),
    ];

    $form['filters']['enable_date_range'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Custom Date Range'),
      '#default_value' => $config->get('enable_date_range') ?? TRUE,
      '#description' => $this->t('Allow users to specify custom date ranges.'),
    ];

    $form['integration'] = [
      '#type' => 'details',
      '#title' => $this->t('Integration Settings'),
      '#open' => FALSE,
    ];

    $form['integration']['include_tdih_events'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include TDIH Events'),
      '#default_value' => $config->get('include_tdih_events') ?? TRUE,
      '#description' => $this->t('Include "This Day in History" events in timeline.'),
    ];

    $form['integration']['parse_html_timelines'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Parse HTML Timeline Content'),
      '#default_value' => $config->get('parse_html_timelines') ?? TRUE,
      '#description' => $this->t('Extract timeline data from HTML content in articles.'),
    ];

    $form['integration']['enable_solr'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Solr for Search'),
      '#default_value' => $config->get('enable_solr') ?? FALSE,
      '#description' => $this->t('Use Apache Solr for enhanced search capabilities.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('saho_timeline.settings');
    
    // General settings.
    $config->set('default_display_mode', $form_state->getValue('default_display_mode'));
    $config->set('items_per_page', $form_state->getValue('items_per_page'));
    $config->set('default_grouping', $form_state->getValue('default_grouping'));
    $config->set('enable_infinite_scroll', $form_state->getValue('enable_infinite_scroll'));
    
    // Search settings.
    $config->set('enable_fuzzy_search', $form_state->getValue('enable_fuzzy_search'));
    $config->set('search_debounce', $form_state->getValue('search_debounce'));
    $config->set('min_search_length', $form_state->getValue('min_search_length'));
    
    // Display settings.
    $config->set('show_event_images', $form_state->getValue('show_event_images'));
    $config->set('event_excerpt_length', $form_state->getValue('event_excerpt_length'));
    $config->set('enable_animations', $form_state->getValue('enable_animations'));
    $config->set('show_period_counts', $form_state->getValue('show_period_counts'));
    
    // Filter settings.
    $config->set('show_filters', $form_state->getValue('show_filters'));
    $config->set('enable_location_filter', $form_state->getValue('enable_location_filter'));
    $config->set('enable_theme_filter', $form_state->getValue('enable_theme_filter'));
    $config->set('enable_date_range', $form_state->getValue('enable_date_range'));
    
    // Integration settings.
    $config->set('include_tdih_events', $form_state->getValue('include_tdih_events'));
    $config->set('parse_html_timelines', $form_state->getValue('parse_html_timelines'));
    $config->set('enable_solr', $form_state->getValue('enable_solr'));
    
    $config->save();
    
    parent::submitForm($form, $form_state);
  }
}