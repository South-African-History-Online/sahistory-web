<?php

namespace Drupal\uc_payment\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Ubercart payment method annotation object.
 *
 * @Annotation
 */
class UbercartPaymentMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human readable name of the payment method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $name;

  /**
   * If TRUE, the plugin will be hidden from the UI.
   *
   * @var bool
   */
  public $no_ui = FALSE;

  /**
   * Name of a class that implements \Drupal\Core\Form\FormBase and provides
   * a Submit button on the /cart/checkout/review page redirecting to an
   * external payment site.
   *
   * @var string
   */
  public $redirect = NULL;

  /**
   * Name of a class that implements \Drupal\Core\Form\FormBase and provides
   * an express checkout button for the /cart page.
   *
   * @var string
   */
  public $express = NULL;
}
