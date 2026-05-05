<?php

namespace Drupal\saho_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles lightweight node view counting (replaces statistics.php).
 *
 * Accepts a POST request with the node ID, increments the counters in
 * the saho_node_counter table, and returns a 204 No Content response.
 * The endpoint is intentionally unprotected (same behaviour as the
 * Statistics module's statistics.php) because the data it writes is
 * non-sensitive. One-per-session deduplication is enforced in JS.
 */
class NodeViewCounterController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a NodeViewCounterController.
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
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Records a node page view.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A 204 No Content response on success, 400 on bad input.
   */
  public function record(Request $request): Response {
    // Reject non-POST requests (the route config enforces this, but be safe).
    if (!$request->isMethod('POST')) {
      return new JsonResponse(['error' => 'Method not allowed'], 405);
    }

    $nid = (int) $request->request->get('nid', 0);
    if ($nid <= 0) {
      return new JsonResponse(['error' => 'Invalid nid'], 400);
    }

    try {
      $timestamp = \Drupal::time()->getRequestTime();

      // Upsert: insert a new row or increment counters on conflict.
      $this->database->merge('saho_node_counter')
        ->key('nid', $nid)
        ->fields([
          'totalcount' => 1,
          'daycount' => 1,
          'timestamp' => $timestamp,
        ])
        ->expression('totalcount', '[totalcount] + 1')
        ->expression('daycount', '[daycount] + 1')
        ->expression('timestamp', ':ts', [':ts' => $timestamp])
        ->execute();

      // Invalidate the saho_node_counter cache tag so blocks refresh
      // on the next request after the max-age window expires naturally.
      // We do NOT proactively invalidate on every hit to avoid cache
      // stampedes; the max-age on each block provides the safety valve.
    }
    catch (\Exception $e) {
      // Log but swallow – a failed counter should never break page delivery.
      $this->getLogger('saho_statistics')->error(
        'Failed to record node view for nid @nid: @msg',
        ['@nid' => $nid, '@msg' => $e->getMessage()]
      );
    }

    return new Response('', Response::HTTP_NO_CONTENT);
  }

}
