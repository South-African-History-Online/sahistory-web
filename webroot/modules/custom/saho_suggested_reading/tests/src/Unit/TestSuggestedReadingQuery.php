<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_suggested_reading\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\saho_suggested_reading\SuggestedReadingQuery;

/**
 * Test double that substitutes fixture nids for the SQL layer.
 *
 * QueryNids() is the only override: the dynamic select builder is not
 * worth mocking, and everything else under test is the caching contract.
 */
class TestSuggestedReadingQuery extends SuggestedReadingQuery {

  /**
   * Normalized conditions received by each queryNids() invocation.
   *
   * @var array
   */
  public array $queryCalls = [];

  /**
   * Constructs the test double.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache bin.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param array $fixtureNids
   *   Nids queryNids() returns on every invocation.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache,
    TimeInterface $time,
    private readonly array $fixtureNids,
  ) {
    parent::__construct($database, $entity_type_manager, $cache, $time);
  }

  /**
   * {@inheritdoc}
   */
  protected function queryNids(array $conditions): array {
    $this->queryCalls[] = $conditions;
    return $this->fixtureNids;
  }

}
