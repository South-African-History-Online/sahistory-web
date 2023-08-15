<?php

namespace Drupal\uc_quote\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a shipping quote plugin annotation object.
 *
 * @see \Drupal\uc_quote\Plugin\ShippingQuotePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class UbercartShippingQuote extends Plugin {

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

}
