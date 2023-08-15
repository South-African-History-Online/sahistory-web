<?php

namespace Drupal\uc_cart\Annotation;

use Drupal\Component\Annotation\Plugin;


/**
 * Defines a checkout pane annotation object.
 *
 * @Annotation
 */
class CheckoutPane extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the checkout pane.
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
