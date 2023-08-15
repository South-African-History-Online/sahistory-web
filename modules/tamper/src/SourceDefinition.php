<?php

namespace Drupal\tamper;

/**
 * Default class for a source definition.
 */
class SourceDefinition implements SourceDefinitionInterface {

  /**
   * An array of source keys.
   *
   * @var array
   */
  protected $list = [];

  /**
   * Constructs a new SourceDefinition.
   *
   * @param array $list
   *   An array of source keys.
   */
  public function __construct(array $list) {
    $this->list = $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getList() {
    return $this->list;
  }

}
