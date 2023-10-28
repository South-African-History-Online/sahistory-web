<?php

namespace Drupal\uc_tax\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a tax rate annotation object.
 *
 * @Annotation
 */
class UbercartTaxRate extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the tax rate.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label = '';

  /**
   * The plugin weight.
   *
   * @var integer
   */
  public $weight;

}
