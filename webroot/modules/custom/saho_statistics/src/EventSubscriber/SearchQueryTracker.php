<?php

namespace Drupal\saho_statistics\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\ProcessingResultsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tracks search queries for analytics purposes.
 *
 * This subscriber listens to Search API events and logs queries to the
 * saho_search_queries table with privacy-focused features including IP
 * hashing and session anonymization.
 */
class SearchQueryTracker implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Temporary storage for query data between events.
   *
   * @var array
   */
  protected $tempStorage = [];

  /**
   * Constructs a SearchQueryTracker object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    Connection $database,
    AccountInterface $current_user,
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => ['onQueryPreExecute', -50],
      SearchApiEvents::PROCESSING_RESULTS => ['onProcessingResults', -50],
    ];
  }

  /**
   * Reacts to the query pre-execute event.
   *
   * Captures query information before execution.
   *
   * @param \Drupal\search_api\Event\QueryPreExecuteEvent $event
   *   The event object.
   */
  public function onQueryPreExecute(QueryPreExecuteEvent $event) {
    // Check if tracking is enabled.
    $config = $this->configFactory->get('saho_statistics.settings');
    if (!$config->get('track_searches')) {
      return;
    }

    $query = $event->getQuery();
    $keys = $query->getKeys();

    // Skip empty queries.
    if (empty($keys)) {
      return;
    }

    // Store query info temporarily (indexed by query object ID).
    $query_id = spl_object_id($query);
    $this->tempStorage[$query_id] = [
      'query_text' => is_string($keys) ? $keys : json_encode($keys),
      'index_id' => $query->getIndex()->id(),
      'filters' => json_encode($this->extractFilters($query)),
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
  }

  /**
   * Reacts to the processing results event.
   *
   * Captures result count and logs the complete query data.
   *
   * @param \Drupal\search_api\Event\ProcessingResultsEvent $event
   *   The event object.
   */
  public function onProcessingResults(ProcessingResultsEvent $event) {
    $results = $event->getResults();
    $query = $results->getQuery();
    $query_id = spl_object_id($query);

    // Check if we have stored data for this query.
    if (isset($this->tempStorage[$query_id])) {
      $data = $this->tempStorage[$query_id];
      $data['result_count'] = $results->getResultCount();
      $data['uid'] = $this->currentUser->id();
      $data['session_id'] = $this->getAnonymizedSessionId();
      $data['ip_hash'] = $this->hashIpAddress();

      try {
        $this->database->insert('saho_search_queries')
          ->fields($data)
          ->execute();
      }
      catch (\Exception $e) {
        // Log error but don't break search functionality.
        \Drupal::logger('saho_statistics')->error('Failed to log search query: @message', [
          '@message' => $e->getMessage(),
        ]);
      }

      // Clean up temporary storage.
      unset($this->tempStorage[$query_id]);
    }
  }

  /**
   * Hashes the client IP address for privacy.
   *
   * Uses SHA-256 with site UUID as salt to prevent reverse lookup.
   *
   * @return string
   *   The hashed IP address.
   */
  protected function hashIpAddress(): string {
    $request = $this->requestStack->getCurrentRequest();
    $ip = $request ? $request->getClientIp() : '';

    // Use site UUID as salt for consistent hashing.
    $salt = $this->configFactory->get('system.site')->get('uuid');

    return hash('sha256', $ip . $salt);
  }

  /**
   * Gets an anonymized session identifier.
   *
   * Returns only the first 8 characters of a hashed session ID for
   * basic session tracking without full user identification.
   *
   * @return string
   *   The anonymized session ID (8 characters).
   */
  protected function getAnonymizedSessionId(): string {
    $request = $this->requestStack->getCurrentRequest();
    $session_id = '';

    if ($request && $request->hasSession()) {
      $session_id = $request->getSession()->getId();
    }

    // Return first 8 chars of hashed session ID.
    return substr(hash('sha256', $session_id), 0, 8);
  }

  /**
   * Extracts applied filters from a search query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query.
   *
   * @return array
   *   Array of filter conditions keyed by field name.
   */
  protected function extractFilters($query): array {
    $filters = [];
    $condition_group = $query->getConditionGroup();

    if ($condition_group) {
      foreach ($condition_group->getConditions() as $condition) {
        // Conditions can be nested condition groups or individual conditions.
        if (is_array($condition) && isset($condition['field'])) {
          $filters[$condition['field']] = $condition['value'];
        }
      }
    }

    return $filters;
  }

}
