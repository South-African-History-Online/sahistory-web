<?php

namespace Drupal\imce\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an ImcePlugin annotation object.
 *
 * Plugin Namespace: Plugin\ImcePlugin
 *
 * @see \Drupal\imce\ImcePluginBase
 *
 * @Annotation
 */
class ImcePlugin extends Plugin {

  /**
   * Plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Plugin label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * Plugin weight.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Operation definitions.
   *
   * @var array
   */
  public $operations = [];

}
