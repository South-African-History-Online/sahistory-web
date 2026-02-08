<?php

declare(strict_types=1);

namespace Drupal\history_classroom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a History Classroom Block.
 *
 * Displays educational content organized by grade level (Grades 4-12)
 * with visual grade cards showing resource counts and featured topics.
 *
 * @Block(
 *   id = "history_classroom_block",
 *   admin_label = @Translation("History Classroom Block"),
 *   category = @Translation("All custom"),
 * )
 */
class HistoryClassroomBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Grade colors using SAHO heritage palette.
   *
   * Colors derived from SAHO brand guidelines for professional,
   * academic presentation while maintaining visual distinction.
   *
   * @var array
   */
  protected const GRADE_COLORS = [
    4 => '#990000',  // Deep Heritage Red.
    5 => '#B22222',  // Firebrick Red.
    6 => '#8b2331',  // Faded Brick Red.
    7 => '#8B0000',  // Dark Red.
    8 => '#3a4a64',  // Slate Blue.
    9 => '#4a5a74',  // Lighter Slate.
    10 => '#b88a2e',  // Muted Gold.
    11 => '#c89a3e',  // Lighter Gold.
    12 => '#8B6914',  // Dark Gold.
  ];

  /**
   * Constructs a HistoryClassroomBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
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
      'display_mode' => 'grid',
      'grades_to_show' => 'all',
      'show_content_count' => TRUE,
      'show_featured_topic' => TRUE,
      'block_title' => 'History by Grade',
      'intro_text' => 'South African History curriculum resources for grades 4-12',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_title'] = $this->configFormHelper->buildTextInput(
      $this->t('Block Title'),
      $this->configuration['block_title'],
      $this->t('Title to display above the grade cards.')
    );

    $form['intro_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Introduction Text'),
      '#description' => $this->t('Brief description shown below the title.'),
      '#default_value' => $this->configuration['intro_text'],
      '#rows' => 2,
    ];

    $form['display_mode'] = $this->configFormHelper->buildDisplayModeSelect(
      $this->configuration['display_mode'],
      [
        'grid' => $this->t('Grid (cards)'),
        'carousel' => $this->t('Carousel (mobile-friendly)'),
        'list' => $this->t('Stacked List'),
      ],
      $this->t('Display Mode'),
      $this->t('How to display the grade cards.')
    );

    $form['grades_to_show'] = [
      '#type' => 'select',
      '#title' => $this->t('Grades to Display'),
      '#description' => $this->t('Which grades to show in the block.'),
      '#options' => [
        'all' => $this->t('All Grades (4-12)'),
        'primary' => $this->t('Primary Grades (4-7)'),
        'secondary' => $this->t('Secondary Grades (8-12)'),
        'high_school' => $this->t('High School (10-12)'),
      ],
      '#default_value' => $this->configuration['grades_to_show'],
    ];

    $form['show_content_count'] = $this->configFormHelper->buildFeatureToggle(
      'Content Count',
      $this->configuration['show_content_count'],
      $this->t('Show the number of resources available for each grade.')
    );

    $form['show_featured_topic'] = $this->configFormHelper->buildFeatureToggle(
      'Featured Topic',
      $this->configuration['show_featured_topic'],
      $this->t('Show a featured or recent topic for each grade.')
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
    $this->configuration['grades_to_show'] = $form_state->getValue('grades_to_show');
    $this->configuration['show_content_count'] = $form_state->getValue('show_content_count');
    $this->configuration['show_featured_topic'] = $form_state->getValue('show_featured_topic');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $grades = $this->getGradesData();

    // Build cache array using CacheHelperService.
    $cache = $this->cacheHelper->buildNodeListCache('article');
    $cache = $this->cacheHelper->addCacheTags($cache, ['taxonomy_term_list:classroom']);

    return [
      '#theme' => 'history_classroom_block',
      '#grades' => $grades,
      '#block_title' => $this->configuration['block_title'],
      '#intro_text' => $this->configuration['intro_text'],
      '#display_mode' => $this->configuration['display_mode'],
      '#show_content_count' => $this->configuration['show_content_count'],
      '#show_featured_topic' => $this->configuration['show_featured_topic'],
      '#attached' => [
        'library' => ['history_classroom/history_classroom'],
      ],
      '#cache' => $cache,
    ];
  }

  /**
   * Get grades data with content counts and featured topics.
   *
   * @return array
   *   Array of grade data.
   */
  protected function getGradesData(): array {
    $grades_to_show = $this->configuration['grades_to_show'];
    $show_featured = $this->configuration['show_featured_topic'];

    // Define grade ranges based on configuration.
    $grade_numbers = match ($grades_to_show) {
      'primary' => [4, 5, 6, 7],
      'secondary' => [8, 9, 10, 11, 12],
      'high_school' => [10, 11, 12],
      default => [4, 5, 6, 7, 8, 9, 10, 11, 12],
    };

    // Load all classroom terms.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties(['vid' => 'classroom']);

    $grades = [];

    foreach ($terms as $term) {
      // Extract grade number from term name (e.g., "History Classroom Grade Eight" -> 8).
      $grade_number = $this->extractGradeNumber($term->getName());

      if (!in_array($grade_number, $grade_numbers, TRUE)) {
        continue;
      }

      // Count articles for this grade.
      $count = $this->countArticlesForGrade((int) $term->id());

      // Get featured topic if enabled.
      $featured_topic = NULL;
      if ($show_featured) {
        $featured_topic = $this->getFeaturedTopicForGrade((int) $term->id());
      }

      $grades[] = [
        'number' => $grade_number,
        'tid' => $term->id(),
        'name' => $term->getName(),
        'label' => $this->t('Grade @number', ['@number' => $grade_number]),
        'count' => $count,
        'featured_topic' => $featured_topic,
        'url' => $term->toUrl()->toString(),
        'color' => self::GRADE_COLORS[$grade_number] ?? '#6C757D',
      ];
    }

    // Sort by grade number.
    usort($grades, fn($a, $b) => $a['number'] <=> $b['number']);

    return $grades;
  }

  /**
   * Extract grade number from term name.
   *
   * @param string $term_name
   *   The term name (e.g., "History Classroom Grade Eight").
   *
   * @return int
   *   The grade number.
   */
  protected function extractGradeNumber(string $term_name): int {
    $grade_map = [
      'Four' => 4,
      'Five' => 5,
      'Six' => 6,
      'Seven' => 7,
      'Eight' => 8,
      'Nine' => 9,
      'Ten' => 10,
      'Eleven' => 11,
      'Twelve' => 12,
    ];

    foreach ($grade_map as $word => $number) {
      if (stripos($term_name, $word) !== FALSE) {
        return $number;
      }
    }

    return 0;
  }

  /**
   * Count articles for a specific grade.
   *
   * @param int $term_id
   *   The taxonomy term ID.
   *
   * @return int
   *   The article count.
   */
  protected function countArticlesForGrade(int $term_id): int {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_classroom', $term_id)
      ->accessCheck(TRUE)
      ->count();

    return (int) $query->execute();
  }

  /**
   * Get featured topic for a grade.
   *
   * @param int $term_id
   *   The taxonomy term ID.
   *
   * @return string|null
   *   The featured topic title or NULL.
   */
  protected function getFeaturedTopicForGrade(int $term_id): ?string {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_classroom', $term_id)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 1);

    $nids = $query->execute();

    if (!empty($nids)) {
      $nid = reset($nids);
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if ($node) {
        return $node->label();
      }
    }

    return NULL;
  }

}
