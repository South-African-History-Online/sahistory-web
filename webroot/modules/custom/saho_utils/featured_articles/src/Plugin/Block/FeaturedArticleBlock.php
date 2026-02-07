<?php

namespace Drupal\featured_articles\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\EntityItemBuilderService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\saho_utils\Service\SortingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Featured Article" block.
 *
 * @Block(
 *   id = "featured_article_block",
 *   admin_label = @Translation("Featured Article Block"),
 *   category = @Translation("All custom")
 * )
 */
class FeaturedArticleBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The entity item builder service.
   *
   * @var \Drupal\saho_utils\Service\EntityItemBuilderService
   */
  protected $entityItemBuilder;

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
   * Constructs a FeaturedArticleBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\saho_utils\Service\SortingService $sorting_service
   *   The sorting service.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $image_extractor
   *   The image extractor service.
   * @param \Drupal\saho_utils\Service\EntityItemBuilderService $entity_item_builder
   *   The entity item builder service.
   * @param \Drupal\saho_utils\Service\ConfigurationFormHelperService $config_form_helper
   *   The configuration form helper service.
   * @param \Drupal\saho_utils\Service\CacheHelperService $cache_helper
   *   The cache helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    SortingService $sorting_service,
    ImageExtractorService $image_extractor,
    EntityItemBuilderService $entity_item_builder,
    ConfigurationFormHelperService $config_form_helper,
    CacheHelperService $cache_helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->sortingService = $sorting_service;
    $this->imageExtractor = $image_extractor;
    $this->entityItemBuilder = $entity_item_builder;
    $this->configFormHelper = $config_form_helper;
    $this->cacheHelper = $cache_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'use_manual_override' => FALSE,
      'manual_entity_id' => NULL,
      'sort_by' => 'none',
      'display_mode' => 'default',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Manual override checkbox using service.
    $form['use_manual_override'] = $this->configFormHelper->buildManualOverrideCheckbox(
      $this->configuration['use_manual_override'],
      NULL,
      $this->t('Select a specific article instead of a random featured one.')
    );

    // Entity autocomplete using service.
    $form['manual_entity_id'] = $this->configFormHelper->buildEntityAutocomplete(
      'node',
      'article',
      $this->configuration['manual_entity_id'],
      [
        'visible' => [
          ':input[name="use_manual_override"]' => ['checked' => TRUE],
        ],
      ],
      $this->t('Manual Article'),
      $this->t('Choose the article to display if override is enabled.')
    );

    // Sort select using service with custom options.
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
    $form['sort_by']['#description'] = $this->t('Choose how to sort featured articles. "Random" shuffles results each time.');

    // Display mode configuration.
    $form['display_mode'] = $this->configFormHelper->buildDisplayModeSelect(
      $this->configuration['display_mode'] ?? 'default'
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['use_manual_override'] = $form_state->getValue('use_manual_override');
    $this->configuration['manual_entity_id'] = $form_state->getValue('manual_entity_id');
    $this->configuration['sort_by'] = $form_state->getValue('sort_by');
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $manual_override = $this->configuration['use_manual_override'];
    $manual_entity_id = $this->configuration['manual_entity_id'];

    // If manual override is set, display that specific article.
    // Validate entity ID is a positive integer before loading for security.
    if ($manual_override && $manual_entity_id && is_numeric($manual_entity_id) && (int) $manual_entity_id > 0) {
      $node = $this->entityTypeManager->getStorage('node')->load((int) $manual_entity_id);
      if ($node) {
        return [
          '#theme' => 'featured_article_block',
          '#article_item' => $this->buildArticleItem($node),
        ];
      }
    }

    // Otherwise, pick an article based on sort configuration.
    // Build base query for featured articles.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_home_page_feature', 1)
      ->condition('field_staff_picks', 1)
      ->accessCheck(TRUE);

    // Apply sorting based on configuration using shared service.
    $sort_by = $this->configuration['sort_by'] ?? 'none';
    $this->sortingService->applySorting($query, $sort_by);

    // Execute query and get the node ID.
    $nids = $query->execute();
    $nid = !empty($nids) ? reset($nids) : NULL;

    if (empty($nid)) {
      return [
        '#theme' => 'featured_article_block',
        '#article_item' => NULL,
      ];
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    // Get display mode.
    $display_mode = $this->configuration['display_mode'] ?? 'default';

    // Build cache array using CacheHelperService.
    $cache = $this->cacheHelper->buildNodeListCache('article');

    return [
      '#theme' => 'featured_article_block',
      '#article_item' => $node ? $this->buildArticleItem($node) : NULL,
      '#display_mode' => $display_mode,
      '#cache' => $cache,
    ];
  }

  /**
   * Builds a data array from the article node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity to build the article item from.
   *
   * @return array
   *   An array containing the article data with keys:
   *   - id: The node ID
   *   - title: The node title
   *   - url: The node URL
   *   - image: The image URL if available
   */
  protected function buildArticleItem(NodeInterface $node) {
    // Use EntityItemBuilderService for consistent item building.
    $item = $this->entityItemBuilder->buildItemWithImage($node, 'field_article_image');

    // Ensure backward compatibility with 'image' key.
    if (isset($item['image_url'])) {
      $item['image'] = $item['image_url'];
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
      $container->get('entity_type.manager'),
      $container->get('saho_utils.sorting'),
      $container->get('saho_utils.image_extractor'),
      $container->get('saho_utils.entity_item_builder'),
      $container->get('saho_utils.config_form_helper'),
      $container->get('saho_utils.cache_helper'),
    );
  }

}
