<?php

namespace Drupal\glazed_helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the flippy pager service.
 */
class FlippyPager {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $entityFieldManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity query for node.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $nodeQuery;

  /**
   * The database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The flippy Settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $flippySettings;

  /**
   * Drupal Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event displatcher.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get flippy settings.
   * @param \Drupal\Core\Language\LanguageManager
   *   Drupal Language manager service.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EventDispatcherInterface $event_dispatcher, QueryFactory $query_factory, Connection $connection, ConfigFactoryInterface $config_factory, LanguageManager $languageManager) {
    $this->entityFieldManager = $entityFieldManager;
    $this->eventDispatcher = $event_dispatcher;
    $this->nodeQuery = $query_factory->get('node');
    $this->connection = $connection;
    $this->flippySettings = $config_factory->get('flippy.settings');
    $this->languageManager = $languageManager;
  }

  /**
   * Helper function: Query to get the list of flippy pagers.
   *
   * @parameter
   *   current node object
   *
   * @return array
   *   a list of flippy pagers
   */
  public function flippy_build_list($node) {
    // Get all the properties from the current node.
    $master_list = &drupal_static(__FUNCTION__);

    if (!isset($master_list)) {
      $master_list = [];
    }
    if (!isset($master_list[$node->id()])) {
      $sort = 'created';
      // Depending on order, decide what before and after means.
      $before = '<';
      $after = '>';
      // Also decide what up and down means
      $up = 'ASC';
      $down = 'DESC';

      // Create a starting-point EntityQuery object.
      $query = $this->nodeQuery;
      $query->condition('type', $node->getType())
        ->condition('status', 1)
        ->condition('nid', $node->id(), '!=')
        ->addTag('node_access');

      // Create the individual queries
      $prev = clone $query;
      $next = clone $query;

      // Otherwise we assume the variable is a column in the base table
      // (a property). Like above, set the conditions.
      $sort_value = $node->get($sort);
      $sort_value = $sort_value->getValue();

      // Previous query to find out the previous item based on the field,
      // using node id if the other criteria is the same.
      $field_default_condition = $prev->andConditionGroup()
        ->condition($sort, $sort_value[0]['value'])
        ->condition('nid', $node->id(), $before);

      $field_sorting_group = $prev->orConditionGroup()
        ->condition($sort, $sort_value[0]['value'], $before)
        ->condition($field_default_condition);

      $prev->condition($field_sorting_group);

      // Next query to find out the next item based on the field, using
      // node id if the other criteria is the same.
      $field_default_condition = $next->andConditionGroup()
        ->condition($sort, $sort_value[0]['value'])
        ->condition('nid', $node->id(), $after);

      $field_sorting_group = $next->orConditionGroup()
        ->condition($sort, $sort_value[0]['value'], $after)
        ->condition($field_default_condition);

      $next->condition($field_sorting_group);

      // Set the ordering.
      $prev->sort($sort, $down);
      $next->sort($sort, $up);

      // Event dispatcher.
      $queries = [
        'prev' => $prev,
        'next' => $next,
      ];

      // Execute the queries.
      $results = [];

      $results['prev'] = $queries['prev']
        ->range(0, 1)
        ->execute();
      $results['prev'] = !empty($results['prev']) ? array_values($results['prev'])[0] : NULL;

      $results['next'] = $queries['next']
        ->range(0, 1)
        ->execute();
      $results['next'] = !empty($results['next']) ? array_values($results['next'])[0] : NULL;

      $node_ids = [];
      foreach ($results as $key => $result) {
        // If the query returned no results, it means we're already
        // at the beginning/end of the pager, so ignore those.
        if(is_numeric($result)){
          // Otherwise we save the node ID.
          $node_ids[$key] = (int)$result;
          continue;
        }

        if (is_array($result) && count($result) > 0) {
          // Otherwise we save the node ID.
          $node_ids[$key] = $results[$key];
        }
      }

      // Make our final array of node IDs and titles.
      $list = [];
      // but only if we actually found some matches
      if (count($node_ids) > 0) {
        foreach ($node_ids as $key => $nid) {
          $list[$key] = [
            'nid' => $nid,
          ];
        }
      }
      // Finally set the current info for themers to use.
      $list['current'] = [
        'nid' => $node->id(),
      ];

      $master_list[$node->id()] = $list;
    }
    return $master_list[$node->id()];
  }

  /**
   * Determine if the Flippy pager should be shown for the give node.
   *
   * @param $node
   *   Node to check for pager
   *
   * @return bool Boolean: TRUE if pager should be shown, FALSE if not
   */
  public function flippy_use_pager($node) {
    if (!is_object($node)) {
      return FALSE;
    }
    $types = theme_get_setting('prevnext_content_types');
    return (node_is_page($node) && (isset($types[$node->getType()])) && ($types[$node->getType()]));
  }

  /**
   * Helper function to generate link.
   *
   * @param $nodeId
   *   Target node ID.
   * @param $label
   *   Target node label.
   *
   * @return array|\mixed[]
   *   Link render array.
   */
  public function flippy_generate_link($nodeId, $label) {
    $language = $this->languageManager->getCurrentLanguage()->getId();

    $url = Url::fromRoute('entity.node.canonical');
    $url->setRouteParameter('node', $nodeId);
    $url->setOptions(['attributes' => ['class' => ['nextprev-link']]]);
    $flippyLink = Link::fromTextAndUrl($label, $url);

    return $flippyLink->toRenderable();
  }

}
