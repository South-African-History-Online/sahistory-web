<?php

namespace Drupal\csv_importer\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Importer manager interface.
 */
interface ImporterInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Prepare data for import.
   *
   * @return array
   *   Prepared data.
   */
  public function data();

  /**
   * Add content.
   *
   * @param mixed $content
   *   CSV content.
   * @param array $context
   *   The batch context array.
   *
   * @return array
   *   Prepared data.
   */
  public function add($content, array &$context);

  /**
   * Get batch operations.
   *
   * @return array
   *   The batch operations.
   */
  public function getOperations();

  /**
   * Batch finish handler.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   Contains the operations that remained unprocessed.
   *
   * @return array
   *   Prepared data.
   */
  public function finished($success, $results, array $operations);

  /**
   * Run batch operations.
   */
  public function process();

}
