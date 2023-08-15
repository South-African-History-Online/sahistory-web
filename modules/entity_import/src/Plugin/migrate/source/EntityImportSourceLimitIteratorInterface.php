<?php

namespace Drupal\entity_import\Plugin\migrate\source;

/**
 * Define entity import source limit iterator interface.
 */
interface EntityImportSourceLimitIteratorInterface {

  /**
   * An iterator that needs to be limited.
   *
   * @return \Iterator
   */
  public function limitedIterator();

  /**
   * Get the iterator limit count.
   *
   * @return $this
   */
  public function getLimitCount();

  /**
   * Get the iterator limit offset.
   *
   * @return $this
   */
  public function getLimitOffset();

  /**
   * Set the iterator limit offset.
   *
   * @param int $offset
   *   The limit offset for the iterator.
   *
   * @return $this
   */
  public function setLimitOffset($offset);

  /**
   * Set the isBatch flag.
   *
   * @param bool $is_batch
   *   Is the operation being processed as a batch.
   *
   * @return $this
   */
  public function setBatch($is_batch = TRUE);

  /**
   * Reset the base iterator.
   *
   * @return $this
   *   The entity import csv source plugin.
   */
  public function resetBaseIterator();

  /**
   * Get the iterator max count.
   *
   * @return int
   *   The limit iterator count.
   */
  public function getLimitIteratorCount();
}
