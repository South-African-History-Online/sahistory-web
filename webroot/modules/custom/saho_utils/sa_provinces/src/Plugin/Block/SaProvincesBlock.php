<?php

declare(strict_types=1);

namespace Drupal\sa_provinces\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a South African Provinces Block.
 *
 * Displays South Africa's 9 provinces as visual cards for exploring
 * places, cities, towns, and historical sites.
 *
 * @Block(
 *   id = "sa_provinces_block",
 *   admin_label = @Translation("South African Provinces Block"),
 *   category = @Translation("All custom"),
 * )
 */
class SaProvincesBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  protected $imageExtractor;

  /**
   * Province data with SAHO heritage colors (alphabetically ordered).
   *
   * @var array
   */
  protected const PROVINCES = [
    'eastern_cape' => [
      'name' => 'Eastern Cape',
      'description' => 'Birthplace of many anti-apartheid leaders',
      'color' => '#990000',
      'icon_letter' => 'EC',
    ],
    'free_state' => [
      'name' => 'Free State',
      'description' => 'Heart of South Africa\'s goldfields',
      'color' => '#8B0000',
      'icon_letter' => 'FS',
    ],
    'gauteng' => [
      'name' => 'Gauteng',
      'description' => 'Economic hub and Johannesburg',
      'color' => '#b88a2e',
      'icon_letter' => 'GP',
    ],
    'kwazulu_natal' => [
      'name' => 'KwaZulu-Natal',
      'description' => 'Rich Zulu heritage and coastal beauty',
      'color' => '#3a4a64',
      'icon_letter' => 'KZN',
    ],
    'limpopo' => [
      'name' => 'Limpopo',
      'description' => 'Ancient kingdoms and Mapungubwe',
      'color' => '#4a5a74',
      'icon_letter' => 'LP',
    ],
    'mpumalanga' => [
      'name' => 'Mpumalanga',
      'description' => 'Land of the rising sun',
      'color' => '#c89a3e',
      'icon_letter' => 'MP',
    ],
    'north_west' => [
      'name' => 'North West',
      'description' => 'Cradle of humankind region',
      'color' => '#8b2331',
      'icon_letter' => 'NW',
    ],
    'northern_cape' => [
      'name' => 'Northern Cape',
      'description' => 'Diamond fields and vast landscapes',
      'color' => '#B22222',
      'icon_letter' => 'NC',
    ],
    'western_cape' => [
      'name' => 'Western Cape',
      'description' => 'Cape of Good Hope and rich colonial history',
      'color' => '#8B6914',
      'icon_letter' => 'WC',
    ],
  ];

  /**
   * Constructs a SaProvincesBlock object.
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
      'block_title' => 'South African Provinces',
      'intro_text' => 'Search for the history of your city, town, suburb or village. View our Places Page to search for places of interest, monuments and centres or browse an alphabetical list.',
      'display_mode' => 'grid',
      'show_place_count' => TRUE,
      'show_featured_place' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_title'] = $this->configFormHelper->buildTextInput(
      $this->t('Block Title'),
      $this->configuration['block_title'],
      $this->t('Title to display above the province cards.')
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
      $this->t('Choose how provinces should be displayed.')
    );

    $form['show_place_count'] = $this->configFormHelper->buildFeatureToggle(
      $this->t('Place Count'),
      $this->configuration['show_place_count'],
      $this->t('Show the number of places available for each province.')
    );

    $form['show_featured_place'] = $this->configFormHelper->buildFeatureToggle(
      $this->t('Featured Place'),
      $this->configuration['show_featured_place'],
      $this->t('Display a featured place for each province.')
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
    $this->configuration['show_place_count'] = $form_state->getValue('show_place_count');
    $this->configuration['show_featured_place'] = $form_state->getValue('show_featured_place');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $provinces = $this->getProvincesData();

    return [
      '#theme' => 'sa_provinces_block',
      '#provinces' => $provinces,
      '#block_title' => $config['block_title'],
      '#intro_text' => $config['intro_text'],
      '#display_mode' => $config['display_mode'],
      '#show_place_count' => $config['show_place_count'],
      '#show_featured_place' => $config['show_featured_place'],
      '#attached' => [
        'library' => ['sa_provinces/sa_provinces'],
      ],
      '#cache' => $this->cacheHelper->buildNodeListCache('place', ['taxonomy_term_list:field_places_level3'], 3600),
    ];
  }

  /**
   * Get province data with counts and featured places.
   *
   * @return array
   *   Array of province data.
   */
  protected function getProvincesData(): array {
    $config = $this->getConfiguration();
    $provinces = [];

    foreach (self::PROVINCES as $province_id => $province_info) {
      // Load the taxonomy term for this province.
      $term = $this->loadProvinceTermByName($province_info['name']);
      if (!$term) {
        continue;
      }

      $term_id = (int) $term->id();

      // Count places for this province.
      $count = $this->countPlacesForProvince($term_id);

      // Get featured place if enabled.
      $featured_place = NULL;
      if ($config['show_featured_place']) {
        $featured_place = $this->getFeaturedPlaceForProvince($term_id);
      }

      // Get representative image for province.
      $image_url = $this->getProvinceImage($term_id);

      $provinces[] = [
        'id' => $province_id,
        'name' => $province_info['name'],
        'description' => $province_info['description'],
        'color' => $province_info['color'],
        'icon_letter' => $province_info['icon_letter'],
        'count' => $count,
        'featured_place' => $featured_place,
        'image' => $image_url,
        'url' => '/places?tid_2[' . $term_id . ']=' . $term_id,
        'tid' => $term_id,
      ];
    }

    return $provinces;
  }

  /**
   * Load a province taxonomy term by name.
   *
   * @param string $name
   *   The province name.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The taxonomy term or NULL.
   */
  protected function loadProvinceTermByName(string $name): ?object {
    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'field_places_level3',
        'name' => $name,
      ]);

    return $terms ? reset($terms) : NULL;
  }

  /**
   * Count places for a province.
   *
   * @param int $term_id
   *   The taxonomy term ID.
   *
   * @return int
   *   The place count.
   */
  protected function countPlacesForProvince(int $term_id): int {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'place')
      ->condition('status', 1)
      ->condition('field_places_level3', $term_id)
      ->accessCheck(TRUE)
      ->count();

    return (int) $query->execute();
  }

  /**
   * Get featured place for a province.
   *
   * @param int $term_id
   *   The taxonomy term ID.
   *
   * @return string|null
   *   The featured place title or NULL.
   */
  protected function getFeaturedPlaceForProvince(int $term_id): ?string {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'place')
      ->condition('status', 1)
      ->condition('field_places_level3', $term_id)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 1);

    $nids = $query->execute();
    if (empty($nids)) {
      return NULL;
    }

    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    return $node ? $node->label() : NULL;
  }

  /**
   * Get a representative image for a province.
   *
   * Loads a place node from the province and extracts its image.
   *
   * @param int $term_id
   *   The taxonomy term ID.
   *
   * @return string|null
   *   The image URL or NULL.
   */
  protected function getProvinceImage(int $term_id): ?string {
    // Find a place with an image for this province.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'place')
      ->condition('status', 1)
      ->condition('field_places_level3', $term_id)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 5);

    $nids = $query->execute();
    if (empty($nids)) {
      return NULL;
    }

    // Try to find a place with an image.
    foreach ($nids as $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if ($node) {
        $image_url = $this->imageExtractor->extractImageUrl($node, 'field_image');
        if ($image_url) {
          return $image_url;
        }
      }
    }

    return NULL;
  }

}
