<?php

namespace Drupal\uc_fulfillment\Plugin\Ubercart\OrderPane;

use Drupal\uc_order\OrderPanePluginBase;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_order\OrderInterface;

/**
 * Display tracking numbers of shipped packages.
 *
 * @UbercartOrderPane(
 *   id = "tracking",
 *   title = @Translation("Tracking numbers"),
 *   weight = 7,
 * )
 */
class Tracking extends OrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return array('pos-left');
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $uc_order, $view_mode) {
    if ($view_mode == 'customer' || $view_mode == 'view') {
      $tracking = array();
      $shipments = Shipment::loadByOrder($uc_order->id());
      foreach ($shipments as $shipment) {
        if ($shipment->getTrackingNumber()) {
          $tracking[$shipment->getCarrier()][] = $shipment->getTrackingNumber();
        }
        else {
          foreach ($shipment->getPackages() as $package) {
            if ($package->getTrackingNumber()) {
              $tracking[$shipment->getCarrier()][] = $package->getTrackingNumber();
            }
          }
        }
      }

      // Do not show an empty pane to customers.
      if ($view_mode == 'view' || !empty($tracking)) {
        $build = array();
        foreach ($tracking as $title => $list) {
          $build[$title] = array(
            '#theme' => 'item_list',
            '#title' => $title,
            '#items' => $list, // @todo #plain_text ?
          );
        }
        if (empty($tracking)) {
          $build = array(
            '#markup' => $this->t('No tracking numbers have been entered.'),
          );
        }

        return $build;
      }
    }
  }

}
