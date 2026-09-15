<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_utils\RegisterGrid\AfricaRegionsSource;
use Drupal\saho_utils\RegisterGrid\ClassroomGradesSource;
use Drupal\saho_utils\RegisterGrid\EducationalResourcesSource;
use Drupal\saho_utils\RegisterGrid\RegisterSourceInterface;
use Drupal\saho_utils\RegisterGrid\SaProvincesSource;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\saho_utils\Service\TaxonomyCounterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * One configurable register grid: grades, provinces, regions or resources.
 *
 * Replaces the four near-identical blocks history_classroom_block,
 * sa_provinces_block, africa_regions_block and educational_resources_block
 * (#485). The "register" setting picks the data source; everything else
 * (title, standfirst, grid/list, count and featured toggles, the archive-card
 * template) is shared.
 *
 * @Block(
 *   id = "register_grid_block",
 *   admin_label = @Translation("Register grid (grades, provinces, regions, resources)"),
 *   category = @Translation("All custom"),
 * )
 */
final class RegisterGridBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The available registers, keyed by id.
   *
   * @var \Drupal\saho_utils\RegisterGrid\RegisterSourceInterface[]
   */
  private array $sources;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    TaxonomyCounterService $taxonomy_counter,
    ImageExtractorService $image_extractor,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $sources = [
      new ClassroomGradesSource($entity_type_manager),
      new SaProvincesSource($entity_type_manager, $image_extractor),
      new AfricaRegionsSource($entity_type_manager, $taxonomy_counter),
      new EducationalResourcesSource($entity_type_manager, $taxonomy_counter),
    ];
    $this->sources = [];
    foreach ($sources as $source) {
      $this->sources[$source->id()] = $source;
    }
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
      $container->get('saho_utils.taxonomy_counter'),
      $container->get('saho_utils.image_extractor'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'register' => 'classroom_grades',
      'subset' => 'all',
      'block_title' => '',
      'intro_text' => '',
      'display_mode' => 'grid',
      'show_count' => TRUE,
      'show_featured' => TRUE,
    ];
  }

  /**
   * The configured register, falling back to the first one.
   */
  private function source(): RegisterSourceInterface {
    return $this->sources[$this->configuration['register']] ?? reset($this->sources);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $options = [];
    foreach ($this->sources as $id => $source) {
      $options[$id] = $source->label();
    }
    $form['register'] = [
      '#type' => 'select',
      '#title' => $this->t('Register'),
      '#description' => $this->t('Which catalogue the grid shows. Each card links into the matching landing or term page.'),
      '#options' => $options,
      '#default_value' => $config['register'],
      '#required' => TRUE,
    ];

    // One subset select per register that offers subsets; #states shows the
    // one matching the chosen register. Stored under a single "subset" key.
    foreach ($this->sources as $id => $source) {
      $subsets = $source->subsetOptions();
      if ($subsets === []) {
        continue;
      }
      $form['subset_' . $id] = [
        '#type' => 'select',
        '#title' => $this->t('Show'),
        '#options' => $subsets,
        '#default_value' => $config['register'] === $id ? $config['subset'] : 'all',
        '#states' => [
          'visible' => [
            ':input[name="settings[register]"]' => ['value' => $id],
          ],
        ],
      ];
    }

    $form['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading'),
      '#description' => $this->t('Leave empty to use the register name.'),
      '#default_value' => $config['block_title'],
      '#maxlength' => 255,
    ];
    $form['intro_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Standfirst'),
      '#description' => $this->t('Optional one-line introduction under the heading.'),
      '#default_value' => $config['intro_text'],
      '#rows' => 2,
    ];
    $form['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#options' => [
        'grid' => $this->t('Grid of cards'),
        'list' => $this->t('Single column list'),
      ],
      '#default_value' => $config['display_mode'],
    ];
    $form['show_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show record counts'),
      '#default_value' => $config['show_count'],
    ];
    $form['show_featured'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the featured record (newest or most populated)'),
      '#default_value' => $config['show_featured'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $register = (string) $form_state->getValue('register');
    $this->configuration['register'] = $register;
    $subset = (string) ($form_state->getValue('subset_' . $register) ?? 'all');
    $this->configuration['subset'] = $subset !== '' ? $subset : 'all';
    $this->configuration['block_title'] = (string) $form_state->getValue('block_title');
    $this->configuration['intro_text'] = (string) $form_state->getValue('intro_text');
    $this->configuration['display_mode'] = $form_state->getValue('display_mode') === 'list' ? 'list' : 'grid';
    $this->configuration['show_count'] = (bool) $form_state->getValue('show_count');
    $this->configuration['show_featured'] = (bool) $form_state->getValue('show_featured');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $source = $this->source();
    $config = $this->getConfiguration();
    $items = $source->items((string) $config['subset'], (bool) $config['show_count'], (bool) $config['show_featured']);
    return [
      '#theme' => 'register_grid_block',
      '#register' => $source->id(),
      '#items' => $items,
      '#block_title' => $config['block_title'] !== '' ? $config['block_title'] : $source->label(),
      '#intro_text' => $config['intro_text'],
      '#display_mode' => $config['display_mode'],
      '#empty_text' => $source->emptyText(),
      '#attached' => [
        'library' => ['saho/saho-card-grid'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), $this->source()->cacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 3600;
  }

}
