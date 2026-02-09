<?php

namespace Drupal\entity_overview\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_utils\Service\BlockQueryBuilderService;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\ContentExtractorService;
use Drupal\saho_utils\Service\EntityItemBuilderService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\saho_utils\Service\SortingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an "Entity Overview" block.
 *
 * @Block(
 *   id = "entity_overview_block",
 *   admin_label = @Translation("Entity Overview Block"),
 *   category = @Translation("All custom")
 * )
 */
class EntityOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The sorting service.
   *
   * @var \Drupal\saho_utils\Service\SortingService
   */
  protected $sortingService;

  /**
   * The image extractor service.
   *
   * @var \Drupal\saho_utils\Service\ImageExtractorService
   */
  protected $imageExtractor;

  /**
   * The block query builder service.
   *
   * @var \Drupal\saho_utils\Service\BlockQueryBuilderService
   */
  protected $queryBuilder;

  /**
   * The entity item builder service.
   *
   * @var \Drupal\saho_utils\Service\EntityItemBuilderService
   */
  protected $entityItemBuilder;

  /**
   * The content extractor service.
   *
   * @var \Drupal\saho_utils\Service\ContentExtractorService
   */
  protected $contentExtractor;

  /**
   * The configuration form helper service.
   *
   * @var \Drupal\saho_utils\Service\ConfigurationFormHelperService
   */
  protected $configFormHelper;

  /**
   * The cache helper service.
   *
   * @var \Drupal\saho_utils\Service\CacheHelperService
   */
  protected $cacheHelper;

  /**
   * Constructs an EntityOverviewBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\saho_utils\Service\SortingService $sorting_service
   *   The sorting service.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $image_extractor
   *   The image extractor service.
   * @param \Drupal\saho_utils\Service\BlockQueryBuilderService $query_builder
   *   The block query builder service.
   * @param \Drupal\saho_utils\Service\EntityItemBuilderService $entity_item_builder
   *   The entity item builder service.
   * @param \Drupal\saho_utils\Service\ContentExtractorService $content_extractor
   *   The content extractor service.
   * @param \Drupal\saho_utils\Service\ConfigurationFormHelperService $config_form_helper
   *   The configuration form helper service.
   * @param \Drupal\saho_utils\Service\CacheHelperService $cache_helper
   *   The cache helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeManagerInterface $entity_type_manager,
    SortingService $sorting_service,
    ImageExtractorService $image_extractor,
    BlockQueryBuilderService $query_builder,
    EntityItemBuilderService $entity_item_builder,
    ContentExtractorService $content_extractor,
    ConfigurationFormHelperService $config_form_helper,
    CacheHelperService $cache_helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->sortingService = $sorting_service;
    $this->imageExtractor = $image_extractor;
    $this->queryBuilder = $query_builder;
    $this->entityItemBuilder = $entity_item_builder;
    $this->contentExtractor = $content_extractor;
    $this->configFormHelper = $config_form_helper;
    $this->cacheHelper = $cache_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content_type' => 'article',
      'sort_order' => 'latest',
      'limit' => 5,
      'custom_header' => '',
      'intro_text' => 'Displaying the latest content from the %title section of the site.',
      'enable_filtering' => FALSE,
      'enable_sorting' => FALSE,
      'require_images' => FALSE,
      'display_mode' => 'default',
      'show_display_toggle' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['custom_header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Header'),
      '#description' => $this->t('Custom header text for the block. Leave empty to use the default block title. You can use %content_type to insert the content type name.'),
      '#default_value' => $this->configuration['custom_header'],
      '#maxlength' => 255,
    ];

    $form['intro_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Intro Text'),
      '#description' => $this->t('The introductory text describing the block. Use %title to insert the block title.'),
      '#default_value' => $this->configuration['intro_text'],
    ];

    // Get available content types for the dropdown.
    $content_types = $this->entityTypeBundleInfo->getBundleInfo('node');
    $allowed_types = ['place', 'event', 'upcomingevent', 'archive', 'article', 'biography'];
    $content_type_options = [];
    foreach ($content_types as $machine_name => $content_type) {
      if (in_array($machine_name, $allowed_types)) {
        $content_type_options[$machine_name] = $content_type['label'];
      }
    }

    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select the content type to display.'),
      '#options' => $content_type_options,
      '#default_value' => $this->configuration['content_type'],
      '#ajax' => [
        'callback' => [$this, 'updateTaxonomyTerms'],
        'wrapper' => 'taxonomy-term-wrapper',
        'event' => 'change',
      ],
    ];

    // Comprehensive sorting options.
    $form['sort_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort Order'),
      '#description' => $this->t('Select how to sort the content items.'),
      '#options' => [
        'latest' => $this->t('Latest - Most recently created items first (by created date)'),
        'oldest' => $this->t('Oldest - Least recently created items first (by created date)'),
        'recently_updated' => $this->t('Recently Updated - Most recently modified items first (by changed date)'),
        'random' => $this->t('Random - Shuffle all matching items'),
        'random_with_images' => $this->t('Random with Images - Only items with images, shuffled'),
      ],
      '#default_value' => $this->configuration['sort_order'],
    ];

    $form['require_images'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require images'),
      '#description' => $this->t('Filter out any items that do not have an image.'),
      '#default_value' => $this->configuration['require_images'],
      '#states' => [
        'visible' => [
          ':input[name="settings[sort_order]"]' => ['!value' => 'random_with_images'],
        ],
      ],
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items'),
      '#description' => $this->t('Number of items to show initially. Additional items can be loaded with "Load More" if enabled below.'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 1,
      '#max' => 50,
    ];

    // Display mode configuration.
    $form['display_mode'] = $this->configFormHelper->buildDisplayModeSelect(
      $this->configuration['display_mode'] ?? 'default'
    );

    // Display toggle option.
    $form['show_display_toggle'] = $this->configFormHelper->buildFeatureToggle(
      'Display Toggle',
      $this->configuration['show_display_toggle'] ?? FALSE,
      $this->t('Allow users to switch between display modes (grid/list/compact).')
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['custom_header'] = $form_state->getValue('custom_header');
    $this->configuration['intro_text'] = $form_state->getValue('intro_text');
    $this->configuration['content_type'] = $form_state->getValue('content_type');
    $this->configuration['sort_order'] = $form_state->getValue('sort_order');
    $this->configuration['limit'] = $form_state->getValue('limit');
    $this->configuration['require_images'] = $form_state->getValue('require_images');
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['show_display_toggle'] = $form_state->getValue('show_display_toggle');
    $this->configuration['enable_filtering'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content_type = $this->configuration['content_type'];
    $sort_order = $this->configuration['sort_order'];
    $limit = $this->configuration['limit'];
    $require_images = $this->configuration['require_images'] ?? FALSE;

    // Simplified - backend configuration only
    // Use custom header if provided, otherwise fall back to block label.
    $custom_header = $this->configuration['custom_header'];
    if (!empty($custom_header)) {
      // Get content type label for token replacement.
      $content_types = $this->entityTypeBundleInfo->getBundleInfo('node');
      $content_type_label = $content_types[$content_type]['label'] ?? $content_type;

      $block_title = str_replace('%content_type', $content_type_label, $custom_header);
    }
    else {
      $block_title = $this->configuration['label'] ?? '';
    }
    $intro_text = $this->configuration['intro_text'];

    // Generate a unique ID for this block instance.
    $block_id = 'entity-overview-' . substr(hash('sha256', $this->getPluginId() . serialize($this->configuration)), 0, 8);

    // Build the query using BlockQueryBuilderService.
    $query = $this->queryBuilder->buildBaseQuery('node', $content_type);
    $query = $this->queryBuilder->addPublishedFilter($query);

    // Apply image filter if required or if sort is random_with_images.
    if ($require_images || $sort_order === 'random_with_images') {
      $image_field = $this->imageExtractor->findImageFieldForContentType($content_type);
      $query = $this->queryBuilder->addImageFilter($query, $image_field);
    }

    // For random sorting, fetch more items for better variety before shuffling.
    if ($sort_order === 'random' || $sort_order === 'random_with_images') {
      // Fetch 5x the limit to provide good randomization.
      $fetch_limit = $limit * 5;
      $query = $this->queryBuilder->applyLimit($query, $fetch_limit);
    }
    else {
      // Apply sorting via SortingService.
      $query = $this->sortingService->applySorting($query, $sort_order, 'node');
      $query = $this->queryBuilder->applyLimit($query, $limit);
    }

    // Execute the query and load the entities.
    $nids = $query->execute();
    $nodes = !empty($nids) ? $this->entityTypeManager->getStorage('node')->loadMultiple($nids) : [];

    // For random sorting, shuffle and slice the results.
    if ($sort_order === 'random' || $sort_order === 'random_with_images') {
      $nodes = $this->sortingService->sortLoadedEntities($nodes, 'random');
      $nodes = array_slice($nodes, 0, $limit);
    }

    // Build entity items using EntityItemBuilderService with BC compatibility.
    $items = [];
    foreach ($nodes as $node) {
      $items[] = $this->buildEntityItem($node);
    }

    // Get display mode and toggle setting.
    $display_mode = $this->configuration['display_mode'] ?? 'default';
    $show_display_toggle = $this->configuration['show_display_toggle'] ?? FALSE;

    // Determine if there are more items available.
    $total_count = $this->getEntityCount($content_type);
    $has_more = count($items) < $total_count;

    // Build cache array using CacheHelperService.
    $cache = $this->cacheHelper->buildNodeListCache($content_type);

    // Return the render array.
    $build = [
      '#theme' => 'entity_overview_block',
      '#items' => $items,
      '#block_title' => $block_title,
      '#intro_text' => $intro_text,
      '#block_id' => $block_id,
      '#display_mode' => $display_mode,
      '#show_display_toggle' => $show_display_toggle,
      '#has_more' => $has_more,
      '#filter_options' => [],
      '#sort_options' => [],
      '#current_sort_order' => $sort_order,
      '#cache' => $cache,
      '#attached' => [
        'library' => ['entity_overview/entity_overview'],
        'drupalSettings' => [
          'entityOverview' => [
            $block_id => [
              'blockId' => $block_id,
              'contentType' => $content_type,
              'currentSortOrder' => $sort_order,
              'limit' => $limit,
              'displayMode' => $display_mode,
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Gets the total count of entities matching the filter.
   *
   * @param string $content_type
   *   The content type.
   *
   * @return int
   *   The total count.
   */
  protected function getEntityCount($content_type) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', $content_type)
      ->condition('status', 1)
      ->accessCheck(TRUE);

    return $query->count()->execute();
  }

  /**
   * Builds a data array from the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array
   *   The entity item data.
   */
  protected function buildEntityItem(NodeInterface $node) {
    // Use EntityItemBuilderService for consistent item building.
    $item = $this->entityItemBuilder->buildFullItem($node);

    // Ensure backward compatibility with legacy field names.
    if (isset($item['image_url'])) {
      $item['image'] = $item['image_url'];
    }
    if (isset($item['created'])) {
      // Convert from date string back to timestamp for BC.
      $item['created'] = $node->getCreatedTime();
    }
    if (isset($item['changed'])) {
      // Convert from date string back to timestamp for BC.
      $item['changed'] = $node->getChangedTime();
    }

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('saho_utils.sorting'),
      $container->get('saho_utils.image_extractor'),
      $container->get('saho_utils.query_builder'),
      $container->get('saho_utils.entity_item_builder'),
      $container->get('saho_utils.content_extractor'),
      $container->get('saho_utils.config_form_helper'),
      $container->get('saho_utils.cache_helper'),
    );
  }

}
