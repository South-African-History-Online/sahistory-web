<?php

namespace Drupal\tamper;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface definition for tamper plugins.
 */
interface TamperInterface extends PluginInspectionInterface, PluginFormInterface, ConfigurableInterface {

  /**
   * Tamper data.
   *
   * Performs the operations on the data to transform it.
   *
   * @param mixed $data
   *   The data to tamper.
   * @param \Drupal\tamper\TamperableItemInterface $item
   *   Item that can be tampered as part of a plugin's execution.
   *
   * @return mixed
   *   The tampered data.
   *
   * @throws \Drupal\tamper\Exception\TamperException
   *   When the plugin can not tamper the given data.
   * @throws \Drupal\tamper\Exception\SkipTamperDataException
   *   When the calling tamper process should be skipped for the given data.
   * @throws \Drupal\tamper\Exception\SkipTamperItemException
   *   When the calling tamper process should be skipped for the given item.
   */
  public function tamper($data, TamperableItemInterface $item = NULL);

  /**
   * Indicates whether the returned value requires multiple handling.
   *
   * @return bool
   *   TRUE when the returned value contains a list of values to be processed.
   *   For example, when the 'data' variable is a string and the tampered value
   *   is an array.
   */
  public function multiple();

  /**
   * Get a particular configuration value.
   *
   * @param string $key
   *   Key of the configuration.
   *
   * @return mixed|null
   *   Setting value if found.
   */
  public function getSetting($key);

}
