<?php

namespace Drupal\tamper;

/**
 * Interface for an item that can be tampered as part of a plugin's execution.
 */
interface TamperableItemInterface {

  /**
   * Returns the whole item as an array.
   *
   * @return array
   *   An array of the source data.
   */
  public function getSource();

  /**
   * Sets a source property.
   *
   * @param string $property
   *   A property on the source.
   * @param mixed $data
   *   The property value to set on the source.
   */
  public function setSourceProperty($property, $data);

  /**
   * Retrieves a source property.
   *
   * @param string $property
   *   A property on the source.
   *
   * @return mixed|null
   *   The found returned property or NULL if not found.
   */
  public function getSourceProperty($property);

}
