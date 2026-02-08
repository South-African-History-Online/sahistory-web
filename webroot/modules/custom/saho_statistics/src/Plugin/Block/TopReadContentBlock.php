<?php

namespace Drupal\saho_statistics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\saho_statistics\TermTracker;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Top Read Content' block.
 *
 * Displays the most read/popular content based on statistics tracking.
 *
 * @Block(
 *   id = "saho_top_read_content",
 *   admin_label = @Translation("SAHO Top Read Content"),
 *   category = @Translation("All custom"),
 * )
 */
class TopReadContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The term tracker service.
   *
   * @var \Drupal\saho_statistics\TermTracker
   */
  protected $termTracker;

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
   * Constructs a TopReadContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\saho_statistics\TermTracker $term_tracker
   *   The term tracker service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $image_extractor
   *   The image extractor service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TermTracker $term_tracker,
    EntityTypeManagerInterface $entity_type_manager,
    ImageExtractorService $image_extractor,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->termTracker = $term_tracker;
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
      $container->get('saho_statistics.term_tracker'),
      $container->get('entity_type.manager'),
      $container->get('saho_utils.image_extractor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items_per_page' => 10,
      'time_period' => 'all_time',
      'content_types' => [],
      'display_mode' => 'list',
      'show_view_count' => TRUE,
      'show_thumbnail' => FALSE,
      'full_width' => TRUE,
      'block_title' => $this->t('Most Read Content'),
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
      '#title' => $this->t('Number of items to display'),
      '#options' => [
        5 => '5',
        10 => '10',
        15 => '15',
        20 => '20',
      ],
      '#default_value' => $config['items_per_page'],
      '#description' => $this->t('The number of most read items to display.'),
    ];

    $form['time_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Time period'),
      '#options' => [
        'today' => $this->t('Today'),
        'this_week' => $this->t('This Week'),
        'all_time' => $this->t('All Time'),
      ],
      '#default_value' => $config['time_period'],
      '#description' => $this->t('Filter content by time period.'),
    ];

    // Get all content types.
    $content_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();

    $type_options = [];
    foreach ($content_types as $type_id => $type) {
      $type_options[$type_id] = $type->label();
    }

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#options' => $type_options,
      '#default_value' => $config['content_types'],
      '#description' => $this->t('Select content types to include. Leave empty to include all types.'),
    ];

    $form['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode'),
      '#options' => [
        'list' => $this->t('List (numbered)'),
        'grid' => $this->t('Grid'),
        'cards' => $this->t('Cards'),
      ],
      '#default_value' => $config['display_mode'],
      '#description' => $this->t('How to display the content items.'),
    ];

    $form['show_view_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show view count'),
      '#default_value' => $config['show_view_count'],
      '#description' => $this->t('Display the number of views for each item.'),
    ];

    $form['show_thumbnail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show thumbnail images'),
      '#default_value' => $config['show_thumbnail'],
      '#description' => $this->t('Display thumbnail images if available.'),
    ];

    $form['full_width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Full width display'),
      '#default_value' => $config['full_width'],
      '#description' => $this->t('Display the block in full width (container-fluid).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    // Filter out unchecked content types.
    $content_types = array_filter($form_state->getValue('content_types'));

    $this->configuration['block_title'] = $form_state->getValue('block_title');
    $this->configuration['items_per_page'] = $form_state->getValue('items_per_page');
    $this->configuration['time_period'] = $form_state->getValue('time_period');
    $this->configuration['content_types'] = array_values($content_types);
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['show_view_count'] = $form_state->getValue('show_view_count');
    $this->configuration['show_thumbnail'] = $form_state->getValue('show_thumbnail');
    $this->configuration['full_width'] = $form_state->getValue('full_width');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;

    // Get most read content from the service.
    $results = $this->termTracker->getMostReadContent(
      $config['items_per_page'],
      $config['time_period'],
      $config['content_types']
    );

    // Prepare items for the template.
    $items = [];
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($results as $result) {
      $node = $node_storage->load($result->nid);
      if (!$node) {
        continue;
      }

      // Determine which count to show based on time period.
      $view_count = $result->totalcount;
      if ($config['time_period'] === 'today') {
        $view_count = $result->daycount;
      }

      $item = [
        'nid' => $result->nid,
        'title' => $result->title,
        'type' => $result->type,
        'url' => Url::fromRoute('entity.node.canonical', ['node' => $result->nid])->toString(),
        'view_count' => $view_count,
      ];

      // Add thumbnail if requested.
      if ($config['show_thumbnail']) {
        $item['image'] = $this->getNodeImageUrl($node);
      }

      $items[] = $item;
    }

    // If no results, show message.
    if (empty($items)) {
      return [
        '#markup' => '<p>' . $this->t('No content available.') . '</p>',
      ];
    }

    return [
      '#theme' => 'saho_top_read_content_block',
      '#items' => $items,
      '#block_title' => $config['block_title'],
      '#display_mode' => $config['display_mode'],
      '#show_view_count' => $config['show_view_count'],
      '#show_thumbnail' => $config['show_thumbnail'],
      '#full_width' => $config['full_width'],
      '#attached' => [
        'library' => [
          'saho_statistics/top-read-content',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['node_list', 'node_counter'],
        'max-age' => 3600,
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
    // Get the image field name for this content type.
    $field_name = $this->imageExtractor->findImageFieldForContentType($node->bundle());

    // Check if field exists and has a value.
    if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
      return NULL;
    }

    $field_value = $node->get($field_name)->first();
    if (!$field_value) {
      return NULL;
    }

    // Get the file entity.
    $file = $field_value->get('entity')->getValue();
    if (!$file) {
      return NULL;
    }

    // Get the file URI and convert to relative URL.
    $uri = $file->getFileUri();
    // Remove 'public://' and prepend '/sites/default/files/'.
    $path = str_replace('public://', '/sites/default/files/', $uri);

    return $path;
  }

}
