<?php

namespace Drupal\uc_fulfillment\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Ubercart fulfillment method annotation object.
 *
 * @Annotation
 */
class UbercartFulfillmentMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';

  /**
   * If TRUE, the plugin will be hidden from the UI.
   *
   * @var bool
   */
  public $no_ui = FALSE;

}
