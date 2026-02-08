<?php

declare(strict_types=1);

namespace Drupal\africa_regions\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\TaxonomyCounterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Africa Regions Block.
 *
 * Displays African regions (North, East, Southern, Central, West) with
 * content counts and featured items. Highlights the rich African history
 * content available on SAHO.
 *
 * @Block(
 *   id = "africa_regions_block",
 *   admin_label = @Translation("Africa Regions Block"),
 *   category = @Translation("All custom"),
 * )
 */
class AfricaRegionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The taxonomy counter service.
   *
   * @var \Drupal\saho_utils\Service\TaxonomyCounterService
   */
  protected TaxonomyCounterService $taxonomyCounter;

  /**
   * African regions with their countries and metadata.
   *
   * @var array
   */
  protected const REGIONS = [
    'north' => [
      'label' => 'Northern Africa',
      'description' => 'View the histories of Northern African nations',
      'color' => '#d97706',
      // Warm Amber.
      'countries' => [
        'Algeria',
        'Egypt',
        'Libya',
        'Morocco',
        'Sudan',
        'Tunisia',
      ],
      'url' => '/africa/northern-africa',
    ],
    'east' => [
      'label' => 'Eastern Africa',
      'description' => 'Discover stories from the Eastern African region',
      'color' => '#059669',
      // Emerald Green.
      'countries' => [
        'Burundi',
        'Djibouti',
        'Eritrea',
        'Ethiopia',
        'Kenya',
        'Rwanda',
        'Somalia',
        'Tanzania',
        'Uganda',
      ],
      'url' => '/africa/eastern-africa',
    ],
    'southern' => [
      'label' => 'Southern Africa',
      'description' => 'Rich histories from Southern African countries',
      'color' => '#dc2626',
      // SAHO Red.
      'countries' => [
        'Botswana',
        'Lesotho',
        'Malawi',
        'Mozambique',
        'Namibia',
        'South Africa',
        'Eswatini',
        'Zambia',
        'Zimbabwe',
      ],
      'url' => '/africa/southern-africa',
    ],
    'central' => [
      'label' => 'Central Africa',
      'description' => 'Central African nations and their heritage',
      'color' => '#7c3aed',
      // Deep Purple.
      'countries' => [
        'Angola',
        'Cameroon',
        'Central African Republic',
        'Chad',
        'Congo/Congo-Brazzaville',
        'Democratic Republic of Congo/Congo-Kinshasha',
        'Equatorial Guinea',
        'Gabon',
      ],
      'url' => '/africa/central-africa',
    ],
    'west' => [
      'label' => 'Western Africa',
      'description' => 'Western African stories and historical narratives',
      'color' => '#0891b2',
      // Cyan Blue.
      'countries' => [
        'Benin',
        'Burkina Faso',
        'Cabo Verde/Cape Verde',
        'Côte d\'Ivoire/Ivory Coast',
        'Gambia',
        'Ghana',
        'Guinea',
        'Guinea-Bissau',
        'Liberia',
        'Mali',
        'Mauritania',
        'Niger',
        'Nigeria',
        'Senegal',
        'Sierra Leone',
        'Togo',
      ],
      'url' => '/africa/western-africa',
    ],
  ];

  /**
   * Constructs an AfricaRegionsBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigurationFormHelperService $config_form_helper,
    CacheHelperService $cache_helper,
    TaxonomyCounterService $taxonomy_counter,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFormHelper = $config_form_helper;
    $this->cacheHelper = $cache_helper;
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
      $container->get('saho_utils.taxonomy_counter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_title' => 'Africa',
      'intro_text' => 'Browse the histories of other African countries organised by region.',
      'display_mode' => 'grid',
      'show_content_count' => TRUE,
      'show_featured_country' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_title'] = $this->configFormHelper->buildTextInput(
      $this->t('Block Title'),
      $this->configuration['block_title'],
      $this->t('Title to display above the region cards.')
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
      $this->t('Choose how regions should be displayed.')
    );

    $form['show_content_count'] = $this->configFormHelper->buildFeatureToggle(
      $this->t('Content Count'),
      $this->configuration['show_content_count'],
      $this->t('Show the number of articles available for each region.')
    );

    $form['show_featured_country'] = $this->configFormHelper->buildFeatureToggle(
      $this->t('Featured Country'),
      $this->configuration['show_featured_country'],
      $this->t('Display a featured country from each region.')
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
    $this->configuration['show_content_count'] = $form_state->getValue('show_content_count');
    $this->configuration['show_featured_country'] = $form_state->getValue('show_featured_country');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $regions = $this->getRegionsData();

    return [
      '#theme' => 'africa_regions_block',
      '#regions' => $regions,
      '#block_title' => $config['block_title'],
      '#intro_text' => $config['intro_text'],
      '#display_mode' => $config['display_mode'],
      '#show_content_count' => $config['show_content_count'],
      '#show_featured_country' => $config['show_featured_country'],
      '#attached' => [
        'library' => ['africa_regions/africa_regions'],
      ],
      '#cache' => $this->cacheHelper->buildNodeListCache('article', ['taxonomy_term_list'], 3600),
    ];
  }

  /**
   * Get region data with counts and featured content.
   *
   * @return array
   *   Array of region data.
   */
  protected function getRegionsData(): array {
    $config = $this->getConfiguration();
    $regions = [];

    foreach (self::REGIONS as $region_id => $region_info) {
      // Get country term IDs for this region.
      $country_tids = $this->getCountryTermIds($region_info['countries']);

      if (empty($country_tids)) {
        continue;
      }

      // Count articles referencing countries in this region.
      $count = $this->countArticlesForRegion($country_tids);

      // Get featured country if enabled.
      $featured_country = NULL;
      if ($config['show_featured_country'] && $count > 0) {
        $featured_country = $this->getFeaturedCountryForRegion($country_tids);
      }

      $regions[] = [
        'id' => $region_id,
        'label' => $region_info['label'],
        'description' => $region_info['description'],
        'color' => $region_info['color'],
        'count' => $count,
        'featured_country' => $featured_country,
        'url' => $region_info['url'],
        'countries' => $region_info['countries'],
      ];
    }

    return $regions;
  }

  /**
   * Get term IDs for country names.
   *
   * @param array $country_names
   *   Array of country names.
   *
   * @return array
   *   Array of term IDs.
   */
  protected function getCountryTermIds(array $country_names): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => 'african_country',
      'name' => $country_names,
    ]);

    return array_keys($terms);
  }

  /**
   * Count articles for countries in a region.
   *
   * @param array $country_tids
   *   Array of country term IDs.
   *
   * @return int
   *   Total article count.
   */
  protected function countArticlesForRegion(array $country_tids): int {
    $total = 0;

    foreach ($country_tids as $tid) {
      $total += $this->taxonomyCounter->countNodesByTerm(
        (int) $tid,
        [],
        ['field_african_country']
      );
    }

    return $total;
  }

  /**
   * Get featured country for a region.
   *
   * @param array $country_tids
   *   Array of country term IDs.
   *
   * @return string|null
   *   Featured country name or NULL.
   */
  protected function getFeaturedCountryForRegion(array $country_tids): ?string {
    // Find the country with the most content.
    $max_count = 0;
    $featured_tid = NULL;

    foreach ($country_tids as $tid) {
      $count = $this->taxonomyCounter->countNodesByTerm(
        (int) $tid,
        [],
        ['field_african_country']
      );

      if ($count > $max_count) {
        $max_count = $count;
        $featured_tid = $tid;
      }
    }

    if ($featured_tid) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($featured_tid);
      return $term ? $term->label() : NULL;
    }

    return NULL;
  }

}
