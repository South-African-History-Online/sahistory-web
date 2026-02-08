<?php

declare(strict_types=1);

namespace Drupal\educational_resources\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Educational Resources Block.
 *
 * Displays educational resources organized by type (CAPS documents,
 * school books, AIDS resources, etc.) with visual resource cards.
 *
 * @Block(
 *   id = "educational_resources_block",
 *   admin_label = @Translation("Educational Resources Block"),
 *   category = @Translation("All custom"),
 * )
 */
class EducationalResourcesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The configuration form helper service.
   *
   * @var \Drupal\saho_utils\Service\ConfigurationFormHelperService
   */
  protected ConfigurationFormHelperService $configFormHelper;

  /**
   * The cache helper service.
   *
   * @var \Drupal\saho_utils\Service\CacheHelperService
   */
  protected CacheHelperService $cacheHelper;

  /**
   * The image extractor service.
   *
   * @var \Drupal\saho_utils\Service\ImageExtractorService
   */
  protected ImageExtractorService $imageExtractor;

  /**
   * Resource types with metadata using SAHO heritage colors.
   *
   * @var array
   */
  protected const RESOURCE_TYPES = [
    'caps' => [
      'label' => 'CAPS Documents',
      'description' => 'Curriculum Assessment Policy Statements',
      'keywords' => ['CAPS', 'curriculum', 'assessment policy'],
      'icon' => 'fa-file-alt',
  // Deep Heritage Red.
      'color' => '#990000',
    ],
    'books' => [
      'label' => 'School Books',
      'description' => 'Educational books and reading materials',
      'keywords' => ['book', 'textbook', 'reading'],
      'icon' => 'fa-book',
    // Slate Blue.
      'color' => '#3a4a64',
    ],
    'aids' => [
      'label' => 'AIDS Resources',
      'description' => 'HIV/AIDS education and awareness materials',
      'keywords' => ['AIDS', 'HIV', 'health'],
      'icon' => 'fa-heartbeat',
    // Faded Brick Red.
      'color' => '#8b2331',
    ],
    'teaching' => [
      'label' => 'Teaching Materials',
      'description' => 'Lesson plans and teaching resources',
      'keywords' => ['teaching', 'lesson', 'educator'],
      'icon' => 'fa-chalkboard-teacher',
    // Muted Gold.
      'color' => '#b88a2e',
    ],
    'policy' => [
      'label' => 'Policy Documents',
      'description' => 'Educational policies and guidelines',
      'keywords' => ['policy', 'regulation', 'guideline'],
      'icon' => 'fa-gavel',
    // Lighter Slate.
      'color' => '#4a5a74',
    ],
  ];

  /**
   * Constructs an EducationalResourcesBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\saho_utils\Service\ConfigurationFormHelperService $config_form_helper
   *   The configuration form helper service.
   * @param \Drupal\saho_utils\Service\CacheHelperService $cache_helper
   *   The cache helper service.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $image_extractor
   *   The image extractor service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigurationFormHelperService $config_form_helper,
    CacheHelperService $cache_helper,
    ImageExtractorService $image_extractor,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFormHelper = $config_form_helper;
    $this->cacheHelper = $cache_helper;
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
      $container->get('saho_utils.config_form_helper'),
      $container->get('saho_utils.cache_helper'),
      $container->get('saho_utils.image_extractor'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_title' => 'Educational Resources',
      'intro_text' => 'Explore our collection of educational materials, documents, and resources.',
      'display_mode' => 'grid',
      'resources_to_show' => 'all',
      'show_content_count' => TRUE,
      'show_featured_item' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_title'] = $this->configFormHelper->buildTextInput(
      $this->t('Block Title'),
      $this->configuration['block_title'],
      $this->t('Title to display above the resource cards.')
    );

    $form['intro_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Introduction Text'),
      '#default_value' => $this->configuration['intro_text'],
      '#description' => $this->t('Brief description shown below the title.'),
      '#rows' => 3,
    ];

    $form['display_mode'] = $this->configFormHelper->buildDisplayModeSelect(
      $this->configuration['display_mode'],
      [
        'grid' => $this->t('Grid (Responsive Cards)'),
        'carousel' => $this->t('Carousel (Slideshow)'),
        'list' => $this->t('List (Stacked)'),
      ],
      $this->t('Display Mode'),
      $this->t('Choose how resource types should be displayed.')
    );

    $form['resources_to_show'] = [
      '#type' => 'select',
      '#title' => $this->t('Resources to Display'),
      '#options' => [
        'all' => $this->t('All Resource Types'),
        'docs' => $this->t('Documents Only (CAPS, Policy)'),
        'materials' => $this->t('Materials Only (Books, Teaching)'),
      ],
      '#default_value' => $this->configuration['resources_to_show'],
      '#description' => $this->t('Filter which resource types to display.'),
    ];

    $form['show_content_count'] = $this->configFormHelper->buildFeatureToggle(
      $this->t('Content Count'),
      $this->configuration['show_content_count'],
      $this->t('Show the number of articles available for each resource type.')
    );

    $form['show_featured_item'] = $this->configFormHelper->buildFeatureToggle(
      $this->t('Featured Item'),
      $this->configuration['show_featured_item'],
      $this->t('Display a recent or featured article for each resource type.')
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['block_title'] = $form_state->getValue('block_title');
    $this->configuration['intro_text'] = $form_state->getValue('intro_text');
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['resources_to_show'] = $form_state->getValue('resources_to_show');
    $this->configuration['show_content_count'] = $form_state->getValue('show_content_count');
    $this->configuration['show_featured_item'] = $form_state->getValue('show_featured_item');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $resources = $this->getResourcesData();

    return [
      '#theme' => 'educational_resources_block',
      '#resources' => $resources,
      '#block_title' => $config['block_title'],
      '#intro_text' => $config['intro_text'],
      '#display_mode' => $config['display_mode'],
      '#show_content_count' => $config['show_content_count'],
      '#show_featured_item' => $config['show_featured_item'],
      '#attached' => [
        'library' => ['educational_resources/educational_resources'],
      ],
      '#cache' => $this->cacheHelper->buildNodeListCache('article', ['taxonomy_term_list'], 3600),
    ];
  }

  /**
   * Get resource data for all configured resource types.
   *
   * @return array
   *   Array of resource data.
   */
  protected function getResourcesData(): array {
    $config = $this->getConfiguration();
    $resources = [];
    $show_count = $config['show_content_count'];
    $show_featured = $config['show_featured_item'];
    $filter = $config['resources_to_show'];

    foreach (self::RESOURCE_TYPES as $type_id => $type_info) {
      // Apply filter.
      if ($filter === 'docs' && !in_array($type_id, ['caps', 'policy'], TRUE)) {
        continue;
      }
      if ($filter === 'materials' && !in_array($type_id, ['books', 'teaching'], TRUE)) {
        continue;
      }

      // Count articles for this resource type.
      $count = $this->countArticlesForResourceType($type_info['keywords']);

      // Get featured item if enabled.
      $featured_item = NULL;
      if ($show_featured) {
        $featured_item = $this->getFeaturedItemForResourceType($type_info['keywords']);
      }

      $resources[] = [
        'id' => $type_id,
        'label' => $type_info['label'],
        'description' => $type_info['description'],
        'icon' => $type_info['icon'],
        'color' => $type_info['color'],
        'count' => $count,
        'featured_item' => $featured_item,
        'url' => '/search?keywords=' . urlencode($type_info['keywords'][0]),
      ];
    }

    return $resources;
  }

  /**
   * Count articles for a resource type.
   *
   * @param array $keywords
   *   Keywords to search for.
   *
   * @return int
   *   The article count.
   */
  protected function countArticlesForResourceType(array $keywords): int {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(TRUE);

    // Create OR condition group for keywords.
    $or_group = $query->orConditionGroup();
    foreach ($keywords as $keyword) {
      $or_group->condition('title', '%' . $keyword . '%', 'LIKE');
    }
    $query->condition($or_group);

    return (int) $query->count()->execute();
  }

  /**
   * Get featured item for a resource type.
   *
   * @param array $keywords
   *   Keywords to search for.
   *
   * @return string|null
   *   The featured item title or NULL.
   */
  protected function getFeaturedItemForResourceType(array $keywords): ?string {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 1);

    // Create OR condition group for keywords.
    $or_group = $query->orConditionGroup();
    foreach ($keywords as $keyword) {
      $or_group->condition('title', '%' . $keyword . '%', 'LIKE');
    }
    $query->condition($or_group);

    $nids = $query->execute();
    if (empty($nids)) {
      return NULL;
    }

    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    return $node ? $node->label() : NULL;
  }

}
