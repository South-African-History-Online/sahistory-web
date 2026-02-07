<?php

namespace Drupal\tdih\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\ContentExtractorService;
use Drupal\saho_utils\Service\EntityItemBuilderService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\saho_utils\Service\SortingService;
use Drupal\tdih\Service\NodeFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a This Day In History Block with manual override config.
 *
 * Displays historical events that occurred on today's date, or allows
 * administrators to manually select a specific event to display.
 *
 * @Block(
 *   id = "tdih_block",
 *   admin_label = @Translation("TDIH Block"),
 *   category = @Translation("All custom")
 * )
 */
class TdihBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The NodeFetcher service used to load today's nodes.
   *
   * @var \Drupal\tdih\Service\NodeFetcher
   */
  protected $nodeFetcher;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

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
   * Constructs a new TdihBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\tdih\Service\NodeFetcher $nodeFetcher
   *   Our custom NodeFetcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\saho_utils\Service\SortingService $sorting_service
   *   The sorting service.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $image_extractor
   *   The image extractor service.
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
    NodeFetcher $nodeFetcher,
    EntityTypeManagerInterface $entity_type_manager,
    TimeInterface $time,
    SortingService $sorting_service,
    ImageExtractorService $image_extractor,
    EntityItemBuilderService $entity_item_builder,
    ContentExtractorService $content_extractor,
    ConfigurationFormHelperService $config_form_helper,
    CacheHelperService $cache_helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeFetcher = $nodeFetcher;
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
    $this->sortingService = $sorting_service;
    $this->imageExtractor = $image_extractor;
    $this->entityItemBuilder = $entity_item_builder;
    $this->contentExtractor = $content_extractor;
    $this->configFormHelper = $config_form_helper;
    $this->cacheHelper = $cache_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tdih.node_fetcher'),
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
      $container->get('saho_utils.sorting'),
      $container->get('saho_utils.image_extractor'),
      $container->get('saho_utils.entity_item_builder'),
      $container->get('saho_utils.content_extractor'),
      $container->get('saho_utils.config_form_helper'),
      $container->get('saho_utils.cache_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Default settings for newly placed blocks.
    return [
      'use_manual_override' => FALSE,
      'manual_entity_id' => NULL,
      'button_block_id' => NULL,
      'sort_by' => 'none',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * Builds the configuration form used when placing or editing this block.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Manual override checkbox using service.
    $form['use_manual_override'] = $this->configFormHelper->buildManualOverrideCheckbox(
      $this->configuration['use_manual_override'],
      NULL,
      $this->t('Enable to manually select a node instead of auto date-based logic.')
    );

    // Entity autocomplete using service.
    $form['manual_entity_id'] = $this->configFormHelper->buildEntityAutocomplete(
      'node',
      'event',
      $this->configuration['manual_entity_id'] ?? NULL,
      [
        'visible' => [
          ':input[name="use_manual_override"]' => ['checked' => TRUE],
        ],
      ],
      $this->t('Manual Entity'),
      $this->t('Select an event node to display if "Use Manual Override" is checked.')
    );

    // Validate button_block_id before loading.
    $button_block_default = NULL;
    $button_id = $this->configuration['button_block_id'] ?? NULL;
    if ($button_id !== NULL && is_numeric($button_id) && (int) $button_id > 0) {
      $button_block_default = $this->entityTypeManager->getStorage('block_content')->load((int) $button_id);
    }

    $form['button_block_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Button Block'),
      '#description' => $this->t('Select a button block to display at the bottom of the TDIH block. Leave empty for no button.'),
      '#target_type' => 'block_content',
      '#selection_handler' => 'default:block_content',
      '#selection_settings' => [
        'target_bundles' => ['button'],
      ],
      '#default_value' => $button_block_default,
    ];

    // Sort select using service.
    $form['sort_by'] = $this->configFormHelper->buildSortSelect(
      $this->configuration['sort_by'],
      [
        'visible' => [
          ':input[name="settings[use_manual_override]"]' => ['checked' => FALSE],
        ],
      ],
      TRUE,
      []
    );
    $form['sort_by']['#description'] = $this->t('Choose how to select the event for today. "Random" shuffles results, other options select consistently.');

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Handles form submissions for this block's configuration.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['use_manual_override'] = $form_state->getValue('use_manual_override');
    $this->configuration['manual_entity_id'] = $form_state->getValue('manual_entity_id');
    $this->configuration['button_block_id'] = $form_state->getValue('button_block_id');
    $this->configuration['sort_by'] = $form_state->getValue('sort_by');
  }

  /**
   * {@inheritdoc}
   *
   * Builds the render array for the TDIH block.
   */
  public function build() {
    $manual_override = $this->configuration['use_manual_override'];
    $manual_entity_id = $this->configuration['manual_entity_id'] ?? NULL;

    $tdih_nodes = [];

    try {
      // 1) If "use manual override" is enabled and an entity is chosen,
      // load that node. Validate entity ID is a positive integer.
      if ($manual_override && $manual_entity_id !== NULL
          && is_numeric($manual_entity_id) && (int) $manual_entity_id > 0) {
        $node = $this->entityTypeManager->getStorage('node')->load((int) $manual_entity_id);
        if ($node) {
          $tdih_nodes[] = $this->buildNodeItem($node);
        }
      }
      // 2) Otherwise, do automatic selection based on today's date.
      else {
        // Force South African timezone for consistent TDIH functionality.
        $sa_timezone = new \DateTimeZone('Africa/Johannesburg');
        $today = new \DateTime('now', $sa_timezone);
        $month = $today->format('m');
        $day = $today->format('d');

        // Use our NodeFetcher to load a set of matching nodes for today's date.
        $target_date = $month . '-' . $day;
        $nodes = $this->nodeFetcher->loadPotentialEvents($target_date);

        // Filter nodes by exact date match and front page feature using simple
        // string operations.
        $filtered_nodes = [];
        foreach ($nodes as $node) {
          // Only process nodes that are featured on front page.
          if ($node->hasField('field_home_page_feature') && $node->get('field_home_page_feature')->value) {
            if ($node->hasField('field_event_date') && !$node->get('field_event_date')->isEmpty()) {
              $raw_date = $node->get('field_event_date')->value;
              if (!empty($raw_date)) {
                // Extract MM-DD from YYYY-MM-DD format.
                if (preg_match('/\d{4}-(\d{2})-(\d{2})/', $raw_date, $matches)) {
                  $item_date = $matches[1] . '-' . $matches[2];
                  if ($item_date === $target_date) {
                    $filtered_nodes[] = $node;
                  }
                }
              }
            }
          }
        }

        if (!empty($filtered_nodes)) {
          // Apply sorting configuration.
          $sort_by = $this->configuration['sort_by'] ?? 'none';
          $selected_node = NULL;

          // Handle random_with_images option.
          if ($sort_by === 'random_with_images') {
            // Filter nodes to only those with images.
            $nodes_with_images = array_filter($filtered_nodes, function ($node) {
              return $this->imageExtractor->hasImage($node, 'field_event_image');
            });

            if (!empty($nodes_with_images)) {
              // Random selection from nodes with images.
              $selected_node = $nodes_with_images[array_rand($nodes_with_images)];
            }
            else {
              // Fallback to random selection if none have images.
              $selected_node = $filtered_nodes[array_rand($filtered_nodes)];
            }
          }
          elseif ($sort_by === 'none') {
            // Random selection (existing behavior).
            $selected_node = $filtered_nodes[array_rand($filtered_nodes)];
          }
          else {
            // Use SortingService for consistent sorting.
            $sorted_nodes = $this->sortingService->sortLoadedEntities($filtered_nodes, $sort_by);
            // Select the first node after sorting.
            $selected_node = reset($sorted_nodes);
          }

          if ($selected_node) {
            $tdih_nodes[] = $this->buildNodeItem($selected_node);
          }
        }
      }
    }
    catch (\Exception $e) {
      // Silently handle exceptions.
    }
    // Force South African timezone for cache key.
    $sa_timezone = new \DateTimeZone('Africa/Johannesburg');
    $today = new \DateTime('now', $sa_timezone);
    $cache_date = $today->format('Y-m-d');
    $sort_by = $this->configuration['sort_by'] ?? 'none';

    // Prepare the render array.
    $build = [
      '#theme' => 'tdih_block',
      '#tdih_nodes' => $tdih_nodes,
      // Attach the TDIH block CSS library.
      '#attached' => [
        'library' => [
          'tdih/tdih-block',
        ],
      ],
      // Add cache metadata to ensure the block updates at midnight.
      '#cache' => [
        'keys' => ['tdih_block', $sort_by, $cache_date],
        'contexts' => $this->getCacheContexts(),
        'tags' => $this->getCacheTags(),
        'max-age' => $this->getCacheMaxAge(),
      ],
    ];

    // Add the button block if configured. Validate ID is a positive integer.
    $button_block_id = $this->configuration['button_block_id'] ?? NULL;
    if ($button_block_id !== NULL && is_numeric($button_block_id) && (int) $button_block_id > 0) {
      try {
        $button_block = $this->entityTypeManager->getStorage('block_content')
          ->load((int) $button_block_id);
        if ($button_block) {
          $view_builder = $this->entityTypeManager->getViewBuilder('block_content');
          $build['#button_block'] = $view_builder->view($button_block);
          // Add the button block's cache tags.
          $build['#cache']['tags'][] = 'block_content:' . $button_block->id();
        }
      }
      catch (\Exception $e) {
        // Silently handle exceptions.
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $manual_id = $this->configuration['manual_entity_id'] ?? NULL;
    if ($this->configuration['use_manual_override']
        && $manual_id !== NULL && is_numeric($manual_id) && (int) $manual_id > 0) {
      // If using manual override with valid ID, use default cache max age.
      return Cache::PERMANENT;
    }

    // Force South African timezone for consistent TDIH functionality.
    $sa_timezone = new \DateTimeZone('Africa/Johannesburg');

    // Calculate seconds until midnight in South African timezone.
    $now = $this->time->getCurrentTime();
    // Calculate the timestamp for midnight tonight in SA timezone.
    $midnight = new \DateTime('now', $sa_timezone);
    $midnight->setTime(0, 0, 0);
    $midnight->modify('+1 day');
    $seconds_until_midnight = $midnight->getTimestamp() - $now;

    // Return seconds until midnight, or minimum 60 seconds to prevent
    // excessive cache rebuilds if we're very close to midnight.
    return max($seconds_until_midnight, 60);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();

    // Add cache tags for the block itself.
    $tags[] = 'tdih_block';

    // If using manual override with valid ID, add the node's cache tag.
    $manual_id = $this->configuration['manual_entity_id'] ?? NULL;
    if ($this->configuration['use_manual_override']
        && $manual_id !== NULL && is_numeric($manual_id) && (int) $manual_id > 0) {
      $tags[] = 'node:' . (int) $manual_id;
    }
    else {
      // Add node_list:event tag to invalidate when event nodes are updated.
      $tags[] = 'node_list:event';
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    // Add user.permissions context to differentiate between anonymous and
    // authenticated users if needed.
    $contexts[] = 'user.permissions';

    return $contexts;
  }

  /**
   * Helper function to build an array of item data from node, including image.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity to build data from.
   *
   * @return array
   *   An array of data for the template, including id, title, url, event_date,
   *   image, and rendered node.
   */
  protected function buildNodeItem($node) {
    // Use EntityItemBuilderService for basic item building.
    $item = $this->entityItemBuilder->buildItemWithImage($node, 'field_event_image');

    // Ensure backward compatibility with 'image' key.
    if (isset($item['image_url'])) {
      $item['image'] = $item['image_url'];
    }

    // Strip tags from title for TDIH display.
    $item['title'] = strip_tags($item['title']);

    try {
      // Add event-specific date handling.
      $event_date = NULL;
      if ($node->hasField('field_event_date') && !$node->get('field_event_date')->isEmpty()) {
        $raw_date = $node->get('field_event_date')->value;

        if (!empty($raw_date)) {
          // Create DateTime at noon to avoid timezone boundary issues.
          $event_date = new \DateTime($raw_date . ' 12:00:00',
            new \DateTimeZone('Africa/Johannesburg'));
        }
      }
      $item['event_date'] = $event_date;

      // Get alt text from the image field if available.
      $image_alt = $node->label();
      if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
        $image_item = $node->get('field_event_image')->first();
        if ($image_item && !empty($image_item->getValue()['alt'])) {
          $image_alt = $image_item->getValue()['alt'];
        }
      }
      $item['image_alt'] = strip_tags($image_alt);
    }
    catch (\Exception $e) {
      // Silently handle exceptions.
      $item['event_date'] = NULL;
      $item['image_alt'] = strip_tags($node->label());
    }

    // Use ContentExtractorService for body text extraction.
    $item['body'] = $this->contentExtractor->extractBodyText($node);

    // Add fully rendered teaser.
    $item['rendered'] = $this->renderNode($node, 'teaser');

    return $item;
  }

  /**
   * Helper function to render a node in a specified view mode.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to render.
   * @param string $view_mode
   *   The view mode (e.g. 'teaser', 'full').
   *
   * @return array
   *   A render array for the node.
   */
  protected function renderNode($node, $view_mode = 'teaser') {
    try {
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      return $view_builder->view($node, $view_mode);
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
