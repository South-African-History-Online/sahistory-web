<?php

namespace Drupal\saho_timeline\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\saho_timeline\Service\TimelineFilterService;
use Drupal\saho_timeline\Service\TimelineEventService;

/**
 * Advanced search form for SAHO content.
 */
class AdvancedSearchForm extends FormBase {

  /**
   * The timeline filter service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineFilterService
   */
  protected $timelineFilterService;

  /**
   * The timeline event service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineEventService
   */
  protected $timelineEventService;

  /**
   * Constructs a new AdvancedSearchForm object.
   *
   * @param \Drupal\saho_timeline\Service\TimelineFilterService $timeline_filter_service
   *   The timeline filter service.
   * @param \Drupal\saho_timeline\Service\TimelineEventService $timeline_event_service
   *   The timeline event service.
   */
  public function __construct(TimelineFilterService $timeline_filter_service, TimelineEventService $timeline_event_service) {
    $this->timelineFilterService = $timeline_filter_service;
    $this->timelineEventService = $timeline_event_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_timeline.filter_service'),
      $container->get('saho_timeline.event_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saho_timeline_advanced_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $active_filters = $this->timelineFilterService->getActiveFilters();
    $available_filters = $this->timelineFilterService->getAvailableFilters();

    // Main search container.
    $form['#attributes']['class'][] = 'saho-advanced-search-form';
    
    // Search keywords.
    $form['keywords'] = [
      '#type' => 'search',
      '#title' => $this->t('Search Keywords'),
      '#placeholder' => $this->t('Search for people, places, events, topics...'),
      '#default_value' => $active_filters['keywords'] ?? '',
      '#attributes' => [
        'class' => ['form-control', 'search-keywords'],
        'autocomplete' => 'off',
      ],
    ];

    // Advanced filters wrapper.
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['advanced-filters']],
      '#tree' => TRUE,
    ];

    // Content type filter.
    $form['filters']['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => $available_filters['content_type']['options'],
      '#default_value' => $active_filters['content_type'] ?? 'all',
      '#attributes' => ['class' => ['form-select']],
    ];

    // Time period filter.
    $form['filters']['time_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Time Period'),
      '#options' => $available_filters['time_period']['options'],
      '#default_value' => $active_filters['time_period'] ?? 'all',
      '#attributes' => ['class' => ['form-select']],
    ];

    // Custom date range.
    $form['filters']['date_range'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Date Range'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['filters']['date_range']['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#default_value' => $active_filters['start_date'] ?? '',
    ];

    $form['filters']['date_range']['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
      '#default_value' => $active_filters['end_date'] ?? '',
    ];

    // Geographical location filter.
    $form['filters']['geographical_location'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Geographical Location'),
      '#options' => $available_filters['geographical_location']['options'],
      '#default_value' => $active_filters['geographical_location'] ?? [],
    ];

    // Themes filter.
    $form['filters']['themes'] = [
      '#type' => 'select',
      '#title' => $this->t('Themes'),
      '#options' => $available_filters['themes']['options'],
      '#multiple' => TRUE,
      '#default_value' => $active_filters['themes'] ?? [],
      '#attributes' => [
        'class' => ['form-select'],
        'data-placeholder' => $this->t('Select themes...'),
      ],
    ];

    // Categories filter.
    if (!empty($available_filters['categories']['options'])) {
      $form['filters']['categories'] = [
        '#type' => 'select',
        '#title' => $this->t('Categories'),
        '#options' => $available_filters['categories']['options'],
        '#multiple' => TRUE,
        '#default_value' => $active_filters['categories'] ?? [],
        '#attributes' => [
          'class' => ['form-select'],
          'data-placeholder' => $this->t('Select categories...'),
        ],
      ];
    }

    // Sort options.
    $form['sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort By'),
      '#options' => [
        'relevance' => $this->t('Relevance'),
        'date_asc' => $this->t('Date (Oldest First)'),
        'date_desc' => $this->t('Date (Newest First)'),
        'title' => $this->t('Title (A-Z)'),
      ],
      '#default_value' => $active_filters['sort'] ?? 'relevance',
      '#attributes' => ['class' => ['form-select']],
    ];

    // Fuzzy search option.
    $form['fuzzy_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include similar spellings and variations'),
      '#default_value' => $active_filters['fuzzy_search'] ?? TRUE,
      '#description' => $this->t('Find results even with minor spelling differences.'),
    ];

    // Actions.
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['search-actions']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => ['class' => ['btn', 'btn-primary']],
    ];

    $form['actions']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Filters'),
      '#submit' => ['::clearFilters'],
      '#attributes' => ['class' => ['btn', 'btn-secondary']],
      '#limit_validation_errors' => [],
    ];

    // Add AJAX behaviors.
    $form['#attached']['library'][] = 'saho_timeline/timeline-interactive';
    $form['#attached']['drupalSettings']['sahoTimeline'] = [
      'apiEndpoint' => '/api/timeline/events',
      'activeFilters' => $active_filters,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    
    // Build query parameters from form values.
    $query_params = [];
    
    if (!empty($values['keywords'])) {
      $query_params['keywords'] = $values['keywords'];
    }
    
    if (!empty($values['sort'])) {
      $query_params['sort'] = $values['sort'];
    }
    
    if (!empty($values['fuzzy_search'])) {
      $query_params['fuzzy_search'] = 1;
    }
    
    // Add filter values.
    foreach ($values['filters'] as $key => $value) {
      if ($key === 'date_range') {
        if (!empty($value['start_date'])) {
          $query_params['start_date'] = $value['start_date'];
        }
        if (!empty($value['end_date'])) {
          $query_params['end_date'] = $value['end_date'];
        }
      }
      elseif (!empty($value) && $value !== 'all') {
        if (is_array($value)) {
          $value = array_filter($value);
          if (!empty($value)) {
            $query_params[$key] = $value;
          }
        }
        else {
          $query_params[$key] = $value;
        }
      }
    }
    
    // Redirect to timeline page with search parameters.
    $form_state->setRedirect('saho_timeline.main', [], [
      'query' => $query_params,
    ]);
  }

  /**
   * Clear all filters.
   */
  public function clearFilters(array &$form, FormStateInterface $form_state) {
    // Redirect to timeline page without parameters.
    $form_state->setRedirect('saho_timeline.main');
  }
}