<?php

namespace Drupal\saho_timeline\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for managing timeline filters and facets.
 */
class TimelineFilterService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a TimelineFilterService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * Get available filters for the timeline.
   *
   * @return array
   *   Array of available filters with options.
   */
  public function getAvailableFilters() {
    return [
      'content_type' => [
        'label' => t('Content Type'),
        'options' => $this->getContentTypeOptions(),
        'multiple' => FALSE,
      ],
      'time_period' => [
        'label' => t('Time Period'),
        'options' => $this->getTimePeriodOptions(),
        'multiple' => FALSE,
      ],
      'geographical_location' => [
        'label' => t('Location'),
        'options' => $this->getGeographicalOptions(),
        'multiple' => TRUE,
      ],
      'themes' => [
        'label' => t('Themes'),
        'options' => $this->getThemeOptions(),
        'multiple' => TRUE,
      ],
      'categories' => [
        'label' => t('Categories'),
        'options' => $this->getCategoryOptions(),
        'multiple' => TRUE,
      ],
    ];
  }

  /**
   * Get active filters from the current request.
   *
   * @return array
   *   Array of active filter values.
   */
  public function getActiveFilters() {
    $request = $this->requestStack->getCurrentRequest();
    $filters = [];
    
    // Single-value parameters.
    $single_params = [
      'content_type',
      'time_period', 
      'keywords',
      'start_date',
      'end_date',
      'sort',
      'fuzzy_search',
    ];
    
    foreach ($single_params as $param) {
      $value = $request->query->get($param);
      if (!empty($value)) {
        // Sanitize single values.
        if (is_string($value)) {
          $filters[$param] = strip_tags(trim($value));
        }
        elseif (is_numeric($value)) {
          $filters[$param] = $value;
        }
      }
    }
    
    // Multi-value parameters (arrays).
    $array_params = [
      'geographical_location',
      'themes',
      'categories',
    ];
    
    foreach ($array_params as $param) {
      $value = $request->query->get($param);
      if (!empty($value)) {
        if (is_array($value)) {
          // Handle array values - sanitize each element.
          $sanitized = [];
          foreach ($value as $key => $val) {
            if (is_string($val) && !empty(trim($val))) {
              $sanitized[] = strip_tags(trim($val));
            }
          }
          if (!empty($sanitized)) {
            $filters[$param] = $sanitized;
          }
        }
        elseif (is_string($value) && !empty(trim($value))) {
          // Single string value, convert to array.
          $filters[$param] = [strip_tags(trim($value))];
        }
      }
    }
    
    return $filters;
  }

  /**
   * Get content type options.
   *
   * @return array
   *   Array of content type options.
   */
  protected function getContentTypeOptions() {
    return [
      'all' => t('All Types'),
      'event' => t('Events'),
      'biography' => t('Biographies'),
      'article' => t('Articles'),
      'topic' => t('Topics'),
      'place' => t('Places'),
      'archive' => t('Archives'),
      'multimedia' => t('Multimedia'),
    ];
  }

  /**
   * Get time period options.
   *
   * @return array
   *   Array of time period options.
   */
  protected function getTimePeriodOptions() {
    return [
      'all' => t('All Periods'),
      'pre-1500' => t('Pre-1500'),
      '1500-1650' => t('1500-1650'),
      '1650-1800' => t('1650-1800'),
      '1800-1900' => t('1800-1900'),
      '1900-1950' => t('1900-1950'),
      '1950-1990' => t('1950-1990 (Apartheid Era)'),
      '1990-2025' => t('1990-Present (Democratic Era)'),
    ];
  }

  /**
   * Get geographical location options.
   *
   * @return array
   *   Array of geographical options.
   */
  protected function getGeographicalOptions() {
    return [
      'eastern-cape' => t('Eastern Cape'),
      'free-state' => t('Free State'),
      'gauteng' => t('Gauteng'),
      'kwazulu-natal' => t('KwaZulu-Natal'),
      'limpopo' => t('Limpopo'),
      'mpumalanga' => t('Mpumalanga'),
      'northern-cape' => t('Northern Cape'),
      'north-west' => t('North West'),
      'western-cape' => t('Western Cape'),
      'southern-africa' => t('Southern Africa Region'),
      'africa' => t('Africa'),
      'international' => t('International'),
    ];
  }

  /**
   * Get theme options.
   *
   * @return array
   *   Array of theme options.
   */
  protected function getThemeOptions() {
    return [
      'liberation-struggle' => t('Liberation Struggle'),
      'apartheid' => t('Apartheid'),
      'pre-colonial' => t('Pre-Colonial History'),
      'colonial' => t('Colonial Period'),
      'cultural-heritage' => t('Cultural Heritage'),
      'womens-history' => t('Women\'s History'),
      'youth-activism' => t('Youth Activism'),
      'labour-history' => t('Labour History'),
      'education' => t('Education'),
      'arts-culture' => t('Arts & Culture'),
      'sports' => t('Sports'),
      'science-technology' => t('Science & Technology'),
      'economy' => t('Economy'),
      'politics' => t('Politics'),
      'religion' => t('Religion'),
      'military' => t('Military History'),
    ];
  }

  /**
   * Get category options from taxonomy.
   *
   * @return array
   *   Array of category options.
   */
  protected function getCategoryOptions() {
    $options = [];
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    
    // Load terms from relevant vocabularies.
    $vocabularies = ['categories', 'tags', 'topics'];
    
    foreach ($vocabularies as $vid) {
      try {
        $terms = $term_storage->loadTree($vid, 0, 1);
        foreach ($terms as $term) {
          $options[$term->tid] = $term->name;
        }
      }
      catch (\Exception $e) {
        // Vocabulary might not exist.
        continue;
      }
    }
    
    return $options;
  }

  /**
   * Build facet counts for current result set.
   *
   * @param array $results
   *   Array of search results.
   *
   * @return array
   *   Array of facet counts.
   */
  public function buildFacetCounts(array $results) {
    $facets = [
      'content_type' => [],
      'time_period' => [],
      'geographical_location' => [],
      'themes' => [],
    ];
    
    foreach ($results as $result) {
      // Count content types.
      if (method_exists($result, 'bundle')) {
        $type = $result->bundle();
        if (!isset($facets['content_type'][$type])) {
          $facets['content_type'][$type] = 0;
        }
        $facets['content_type'][$type]++;
      }
      
      // Count time periods based on date.
      if (method_exists($result, 'hasField') && $result->hasField('field_this_day_in_history_3') && !$result->get('field_this_day_in_history_3')->isEmpty()) {
        $period = $this->calculateTimePeriod($result);
        if ($period && !isset($facets['time_period'][$period])) {
          $facets['time_period'][$period] = 0;
        }
        if ($period) {
          $facets['time_period'][$period]++;
        }
      }
      
      // Count geographical locations.
      if (method_exists($result, 'hasField') && $result->hasField('field_location') && !$result->get('field_location')->isEmpty()) {
        foreach ($result->get('field_location') as $location) {
          if ($location->entity) {
            $loc_key = $location->entity->id();
            if (!isset($facets['geographical_location'][$loc_key])) {
              $facets['geographical_location'][$loc_key] = 0;
            }
            $facets['geographical_location'][$loc_key]++;
          }
        }
      }
      
      // Count themes.
      if (method_exists($result, 'hasField') && $result->hasField('field_themes') && !$result->get('field_themes')->isEmpty()) {
        foreach ($result->get('field_themes') as $theme) {
          if ($theme->entity) {
            $theme_key = $theme->entity->id();
            if (!isset($facets['themes'][$theme_key])) {
              $facets['themes'][$theme_key] = 0;
            }
            $facets['themes'][$theme_key]++;
          }
        }
      }
    }
    
    return $facets;
  }

  /**
   * Calculate time period for a result.
   *
   * @param object $result
   *   The result object.
   *
   * @return string|null
   *   The time period key or NULL.
   */
  protected function calculateTimePeriod($result) {
    $date = NULL;
    
    if (method_exists($result, 'hasField') && $result->hasField('field_this_day_in_history_3') && !$result->get('field_this_day_in_history_3')->isEmpty()) {
      $date = $result->get('field_this_day_in_history_3')->value;
    }
    
    if (!$date) {
      return NULL;
    }
    
    $year = (int) substr($date, 0, 4);
    
    if ($year < 1500) return 'pre-1500';
    if ($year <= 1650) return '1500-1650';
    if ($year <= 1800) return '1650-1800';
    if ($year <= 1900) return '1800-1900';
    if ($year <= 1950) return '1900-1950';
    if ($year <= 1990) return '1950-1990';
    
    return '1990-2025';
  }

  /**
   * Apply filters to a query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query.
   * @param array $filters
   *   Array of filters to apply.
   */
  public function applyFiltersToQuery($query, array $filters) {
    foreach ($filters as $key => $value) {
      switch ($key) {
        case 'content_type':
          if ($value !== 'all') {
            $query->condition('type', $value);
          }
          break;
          
        case 'time_period':
          if ($value !== 'all') {
            $this->applyTimePeriodFilter($query, $value);
          }
          break;
          
        case 'geographical_location':
          if (is_array($value)) {
            $query->condition('field_location', $value, 'IN');
          }
          break;
          
        case 'themes':
          if (is_array($value)) {
            $query->condition('field_themes', $value, 'IN');
          }
          break;
          
        case 'categories':
          if (is_array($value)) {
            $or_group = $query->orConditionGroup();
            $or_group->condition('field_tags', $value, 'IN');
            $or_group->condition('field_categories', $value, 'IN');
            $query->condition($or_group);
          }
          break;
          
        case 'keywords':
          if (!empty($value)) {
            $or_group = $query->orConditionGroup();
            $or_group->condition('title', $value, 'CONTAINS');
            $or_group->condition('body', $value, 'CONTAINS');
            $query->condition($or_group);
          }
          break;
          
        case 'start_date':
          if (!empty($value)) {
            $or_group = $query->orConditionGroup();
            $or_group->condition('field_event_date', $value, '>=');
            $or_group->condition('field_this_day_in_history_3', $value, '>=');
            $query->condition($or_group);
          }
          break;
          
        case 'end_date':
          if (!empty($value)) {
            $or_group = $query->orConditionGroup();
            $or_group->condition('field_event_date', $value, '<=');
            $or_group->condition('field_this_day_in_history_3', $value, '<=');
            $query->condition($or_group);
          }
          break;
      }
    }
  }

  /**
   * Apply time period filter to query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query.
   * @param string $period
   *   The time period key.
   */
  protected function applyTimePeriodFilter($query, $period) {
    $date_ranges = [
      'pre-1500' => [NULL, '1500-01-01'],
      '1500-1650' => ['1500-01-01', '1650-12-31'],
      '1650-1800' => ['1650-01-01', '1800-12-31'],
      '1800-1900' => ['1800-01-01', '1900-12-31'],
      '1900-1950' => ['1900-01-01', '1950-12-31'],
      '1950-1990' => ['1950-01-01', '1990-12-31'],
      '1990-2025' => ['1990-01-01', NULL],
    ];
    
    if (isset($date_ranges[$period])) {
      [$start, $end] = $date_ranges[$period];
      
      $or_group = $query->orConditionGroup();
      
      // Apply to both date fields.
      if ($start && $end) {
        $and_group1 = $query->andConditionGroup();
        $and_group1->condition('field_event_date', $start, '>=');
        $and_group1->condition('field_event_date', $end, '<=');
        $or_group->condition($and_group1);
        
        $and_group2 = $query->andConditionGroup();
        $and_group2->condition('field_this_day_in_history_3', $start, '>=');
        $and_group2->condition('field_this_day_in_history_3', $end, '<=');
        $or_group->condition($and_group2);
      }
      elseif ($start) {
        $or_group->condition('field_event_date', $start, '>=');
        $or_group->condition('field_this_day_in_history_3', $start, '>=');
      }
      elseif ($end) {
        $or_group->condition('field_event_date', $end, '<=');
        $or_group->condition('field_this_day_in_history_3', $end, '<=');
      }
      
      $query->condition($or_group);
    }
  }
}