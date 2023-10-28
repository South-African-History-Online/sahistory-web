<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface EntityImportProcessInterface
 *
 * @package Drupal\entity_import\Plugin\migrate\process
 */
interface EntityImportProcessInterface extends PluginFormInterface {

  /**
   * Get process label.
   *
   * @return mixed
   */
  public function getLabel();

  /**
   * Define default configurations.
   *
   * @return array
   */
  public function defaultConfigurations();

}
