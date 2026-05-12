<?php

namespace Drupal\saho_statistics\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Flood\FloodInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles lightweight node view counting (replaces statistics.php).
 *
 * Accepts a POST with the node ID, increments saho_node_counter, returns 204.
 *
 * The endpoint is anonymously accessible (the JS beacon fires for every
 * full-mode node view, including cached pages). Defence in depth:
 *  - The nid must reference a published node — unknown nids return 404 and
 *    never reach merge(), so the counter table cannot be polluted.
 *  - Server-side flood control caps writes per (client IP, nid) — JS-only
 *    session dedup is not enough on an open endpoint.
 *  - After a successful merge, the saho_node_counter cache tag is
 *    invalidated so dependent caches (TopReadContentBlock, TermTracker)
 *    pick up the new count on the next request.
 */
class NodeViewCounterController extends ControllerBase {

  /**
   * Flood event identifier for node-view writes.
   */
  protected const FLOOD_EVENT = 'saho_statistics.node_view';

  /**
   * Maximum writes per (client IP, nid) within FLOOD_WINDOW.
   */
  protected const FLOOD_THRESHOLD = 5;

  /**
   * Flood window in seconds.
   */
  protected const FLOOD_WINDOW = 3600;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The flood control service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a NodeViewCounterController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood control service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(
    Connection $database,
    TimeInterface $time,
    FloodInterface $flood,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
  ) {
    $this->database = $database;
    $this->time = $time;
    $this->flood = $flood;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('datetime.time'),
      $container->get('flood'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * Records a node page view.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   204 No Content on success or when flood-limited, 400 on bad input,
   *   404 when the nid doesn't reference a published node.
   */
  public function record(Request $request): Response {
    // The route restricts to POST, but be defensive.
    if (!$request->isMethod('POST')) {
      return new JsonResponse(['error' => 'Method not allowed'], 405);
    }

    $nid = (int) $request->request->get('nid', 0);
    if ($nid <= 0) {
      return new JsonResponse(['error' => 'Invalid nid'], 400);
    }

    // Reject unknown / unpublished nids so the counter table never accrues
    // rows that don't reference real published content. Direct DB query —
    // cheaper than loading the full node entity for this hot path.
    $exists = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.nid', $nid)
      ->condition('n.status', 1)
      ->range(0, 1)
      ->execute()
      ->fetchField();
    if (!$exists) {
      return new JsonResponse(['error' => 'Unknown nid'], 404);
    }

    // Cap writes per (IP, nid). Silently 204 once the cap is hit so an
    // abusive client gets no signal that the count stopped incrementing.
    $flood_id = $request->getClientIp() . ':' . $nid;
    if (!$this->flood->isAllowed(self::FLOOD_EVENT, self::FLOOD_THRESHOLD, self::FLOOD_WINDOW, $flood_id)) {
      return new Response('', Response::HTTP_NO_CONTENT);
    }
    $this->flood->register(self::FLOOD_EVENT, self::FLOOD_WINDOW, $flood_id);

    try {
      $timestamp = $this->time->getRequestTime();
      // Upsert. fields() supplies the INSERT case (counts start at 1).
      // expression() overrides totalcount/daycount on UPDATE so each
      // subsequent merge increments rather than overwrites.
      $this->database->merge('saho_node_counter')
        ->key('nid', $nid)
        ->fields([
          'totalcount' => 1,
          'daycount' => 1,
          'timestamp' => $timestamp,
        ])
        ->expression('totalcount', '[totalcount] + 1')
        ->expression('daycount', '[daycount] + 1')
        ->execute();

      // Bust dependent caches (TopReadContentBlock, TermTracker results).
      $this->cacheTagsInvalidator->invalidateTags(['saho_node_counter']);
    }
    catch (\Exception $e) {
      // Log but swallow — a failed counter must never break page delivery.
      $this->getLogger('saho_statistics')->error(
        'Failed to record node view for nid @nid: @msg',
        ['@nid' => $nid, '@msg' => $e->getMessage()]
      );
    }

    return new Response('', Response::HTTP_NO_CONTENT);
  }

}
