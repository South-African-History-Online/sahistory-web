<?php

namespace Drupal\wall_of_champions\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\wall_of_champions\Service\ChampionWallService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Wall of Champions' Block.
 *
 * @Block(
 *   id = "wall_of_champions_block",
 *   admin_label = @Translation("Wall of Champions Block"),
 *   category = @Translation("All custom"),
 * )
 */
class WallOfChampionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The champion wall service.
   *
   * @var \Drupal\wall_of_champions\Service\ChampionWallService
   */
  protected ChampionWallService $championWall;

  /**
   * The cache helper service.
   *
   * @var \Drupal\saho_utils\Service\CacheHelperService
   */
  protected $cacheHelper;

  /**
   * Constructs a WallOfChampionsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\wall_of_champions\Service\ChampionWallService $champion_wall
   *   The champion wall service.
   * @param \Drupal\saho_utils\Service\CacheHelperService $cache_helper
   *   The cache helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ChampionWallService $champion_wall,
    CacheHelperService $cache_helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->championWall = $champion_wall;
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
      $container->get('wall_of_champions.champion_wall'),
      $container->get('saho_utils.cache_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_count' => 6,
      'sort_order' => 'recent',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['display_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of champions to display'),
      '#options' => [
        3 => $this->t('3 champions'),
        6 => $this->t('6 champions'),
        9 => $this->t('9 champions'),
        12 => $this->t('12 champions'),
      ],
      '#default_value' => $this->configuration['display_count'],
      '#description' => $this->t('Select how many champions to show in this block.'),
    ];

    $form['sort_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort order'),
      '#options' => [
        'recent' => $this->t('Recent Champions (default)'),
        'alphabetical' => $this->t('Alphabetical (A-Z)'),
        'random' => $this->t('Random'),
      ],
      '#default_value' => $this->configuration['sort_order'],
      '#description' => $this->t('Select how to sort the champions. Recent Champions shows the newest champions first.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['display_count'] = $form_state->getValue('display_count');
    $this->configuration['sort_order'] = $form_state->getValue('sort_order');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $limit = $this->configuration['display_count'];
    $sort_order = $this->configuration['sort_order'] ?? 'recent';
    $champions = $this->championWall->getChampions($limit, $sort_order);

    // Random ordering must not be cached for long, or every visitor sees
    // the same "random" set.
    $cache_max_age = ($sort_order === 'random') ? 300 : 3600;
    $cache = $this->cacheHelper->buildStandardCache(
      'wall_of_champions_block',
      $this->configuration,
      $cache_max_age
    );
    $cache = $this->cacheHelper->addCacheTags($cache, ['user_list', 'commerce_order_list']);

    return [
      '#theme' => 'wall_of_champions_block',
      '#champions' => $champions,
      '#total_count' => $this->championWall->getTotalCount(),
      '#view_all_url' => Url::fromRoute('wall_of_champions.page')->toString(),
      '#cache' => $cache,
    ];
  }

}
