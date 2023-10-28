<?php

namespace Drupal\uc_order\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an order pane annotation object.
 *
 * @Annotation
 */
class UbercartOrderPane extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the pane.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

}
