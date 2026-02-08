<?php

namespace Drupal\saho_statistics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'History Through Pictures' block.
 *
 * Displays featured images from the image content type.
 *
 * @Block(
 *   id = "saho_history_through_pictures",
 *   admin_label = @Translation("History Through Pictures"),
 *   category = @Translation("All custom"),
 * )
 */
class HistoryThroughPicturesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The image extractor service.
   *
   * @var \Drupal\saho_utils\Service\ImageExtractorService
   */
  protected $imageExtractor;

  /**
   * Constructs a HistoryThroughPicturesBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $image_extractor
   *   The image extractor service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ImageExtractorService $image_extractor,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->imageExtractor = $image_extractor;
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
      $container->get('saho_utils.image_extractor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items_per_page' => 6,
      'display_mode' => 'grid',
      'show_title' => TRUE,
      'show_caption' => FALSE,
      'block_title' => $this->t('History Through Pictures'),
      'sort_order' => 'random',
      'manual_selection' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    $form['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block title'),
      '#default_value' => $config['block_title'],
      '#description' => $this->t('The title to display for this block.'),
    ];

    $form['items_per_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of images to display'),
      '#options' => [
        3 => '3',
        6 => '6',
        9 => '9',
        12 => '12',
      ],
      '#default_value' => $config['items_per_page'],
      '#description' => $this->t('The number of featured images to display.'),
    ];

    $form['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode'),
      '#options' => [
        'grid' => $this->t('Grid'),
        'masonry' => $this->t('Masonry (Pinterest-style)'),
        'carousel' => $this->t('Carousel'),
      ],
      '#default_value' => $config['display_mode'],
      '#description' => $this->t('How to display the images.'),
    ];

    $form['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show image titles'),
      '#default_value' => $config['show_title'],
      '#description' => $this->t('Display the title overlay on images.'),
    ];

    $form['show_caption'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show captions'),
      '#default_value' => $config['show_caption'],
      '#description' => $this->t('Display image captions/descriptions.'),
    ];

    $form['sort_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort order'),
      '#options' => [
        'manual' => $this->t('Manual - Select specific images in exact order'),
        'random' => $this->t('Random - Different each page load'),
        'newest' => $this->t('Newest first'),
        'oldest' => $this->t('Oldest first'),
        'title_asc' => $this->t('Title A-Z'),
        'title_desc' => $this->t('Title Z-A'),
      ],
      '#default_value' => $config['sort_order'] ?? 'random',
      '#description' => $this->t('How to sort the featured images.'),
    ];

    $form['manual_selection'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Manual image selection'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['image'],
      ],
      '#tags' => TRUE,
      '#default_value' => $this->getManualSelectionNodes($config['manual_selection'] ?? ''),
      '#description' => $this->t('Select specific images to display in the order you want them to appear. The order you add them here is the order they will display. This overrides the "Number of images to display" setting above.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[sort_order]"]' => ['value' => 'manual'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Helper method to get node entities from stored manual selection.
   *
   * @param string $manual_selection
   *   Comma-separated node IDs.
   *
   * @return array
   *   Array of node entities.
   */
  protected function getManualSelectionNodes($manual_selection) {
    if (empty($manual_selection)) {
      return [];
    }

    $nids = array_filter(array_map('trim', explode(',', $manual_selection)));
    if (empty($nids)) {
      return [];
    }

    $node_storage = $this->entityTypeManager->getStorage('node');
    return $node_storage->loadMultiple($nids);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['block_title'] = $form_state->getValue('block_title');
    $this->configuration['items_per_page'] = $form_state->getValue('items_per_page');
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['show_title'] = $form_state->getValue('show_title');
    $this->configuration['show_caption'] = $form_state->getValue('show_caption');
    $this->configuration['sort_order'] = $form_state->getValue('sort_order');

    // Save manual selection as comma-separated node IDs.
    $manual_selection = $form_state->getValue('manual_selection');
    if (!empty($manual_selection) && is_array($manual_selection)) {
      $nids = array_map(function ($item) {
        return $item['target_id'];
      }, $manual_selection);
      $this->configuration['manual_selection'] = implode(',', $nids);
    }
    else {
      $this->configuration['manual_selection'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;
    $node_storage = $this->entityTypeManager->getStorage('node');
    $sort_order = $config['sort_order'] ?? 'random';

    // Check for manual selection first.
    if ($sort_order === 'manual' && !empty($config['manual_selection'])) {
      // Load manually selected nodes in the specified order.
      $nids = array_filter(array_map('trim', explode(',', $config['manual_selection'])));
      $nodes = [];
      foreach ($nids as $nid) {
        $node = $node_storage->load($nid);
        // Only include published image nodes.
        if ($node && $node->bundle() === 'image' && $node->isPublished()) {
          $nodes[$nid] = $node;
        }
      }
    }
    else {
      // Query for featured images.
      $query = $node_storage->getQuery()
        ->condition('type', 'image')
        ->condition('status', 1)
        ->condition('field_home_page_feature', 1)
        ->accessCheck(TRUE);

      // Apply sorting based on configuration.
      switch ($sort_order) {
        case 'manual':
          // Fallback if no manual selection: sort by weight field.
          $query->sort('field_home_page_feature_weight', 'ASC');
          $query->sort('title', 'ASC');
          $query->range(0, $config['items_per_page']);
          break;

        case 'random':
          // Get ALL featured images for true randomization.
          // No range() - we'll shuffle all results and take N items.
          break;

        case 'newest':
          $query->sort('created', 'DESC');
          $query->range(0, $config['items_per_page']);
          break;

        case 'oldest':
          $query->sort('created', 'ASC');
          $query->range(0, $config['items_per_page']);
          break;

        case 'title_asc':
          $query->sort('title', 'ASC');
          $query->range(0, $config['items_per_page']);
          break;

        case 'title_desc':
          $query->sort('title', 'DESC');
          $query->range(0, $config['items_per_page']);
          break;

        default:
          // Default to newest.
          $query->sort('created', 'DESC');
          $query->range(0, $config['items_per_page']);
          break;
      }

      $nids = $query->execute();

      if (empty($nids)) {
        return [
          '#markup' => '<p>' . $this->t('No featured images available.') . '</p>',
        ];
      }

      $nodes = $node_storage->loadMultiple($nids);

      // Post-process random sorting (shuffle after loading).
      if ($sort_order === 'random') {
        $nodes = array_values($nodes);
        shuffle($nodes);
        $nodes = array_slice($nodes, 0, $config['items_per_page']);
      }
    }

    // Final check for empty nodes.
    if (empty($nodes)) {
      return [
        '#markup' => '<p>' . $this->t('No featured images available.') . '</p>',
      ];
    }

    // Prepare items for the template.
    $items = [];
    foreach ($nodes as $node) {
      $image_url = $this->getNodeImageUrl($node);

      if (!$image_url) {
        continue;
      }

      // Get the feature link if available, otherwise link to the image node.
      $target_url = $this->getFeatureLink($node);

      $item = [
        'nid' => $node->id(),
        'title' => $node->getTitle(),
        'url' => $target_url,
        'image' => $image_url,
        'has_feature_link' => $this->hasFeatureLink($node),
      ];

      // Add caption if requested (strip HTML tags for clean display).
      if ($config['show_caption'] && $node->hasField('field_source') && !$node->get('field_source')->isEmpty()) {
        $caption = $node->get('field_source')->value;
        // Strip HTML tags and decode entities for clean text.
        $caption = strip_tags($caption);
        $caption = html_entity_decode($caption, ENT_QUOTES | ENT_HTML5);
        // Only add if there's actual content after stripping.
        if (!empty(trim($caption))) {
          $item['caption'] = $caption;
        }
      }

      $items[] = $item;
    }

    if (empty($items)) {
      return [
        '#markup' => '<p>' . $this->t('No images with valid image files found.') . '</p>',
      ];
    }

    // Get total count of all featured images for "View All" link.
    $total_count_query = $node_storage->getQuery()
      ->condition('type', 'image')
      ->condition('status', 1)
      ->condition('field_home_page_feature', 1)
      ->accessCheck(TRUE)
      ->count();
    $total_count = $total_count_query->execute();

    // Dynamic cache settings based on sort order.
    $cache_max_age = 3600;
    $cache_contexts = ['url.path'];

    // For random sorting, use shorter cache so different visitors see variety.
    if ($sort_order === 'random') {
      $cache_max_age = 300;
      // Use session context for true per-user randomization.
      $cache_contexts[] = 'session';
    }

    return [
      '#theme' => 'saho_history_through_pictures',
      '#items' => $items,
      '#block_title' => $config['block_title'],
      '#display_mode' => $config['display_mode'],
      '#show_title' => $config['show_title'],
      '#show_caption' => $config['show_caption'],
      '#total_count' => $total_count,
      '#attached' => [
        'library' => [
          'saho_statistics/history-through-pictures',
        ],
      ],
      '#cache' => [
        'contexts' => $cache_contexts,
        'tags' => ['node_list:image'],
        'max-age' => $cache_max_age,
      ],
    ];
  }

  /**
   * Gets the image URL for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string|null
   *   The relative image URL or NULL if not available.
   */
  protected function getNodeImageUrl($node) {
    // Try field_image first, then field_archive_image.
    $image_fields = ['field_image', 'field_archive_image'];

    foreach ($image_fields as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }

      $field_value = $node->get($field_name)->first();
      if (!$field_value) {
        continue;
      }

      $file = $field_value->get('entity')->getValue();
      if (!$file) {
        continue;
      }

      $uri = $file->getFileUri();
      $path = str_replace('public://', '/sites/default/files/', $uri);
      return $path;
    }

    return NULL;
  }

  /**
   * Gets the feature link URL for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   The URL to link to (feature article/biography or the image node itself).
   */
  protected function getFeatureLink($node) {
    // Check if field_feature_link has a value.
    if ($node->hasField('field_feature_link') && !$node->get('field_feature_link')->isEmpty()) {
      $referenced_entity = $node->get('field_feature_link')->entity;
      if ($referenced_entity) {
        return Url::fromRoute('entity.node.canonical', [
          'node' => $referenced_entity->id(),
        ])->toString();
      }
    }

    // Fallback to the image node itself.
    return Url::fromRoute('entity.node.canonical', [
      'node' => $node->id(),
    ])->toString();
  }

  /**
   * Checks if the node has a feature link.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return bool
   *   TRUE if the node has a feature link, FALSE otherwise.
   */
  protected function hasFeatureLink($node) {
    return $node->hasField('field_feature_link') && !$node->get('field_feature_link')->isEmpty();
  }

}
