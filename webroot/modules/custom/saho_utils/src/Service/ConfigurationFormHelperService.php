<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Service for building reusable configuration form elements.
 *
 * Eliminates duplicate form element code across SAHO custom blocks by
 * providing standardized, reusable form element builders.
 */
class ConfigurationFormHelperService {

  use StringTranslationTrait;

  /**
   * The sorting service.
   *
   * @var \Drupal\saho_utils\Service\SortingService
   */
  protected SortingService $sortingService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a ConfigurationFormHelperService object.
   *
   * @param \Drupal\saho_utils\Service\SortingService $sorting_service
   *   The sorting service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    SortingService $sorting_service,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation,
  ) {
    $this->sortingService = $sorting_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Build manual override checkbox element.
   *
   * @param bool $default_value
   *   Default value for the checkbox.
   * @param string|null $title
   *   Optional custom title. If NULL, uses default.
   * @param string|null $description
   *   Optional custom description. If NULL, uses default.
   *
   * @return array
   *   Form element array.
   */
  public function buildManualOverrideCheckbox(
    bool $default_value = FALSE,
    ?string $title = NULL,
    ?string $description = NULL,
  ): array {
    return [
      '#type' => 'checkbox',
      '#title' => $title ?? $this->t('Use Manual Override?'),
      '#description' => $description ?? $this->t('Select a specific item instead of automatic selection.'),
      '#default_value' => $default_value,
    ];
  }

  /**
   * Build entity autocomplete element.
   *
   * @param string $entity_type
   *   The entity type (e.g., 'node', 'taxonomy_term').
   * @param string $bundle
   *   The bundle (e.g., 'article', 'biography').
   * @param mixed $default_value
   *   Default entity or entity ID.
   * @param array $states
   *   Optional Form API #states for conditional visibility.
   * @param string|null $title
   *   Optional custom title.
   * @param string|null $description
   *   Optional custom description.
   *
   * @return array
   *   Form element array.
   */
  public function buildEntityAutocomplete(
    string $entity_type,
    string $bundle,
    $default_value = NULL,
    array $states = [],
    ?string $title = NULL,
    ?string $description = NULL,
  ): array {
    // Load entity if we have an ID.
    $default_entity = NULL;
    if ($default_value && is_numeric($default_value) && (int) $default_value > 0) {
      $default_entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->load($default_value);
    }
    elseif (is_object($default_value)) {
      $default_entity = $default_value;
    }

    $element = [
      '#type' => 'entity_autocomplete',
      '#title' => $title ?? $this->t('Select @bundle', ['@bundle' => ucfirst($bundle)]),
      '#description' => $description ?? $this->t('Choose the @bundle to display.', ['@bundle' => $bundle]),
      '#target_type' => $entity_type,
      '#selection_handler' => 'default:' . $entity_type,
      '#selection_settings' => [
        'target_bundles' => [$bundle],
      ],
      '#default_value' => $default_entity,
    ];

    if (!empty($states)) {
      $element['#states'] = $states;
    }

    return $element;
  }

  /**
   * Build sort select element.
   *
   * @param string $default_value
   *   Default sort option.
   * @param array $states
   *   Optional Form API #states for conditional visibility.
   * @param bool $include_random
   *   Whether to include random option. Default is TRUE.
   * @param array $custom_options
   *   Optional custom sort options to merge with defaults.
   *
   * @return array
   *   Form element array.
   */
  public function buildSortSelect(
    string $default_value = 'none',
    array $states = [],
    bool $include_random = TRUE,
    array $custom_options = [],
  ): array {
    $options = [
      'none' => $this->t('None (Default)'),
      'latest' => $this->t('Latest (Newest First)'),
      'oldest' => $this->t('Oldest (Oldest First)'),
      'recently_updated' => $this->t('Recently Updated'),
      'title_asc' => $this->t('Title (A-Z)'),
    ];

    if ($include_random) {
      $options['random'] = $this->t('Random');
    }

    // Merge custom options.
    if (!empty($custom_options)) {
      $options = array_merge($options, $custom_options);
    }

    $element = [
      '#type' => 'select',
      '#title' => $this->t('Sort By'),
      '#description' => $this->t('Choose how to sort the items.'),
      '#options' => $options,
      '#default_value' => $default_value,
    ];

    if (!empty($states)) {
      $element['#states'] = $states;
    }

    return $element;
  }

  /**
   * Build item limit select element.
   *
   * @param int $default_value
   *   Default number of items.
   * @param int $min
   *   Minimum number of items. Default is 1.
   * @param int $max
   *   Maximum number of items. Default is 50.
   * @param string|null $title
   *   Optional custom title.
   * @param string|null $description
   *   Optional custom description.
   *
   * @return array
   *   Form element array.
   */
  public function buildItemLimitSelect(
    int $default_value = 5,
    int $min = 1,
    int $max = 50,
    ?string $title = NULL,
    ?string $description = NULL,
  ): array {
    // Build options array from min to max.
    $options = [];
    for ($i = $min; $i <= $max; $i++) {
      $options[$i] = (string) $i;
    }

    return [
      '#type' => 'select',
      '#title' => $title ?? $this->t('Number of Items'),
      '#description' => $description ?? $this->t('How many items to display.'),
      '#options' => $options,
      '#default_value' => $default_value,
    ];
  }

  /**
   * Build display mode select element.
   *
   * @param string $default_value
   *   Default display mode.
   * @param array $custom_modes
   *   Optional custom display modes to use instead of defaults.
   * @param string|null $title
   *   Optional custom title.
   * @param string|null $description
   *   Optional custom description.
   *
   * @return array
   *   Form element array.
   */
  public function buildDisplayModeSelect(
    string $default_value = 'default',
    array $custom_modes = [],
    ?string $title = NULL,
    ?string $description = NULL,
  ): array {
    $options = !empty($custom_modes) ? $custom_modes : [
      'default' => $this->t('Default'),
      'compact' => $this->t('Compact'),
      'full-width' => $this->t('Full Width'),
    ];

    return [
      '#type' => 'select',
      '#title' => $title ?? $this->t('Display Mode'),
      '#description' => $description ?? $this->t('Choose how items should be displayed.'),
      '#options' => $options,
      '#default_value' => $default_value,
    ];
  }

  /**
   * Build category/taxonomy term select element.
   *
   * @param string $vocabulary_id
   *   The vocabulary ID (e.g., 'biography_category').
   * @param mixed $default_value
   *   Default term ID or term object.
   * @param string|null $title
   *   Optional custom title.
   * @param string|null $description
   *   Optional custom description.
   *
   * @return array
   *   Form element array.
   */
  public function buildCategorySelect(
    string $vocabulary_id,
    $default_value = NULL,
    ?string $title = NULL,
    ?string $description = NULL,
  ): array {
    // Load all terms from the vocabulary.
    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary_id);

    $options = ['' => $this->t('- All Categories -')];
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    return [
      '#type' => 'select',
      '#title' => $title ?? $this->t('Category'),
      '#description' => $description ?? $this->t('Filter by category.'),
      '#options' => $options,
      '#default_value' => $default_value,
    ];
  }

  /**
   * Build enable/disable checkbox for features.
   *
   * @param string $feature_name
   *   The name of the feature (e.g., 'Load More', 'Filtering').
   * @param bool $default_value
   *   Default value for the checkbox.
   * @param string|null $description
   *   Optional custom description.
   *
   * @return array
   *   Form element array.
   */
  public function buildFeatureToggle(
    string $feature_name,
    bool $default_value = FALSE,
    ?string $description = NULL,
  ): array {
    return [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable @feature', ['@feature' => $feature_name]),
      '#description' => $description ?? $this->t('Enable or disable the @feature feature.', ['@feature' => strtolower($feature_name)]),
      '#default_value' => $default_value,
    ];
  }

  /**
   * Build number input element.
   *
   * @param string $title
   *   The title for the field.
   * @param int $default_value
   *   Default value.
   * @param int $min
   *   Minimum value.
   * @param int $max
   *   Maximum value.
   * @param string|null $description
   *   Optional description.
   *
   * @return array
   *   Form element array.
   */
  public function buildNumberInput(
    string $title,
    int $default_value,
    int $min = 0,
    int $max = 100,
    ?string $description = NULL,
  ): array {
    return [
      '#type' => 'number',
      '#title' => $title,
      '#description' => $description,
      '#default_value' => $default_value,
      '#min' => $min,
      '#max' => $max,
    ];
  }

  /**
   * Build text input element.
   *
   * @param string $title
   *   The title for the field.
   * @param string $default_value
   *   Default value.
   * @param string|null $description
   *   Optional description.
   * @param int $maxlength
   *   Maximum length. Default is 255.
   *
   * @return array
   *   Form element array.
   */
  public function buildTextInput(
    string $title,
    string $default_value = '',
    ?string $description = NULL,
    int $maxlength = 255,
  ): array {
    return [
      '#type' => 'textfield',
      '#title' => $title,
      '#description' => $description,
      '#default_value' => $default_value,
      '#maxlength' => $maxlength,
    ];
  }

}
