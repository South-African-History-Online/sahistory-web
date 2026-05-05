<?php

namespace Drupal\saho_statistics\Controller;

use Drupal\Component\Datetime\TimeInterface;
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
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a NodeViewCounterController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('datetime.time')
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
      $timestamp = $this->time->getRequestTime();

      // Upsert the view counters.  The fields() call covers the INSERT case
      // (new row: start counts at 1).  The expression() calls override the
      // UPDATE case for totalcount and daycount only; timestamp is set from
      // fields() in both INSERT and UPDATE paths.
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
