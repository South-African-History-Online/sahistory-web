<?php

declare(strict_types=1);

namespace Drupal\educational_resources\Plugin\Block;

use Drupal\taxonomy\TermInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\saho_utils\Service\TaxonomyCounterService;
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
   * The taxonomy counter service.
   *
   * @var \Drupal\saho_utils\Service\TaxonomyCounterService
   */
  protected TaxonomyCounterService $taxonomyCounter;

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
      'term_name' => 'CAPS Document',
      'vocabulary' => 'field_media_library_type',
      'field' => 'field_media_library_type',
      'bundles' => [],
    ],
    'books' => [
      'label' => 'Life Orientation and School Books',
      'description' => 'Educational books and reading materials',
      'keywords' => ['book', 'textbook', 'reading'],
      'icon' => 'fa-book',
      // Slate Blue.
      'color' => '#3a4a64',
      'term_name' => 'School book',
      'vocabulary' => 'field_media_library_type',
      'field' => 'field_media_library_type',
      'bundles' => [],
    ],
    'aids' => [
      'label' => 'Aids & Resources',
      'description' => 'Educational aids, teaching tools and classroom resources',
      'keywords' => ['aids', 'resources', 'teaching tools'],
      'icon' => 'fa-toolbox',
      // Faded Brick Red.
      'color' => '#8b2331',
      'term_name' => 'Aids & Resources',
      'vocabulary' => 'field_classroom_categories',
      'field' => 'field_classroom_categories',
      'bundles' => [],
    ],
    'technical' => [
      'label' => 'Technical Skills',
      'description' => 'Technical and vocational education resources',
      'keywords' => ['technical', 'skills', 'vocational'],
      'icon' => 'fa-cog',
      // Deep Teal.
      'color' => '#2c7a7b',
      'term_name' => 'Technical skills',
      'vocabulary' => 'field_classroom_categories',
      'field' => 'field_classroom_categories',
      'bundles' => [],
    ],
    'teaching' => [
      'label' => 'Lecture Materials',
      'description' => 'Lectures and teaching presentations',
      'keywords' => ['teaching', 'lesson', 'educator'],
      'icon' => 'fa-chalkboard-teacher',
      // Muted Gold.
      'color' => '#b88a2e',
      'term_name' => 'Lecture',
      'vocabulary' => 'field_media_library_type',
      'field' => 'field_media_library_type',
      'bundles' => [],
    ],
    'policy' => [
      'label' => 'Policy Documents',
      'description' => 'Educational policies and guidelines',
      'keywords' => ['policy', 'regulation', 'guideline'],
      'icon' => 'fa-gavel',
      // Lighter Slate.
      'color' => '#4a5a74',
      'term_name' => 'Official Document - Policy documents',
      'vocabulary' => 'field_media_library_type',
      'field' => 'field_media_library_type',
      'bundles' => [],
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
   * @param \Drupal\saho_utils\Service\TaxonomyCounterService $taxonomy_counter
   *   The taxonomy counter service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigurationFormHelperService $config_form_helper,
    CacheHelperService $cache_helper,
    ImageExtractorService $image_extractor,
    TaxonomyCounterService $taxonomy_counter,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFormHelper = $config_form_helper;
    $this->cacheHelper = $cache_helper;
    $this->imageExtractor = $image_extractor;
    $this->taxonomyCounter = $taxonomy_counter;
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
      $container->get('saho_utils.taxonomy_counter'),
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

      // Load the taxonomy term.
      $vocabulary = $type_info['vocabulary'] ?? 'field_media_library_type';
      $field = $type_info['field'] ?? 'field_media_library_type';
      $bundles = $type_info['bundles'] ?? [];

      $term = $this->loadTermByName($type_info['term_name'], $vocabulary);
      if (!$term) {
        continue;
      }

      // Count nodes tagged with this term using the counter service.
      $count = $this->taxonomyCounter->countNodesByTerm(
        (int) $term->id(),
        $bundles,
        [$field]
      );

      // Get featured item if enabled.
      $featured_item = NULL;
      if ($show_featured) {
        $featured_entity = $this->taxonomyCounter->getRecentEntity(
          (int) $term->id(),
          'node',
          $bundles,
          [$field]
        );
        $featured_item = $featured_entity ? $featured_entity->label() : NULL;
      }

      $resources[] = [
        'id' => $type_id,
        'label' => $type_info['label'],
        'description' => $type_info['description'],
        'icon' => $type_info['icon'],
        'color' => $type_info['color'],
        'count' => $count,
        'featured_item' => $featured_item,
        'url' => $term->toUrl()->toString(),
      ];
    }

    return $resources;
  }

  /**
   * Load a taxonomy term by name.
   *
   * @param string $term_name
   *   The term name to search for.
   * @param string $vocabulary
   *   The vocabulary ID.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The loaded term or NULL.
   */
  protected function loadTermByName(string $term_name, string $vocabulary): ?TermInterface {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $vocabulary,
      'name' => $term_name,
    ]);

    return !empty($terms) ? reset($terms) : NULL;
  }

}
