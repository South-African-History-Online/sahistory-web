<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\OrderPanePluginBase;

/**
 * Button to open a printable invoice.
 *
 * @UbercartOrderPane(
 *   id = "print_button",
 *   title = @Translation("Print button"),
 *   weight = -10,
 * )
 */
class PrintButton extends OrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode == 'customer' && $order->access('invoice')) {
      $build = array(
        '#type' => 'link',
        '#title' => $this->t('Click to open a window with a printable invoice.'),
        '#url' => Url::fromRoute('uc_order.user_invoice_print', ['user' => $order->getOwnerId(), 'uc_order' => $order->id()], array(
          'attributes' => array(
            'onclick' => "window.open(this.href, '" . $this->t('Invoice') . "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=600,height=480,left=50,top=50'); return false;",
          ),
        )),
      );
      return $build;
    }
  }

}
