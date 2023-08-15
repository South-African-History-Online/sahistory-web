<?php

namespace Drupal\tamper;

/**
 * Defines an interface for source definitions.
 *
 * A source definition tells which sources are available on the whole tamperable
 * item. Tamper plugins can use this knowledge to display available sources in
 * the UI.
 */
interface SourceDefinitionInterface {

  /**
   * Returns an unique list of sources.
   *
   * @return array
   *   A list of sources.
   */
  public function getList();

}
