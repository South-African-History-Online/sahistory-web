<?php

namespace Drupal\tamper\Adapter;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\tamper\TamperableItemInterface;

/**
 * Provides an adapter to use complex typed data as a tamperable item.
 */
class TamperableComplexDataAdapter implements TamperableItemInterface {

  /**
   * Typed complex data object.
   *
   * @var \Drupal\Core\TypedData\ComplexDataInterface
   */
  protected $complexData;

  /**
   * Create a new instance of the adapter.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $complexData
   *   Typed complex data object.
   */
  public function __construct(ComplexDataInterface $complexData) {
    $this->complexData = $complexData;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource($include_computed = FALSE) {
    return $this->complexData->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceProperty($property, $data) {
    $this->complexData->set($property, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceProperty($property) {
    return $this->complexData->get($property);
  }

}
