<?php

namespace Drupal\wall_of_champions\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Wall of Champions' Block.
 *
 * @Block(
 *   id = "wall_of_champions_block",
 *   admin_label = @Translation("Wall of Champions Block"),
 *   category = @Translation("SAHO"),
 * )
 */
class WallOfChampionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a WallOfChampionsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_count' => 6,
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['display_count'] = $form_state->getValue('display_count');
  }

  /**
   * Get champions data (reuses controller logic).
   *
   * @param int $limit
   *   Number of champions to return.
   *
   * @return array
   *   Array of champion data.
   */
  protected function getChampions($limit) {
    // Check if first_name and last_name fields exist.
    $has_name_fields = $this->database->schema()->tableExists('user__field_first_name')
      && $this->database->schema()->tableExists('user__field_last_name');

    $query = $this->database->select('commerce_subscription', 'cs');
    $query->join('commerce_product_variation', 'cpv', 'cs.purchased_entity = cpv.variation_id');
    $query->join('users_field_data', 'u', 'cs.uid = u.uid');
    $query->join('user__field_champion_wall_opt_in', 'opt', 'u.uid = opt.entity_id');

    // Only join name fields if they exist.
    if ($has_name_fields) {
      $query->leftJoin('user__field_first_name', 'fn', 'u.uid = fn.entity_id');
      $query->leftJoin('user__field_last_name', 'ln', 'u.uid = ln.entity_id');
    }

    $query->leftJoin('user__field_champion_testimonial', 'test', 'u.uid = test.entity_id');

    $query->fields('u', ['uid', 'name', 'created']);

    // Only add name fields if they exist.
    if ($has_name_fields) {
      $query->addField('fn', 'field_first_name_value', 'first_name');
      $query->addField('ln', 'field_last_name_value', 'last_name');
    }

    $query->addField('test', 'field_champion_testimonial_value', 'testimonial');
    $query->addField('cs', 'starts', 'member_since');

    $query->condition('cs.state', 'active');
    $query->condition('cpv.type', 'champion_membership');
    $query->condition('u.status', 1);
    $query->condition('opt.field_champion_wall_opt_in_value', 1);

    $query->distinct();
    $query->orderBy('cs.starts', 'DESC');
    $query->range(0, $limit);

    $results = $query->execute()->fetchAll();

    $champions = [];
    foreach ($results as $row) {
      // Build display name.
      $display_name = '';
      if (!empty($row->first_name) || !empty($row->last_name)) {
        $display_name = trim($row->first_name . ' ' . $row->last_name);
      }
      else {
        $display_name = $row->name;
      }

      // Sanitize and truncate testimonial.
      $testimonial = '';
      if (!empty($row->testimonial)) {
        $testimonial = strip_tags($row->testimonial);
        if (mb_strlen($testimonial) > 250) {
          $testimonial = mb_substr($testimonial, 0, 250) . '...';
        }
      }

      $champions[] = [
        'uid' => $row->uid,
        'display_name' => $display_name,
        'testimonial' => $testimonial,
        'member_since' => $row->member_since,
      ];
    }

    return $champions;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $limit = $this->configuration['display_count'];
    $champions = $this->getChampions($limit);

    // Get total count for context.
    $total_query = $this->database->select('commerce_subscription', 'cs');
    $total_query->join('commerce_product_variation', 'cpv', 'cs.purchased_entity = cpv.variation_id');
    $total_query->join('users_field_data', 'u', 'cs.uid = u.uid');
    $total_query->join('user__field_champion_wall_opt_in', 'opt', 'u.uid = opt.entity_id');
    $total_query->condition('cs.state', 'active');
    $total_query->condition('cpv.type', 'champion_membership');
    $total_query->condition('u.status', 1);
    $total_query->condition('opt.field_champion_wall_opt_in_value', 1);
    $total_query->addExpression('COUNT(DISTINCT cs.uid)', 'total');
    $total_count = $total_query->execute()->fetchField();

    return [
      '#theme' => 'wall_of_champions_block',
      '#champions' => $champions,
      '#total_count' => $total_count,
      '#view_all_url' => Url::fromRoute('wall_of_champions.page')->toString(),
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['url'],
        'tags' => ['commerce_subscription_list', 'user_list'],
      ],
    ];
  }

}
