<?php

namespace Drupal\uc_fulfillment\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_order\OrderInterface;

/**
 * Controller routines for packaging.
 */
class PackageController extends ControllerBase {

  /**
   * Displays a list of an order's packaged products.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array, or a redirect response if there are no packaged products.
   */
  public function listOrderPackages(OrderInterface $uc_order) {
    $shipping_type_options = uc_quote_shipping_type_options();
    $header = array(
      $this->t('Package ID'),
      $this->t('Products'),
      $this->t('Shipping type'),
      $this->t('Package type'),
      $this->t('Shipment ID'),
      $this->t('Tracking number'),
      $this->t('Labels'),
      $this->t('Actions')
    );
    $rows = array();
    $packages = Package::loadByOrder($uc_order->id());
    foreach ($packages as $package) {
      $row = array();
      // Package ID.
      $row[] = array('data' => array('#plain_text' => $package->id()));

      $product_list = array();
      foreach ($package->getProducts() as $product) {
        $product_list[] = $product->qty . ' x ' . $product->model;
      }
      // Products.
      $row[] = array('data' => array('#theme' => 'item_list', '#items' => $product_list));

      // Shipping type.
      $row[] = isset($shipping_type_options[$package->getShippingType()]) ? $shipping_type_options[$package->getShippingType()] : strtr($package->getShippingType(), '_', ' ');

      // Package type.
      $row[] = array('data' => array('#plain_text' => $package->getPackageType()));

      // Shipment ID.
      $row[] = $package->getSid() ?
        array('data' => array(
          '#type' => 'link',
          '#title' => $package->getSid(),
          '#url' => Url::fromRoute('uc_fulfillment.view_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $package->getSid()]),
        )) : '';

      // Tracking number.
      $row[] = $package->getTrackingNumber() ? array('data' => array('#plain_text' => $package->getTrackingNumber())) : '';

      if ($package->getLabelImage() && $image = file_load($package->getLabelImage())) {
        $package->setLabelImage($image);
      }
      else {
        $package->setLabelImage('');
      }

      // Shipping label.
      if ($package->getSid() && $package->getLabelImage()) {
        $shipment = Shipment::load($package->getSid());
        $row[] = Link::fromTextAndUrl("image goes here",
     //     theme('image_style', array(
     //       'style_name' => 'uc_thumbnail',
     //       'uri' => $package->getLabelImage()->uri,
     //       'alt' => $this->t('Shipping label'),
     //       'title' => $this->t('Shipping label'),
     //     )),
          Url::fromUri('base:admin/store/orders/' . $uc_order->id() . '/shipments/labels/' . $shipment->getShippingMethod() . '/' . $package->getLabelImage()->uri, ['uc_order' => $uc_order->id(), 'method' => $shipment->getShippingMethod(), 'image_uri' => $package->getLabelImage()->uri])
        )->toString();
      }
      else {
        $row[] = '';
      }

      // Operations.
      $ops = array(
        '#type' => 'operations',
        '#links' => array(
          'edit' => array(
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('uc_fulfillment.edit_package', ['uc_order' => $uc_order->id(), 'uc_package' => $package->id()]),
          ),
          'ship' => array(
            'title' => $this->t('Ship'),
            'url' => Url::fromRoute('uc_fulfillment.new_shipment', ['uc_order' => $uc_order->id()], ['query' => ['pkgs' => $package->id()]]),
          ),
          'delete' => array(
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_fulfillment.delete_package', ['uc_order' => $uc_order->id(), 'uc_package' => $package->id()]),
          ),
        ),
      );
      if ($package->getSid()) {
        $ops['#links']['cancel'] = array(
          'title' => $this->t('Cancel'),
          'url' => Url::fromRoute('uc_fulfillment.cancel_package', ['uc_order' => $uc_order->id(), 'uc_package' => $package->id()]),
        );
      }
      $row[] = array('data' => $ops);
      $rows[] = $row;
    }

    if (empty($rows)) {
      drupal_set_message($this->t("This order's products have not been organized into packages."), 'warning');
      return $this->redirect('uc_fulfillment.new_package', ['uc_order' => $uc_order->id()]);
    }

    $build['packages'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $build;
  }

}
