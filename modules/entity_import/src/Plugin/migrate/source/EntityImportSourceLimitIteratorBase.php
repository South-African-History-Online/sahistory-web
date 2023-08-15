<?php

namespace Drupal\entity_import\Plugin\migrate\source;

/**
 * Define the entity import source limit iterator base class.
 */
abstract class EntityImportSourceLimitIteratorBase extends EntityImportSourceBase implements EntityImportSourceLimitIteratorInterface {

  /**
   * The limit offset.
   *
   * @var int
   */
  protected $limitOffset = 0;

  /**
   * Is source operating in batch.
   *
   * @var bool
   */
  protected $isBatch = FALSE;

  /**
   * {@inheritDoc}
   */
  public function getLimitCount() {
    return 10;
  }

  /**
   * {@inheritDoc}
   */
  public function getLimitOffset() {
    return $this->limitOffset;
  }

  /**
   * {@inheritDoc}
   */
  public function setLimitOffset($offset) {
    $this->limitOffset = $offset;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBatch($is_batch = TRUE) {
    $this->isBatch = $is_batch;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function resetBaseIterator() {
    $this->iterator = NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \LimitIterator(
      $this->limitedIterator(), $this->getLimitOffset(), $this->getLimitCount()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLimitIteratorCount() {
    $iterator = $this->getIterator();

    if ($iterator instanceof \Countable) {
      return $iterator->count();
    }

    return iterator_count($this->getIterator());
  }
}
