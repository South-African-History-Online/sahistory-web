<?php

namespace Drupal\wall_of_champions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Wall of Champions page.
 */
class WallOfChampionsController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a WallOfChampionsController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Get champions data.
   *
   * @param int|null $limit
   *   Optional limit for number of champions to return.
   *
   * @return array
   *   Array of champion data.
   */
  public function getChampions($limit = NULL) {
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

    if ($limit) {
      $query->range(0, $limit);
    }

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
   * Renders the Wall of Champions page.
   *
   * @return array
   *   Render array.
   */
  public function page() {
    $champions = $this->getChampions();

    return [
      '#theme' => 'wall_of_champions_page',
      '#champions' => $champions,
      '#total_count' => count($champions),
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['url'],
        'tags' => ['commerce_subscription_list', 'user_list'],
      ],
    ];
  }

}
