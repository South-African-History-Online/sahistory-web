<?php

namespace Drupal\uc_fulfillment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\uc_fulfillment\Entity\FulfillmentMethod;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_fulfillment\ShipmentInterface;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_fulfillment\PackageInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_store\Address;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for shipments.
 */
class ShipmentController extends ControllerBase {

  /**
   * The page title callback for shipment views.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The shipment's order.
   * @param \Drupal\uc_fulfillment\ShipmentInterface $uc_shipment
   *   The ID of shipment.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(OrderInterface $uc_order, ShipmentInterface $uc_shipment) {
    return $this->t('Shipment @id', ['@id' => $uc_shipment->id()]);
  }

  /**
   * Default method to create a shipment from packages.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array, or a redirect response if there are selected packages.
   */
  public function makeShipment(OrderInterface $uc_order, Request $request) {
    $method_id = $request->query->get('method_id');
    $request->query->remove('method_id');
    $package_ids = $request->query->all();
    if (count($package_ids) > 0) {
//      $breadcrumb = drupal_get_breadcrumb();
//      $breadcrumb[] = Link::createFromRoute($this->t('Shipments'), 'uc_fulfillment.shipments', ['uc_order' => $uc_order->id()]);
//      drupal_set_breadcrumb($breadcrumb);

      // Find FulfillmentMethod plugins.
      $manager = \Drupal::service('plugin.manager.uc_fulfillment.method');
      $methods = FulfillmentMethod::loadMultiple();

      if (isset($methods[$method_id])) {
        $method = $methods[$method_id];
      }
      else {
        // The selected fulfullment isn't available, so use built-in "Manual" shipping.
        $method = $methods['manual'];
      }
      $plugin = $manager->createInstance($method->getPluginId(), $method->getPluginConfiguration());
      return $plugin->fulfillOrder($uc_order, $package_ids);
    }
    else {
      drupal_set_message($this->t('There is no sense in making a shipment with no packages on it, right?'), 'warning');
      return $this->redirect('uc_fulfillment.new_shipment', ['uc_order' => $uc_order->id()]);
    }
  }

  /**
   * Shows a printer-friendly version of a shipment.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   * @param \Drupal\uc_fulfillment\ShipmentInterface $uc_shipment
   *   The ID of shipment.
   * @param bool $print
   *   Whether to generate a printable version.
   * @param bool $labels
   *   Whether to include mailing labels.
   *
   * @return array|string
   *   A render array or HTML markup in a form suitable for printing.
   */
  public function printShipment(OrderInterface $uc_order, ShipmentInterface $uc_shipment, $print = FALSE, $labels = TRUE) {
    $packing_slip = array(
      '#theme' => 'uc_packing_slip',
      '#order' => $uc_order,
      '#shipment' => $uc_shipment,
      '#labels' => $labels,
      '#op' => $print ? 'print' : 'view',
    );

    if ($print) {
      $build = array(
        '#theme' => 'uc_packing_slip_page',
        '#content' => $packing_slip,
      );
      $markup = \Drupal::service('renderer')->renderPlain($build);
      $response = new Response($markup);
      $response->headers->set('Content-Type', 'text/html; charset=utf-8');
      return $response;
    }

    return $packing_slip;
  }

  /**
   * Displays a list of shipments for an order.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array, or redirect response if there are no shipments.
   */
  public function listOrderShipments(OrderInterface $uc_order) {
    $header = array(
      $this->t('Shipment ID'),
      $this->t('Name'),
      $this->t('Company'),
      $this->t('Destination'),
      $this->t('Ship date'),
      $this->t('Estimated delivery'),
      $this->t('Tracking number'),
      $this->t('Actions')
    );

    $shipments = Shipment::loadByOrder($uc_order->id());
    $rows = array();
    foreach ($shipments as $shipment) {
      $row = array();
      // Shipment ID.
      $row[] = array('data' => array('#plain_text' => $shipment->id()));

      // Destination address.
      $destination = $shipment->getDestination();

      // Name.
      $row[] = array('data' => array('#plain_text' => $destination->getFirstName() . ' ' . $destination->getLastName()));

      // Company.
      $row[] = array('data' => array('#plain_text' => $destination->getCompany()));

      // Destination.
      $row[] = array('data' => array('#plain_text' => $destination->getCity() . ', ' . $destination->getZone() . ' ' . $destination->getPostalCode()));

      // Ship date.
      $row[] = \Drupal::service('date.formatter')->format($shipment->getShipDate(), 'uc_store');

      // Estimated delivery.
      $row[] = \Drupal::service('date.formatter')->format($shipment->getExpectedDelivery(), 'uc_store');

      // Tracking number.
      $row[] = empty($shipment->getTrackingNumber()) ? $this->t('n/a') : array('data' => array('#plain_text' => $shipment->getTrackingNumber()));

      // Actions.
      $ops[] = array(
        '#type' => 'operations',
        '#links' => array(
          'view' => array(
            'title' => $this->t('View'),
            'url' => Url::fromRoute('uc_fulfillment.view_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
          'edit' => array(
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('uc_fulfillment.edit_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
          'print' => array(
            'title' => $this->t('Print'),
            'url' => Url::fromRoute('uc_fulfillment.print_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
          'packing_slip' => array(
            'title' => $this->t('Packing slip'),
            'url' => Url::fromRoute('uc_fulfillment.packing_slip', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
          'delete' => array(
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_fulfillment.delete_shipment', ['uc_order' => $uc_order->id(), 'uc_shipment' => $shipment->id()]),
          ),
        ),
      );
      $row[] = array('data' => $ops);
      $rows[] = $row;
    }

    if (empty($rows)) {
      if (count(Package::loadByOrder($uc_order->id())) == 0) {
        drupal_set_message($this->t("This order's products have not been organized into packages."), 'warning');
        return $this->redirect('uc_fulfillment.new_package', ['uc_order' => $uc_order->id()]);
      }
      else {
        drupal_set_message($this->t('No shipments have been made for this order.'), 'warning');
        return $this->redirect('uc_fulfillment.new_shipment', ['uc_order' => $uc_order->id()]);
      }
    }

    $build['shipments'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    return $build;
  }


  /**
   * Displays shipment details.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order object.
   * @param \Drupal\uc_fulfillment\ShipmentInterface $uc_shipment
   *   The shipment.
   *
   * @return array
   *   A render array.
   */
  public function viewShipment(OrderInterface $uc_order, ShipmentInterface $uc_shipment) {

    // Origin address.
    $build['pickup_address'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'pos-left')),
    );
    $build['pickup_address']['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Pickup Address:'),
      '#attributes' => array('class' => array('order-pane-title')),
    );
    $build['pickup_address']['address'] = array(
      '#type' => 'container',
      '#markup' => $uc_shipment->getOrigin(),
    );

    // Destination address.
    $build['delivery_address'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'pos-left')),
    );
    $build['delivery_address']['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Delivery Address:'),
      '#attributes' => array('class' => array('order-pane-title')),
    );
    $build['delivery_address']['address'] = array(
      '#type' => 'container',
      '#markup' => $uc_shipment->getDestination(),
    );

    // Fulfillment schedule.
    $rows = array();
    $rows[] = array(
      $this->t('Ship date:'),
      \Drupal::service('date.formatter')->format($uc_shipment->getShipDate(), 'uc_store')
    );
    $rows[] = array(
      $this->t('Expected delivery:'),
      \Drupal::service('date.formatter')->format($uc_shipment->getExpectedDelivery(), 'uc_store')
    );
    $build['schedule'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'abs-left')),
    );
    $build['schedule']['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Schedule:'),
      '#attributes' => array('class' => array('order-pane-title')),
    );
    $build['schedule']['table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('style' => 'width: auto'),
    );

    // Shipment details.
    $rows = array();
    $rows[] = array(
      $this->t('Carrier:'),
      array('data' => array('#plain_text' => $uc_shipment->getCarrier())),
    );
    if ($uc_shipment->getTransactionId()) {
      $rows[] = array(
        $this->t('Transaction ID:'),
        array('data' => array('#plain_text' => $uc_shipment->getTransactionId())),
      );
    }
    if ($uc_shipment->getTrackingNumber()) {
      $rows[] = array(
        $this->t('Tracking number:'),
        array('data' => array('#plain_text' => $uc_shipment->getTrackingNumber())),
      );
    }
    $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
    if (isset($methods[$uc_shipment->getShippingMethod()]['quote']['accessorials'][$uc_shipment->getAccessorials()])) {
      $rows[] = array($this->t('Services:'),
        $methods[$uc_shipment->getShippingMethod()]['quote']['accessorials'][$uc_shipment->getAccessorials()],
      );
    }
    else {
      $rows[] = array($this->t('Services:'),
        $uc_shipment->getAccessorials(),
      );
    }
    $rows[] = array(
      $this->t('Cost:'),
      array('data' => array('#theme' => 'uc_price', '#price' => $uc_shipment->getCost())),
    );
    $build['details'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'abs-left')),
    );
    $build['details']['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Shipment Details:'),
      '#attributes' => array('class' => array('order-pane-title')),
    );
    $build['details']['table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => array('style' => 'width:auto'),
    );

    // Packages.
    foreach ($uc_shipment->getPackages() as $package) {
      $build['packages'][] = $this->viewPackage($package);
    }

    return $build;
  }

  /**
   * Returns an address from an object.
   *
   * @param \Drupal\uc_fulfillment\ShipmentInterface $uc_shipment
   *   A Shipment object.
   * @param string $type
   *   The key prefix to use to extract the address.
   *
   * @return \Drupal\uc_store\AddressInterface
   *   An address object.
   */
  protected function getAddress(ShipmentInterface $uc_shipment, $type) {
    $name = $shipment->{$type . '_first_name'} . ' ' . $shipment->{$type . '_last_name'};
    $address = Address::create();
    $address
      ->setFirstName($shipment->{$type . '_first_name'})
      ->setLastName($shipment->{$type . '_last_name'})
      ->setCompany($shipment->{$type . '_company'})
      ->setStreet1($shipment->{$type . '_street1'})
      ->setStreet2($shipment->{$type . '_street2'})
      ->setCity($shipment->{$type . '_city'})
      ->setZone($shipment->{$type . '_zone'})
      ->setPostalCode($shipment->{$type . '_postal_code'})
      ->setCountry($shipment->{$type . '_country'});

    return $address;
  }

  /**
   * Displays the details of a package.
   *
   * @param \Drupal\uc_fulfillment\PackageInterface $package
   *   The package object.
   *
   * @return array
   *   A render array.
   */
  public function viewPackage(PackageInterface $package) {
    $shipment = Shipment::load($package->getSid());
    $build = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('order-pane', 'pos-left')),
    );
    $build['title'] = array(
      '#type' => 'container',
      '#markup' => $this->t('Package %id:', ['%id' => $package->id()]),
      '#attributes' => array('class' => array('order-pane-title')),
    );

    $rows = array();
    $rows[] = array($this->t('Contents:'), array('data' => array('#markup' => $package->getDescription())));

    if ($shipment) {
      $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
      if (isset($methods[$shipment->getShippingMethod()])) {
        $pkg_type = $methods[$shipment->getShippingMethod()]['ship']['pkg_types'][$package->getPackageType()];
      }
    }

    $rows[] = array($this->t('Package type:'), isset($pkg_type) ? $pkg_type : array('data' => array('#plain_text' => $package->getPackageType())));

    if ($package->getLength() && $package->getWidth() && $package->getHeight()) {
      $units = $package->getLengthUnits();
      $rows[] = array($this->t('Dimensions:'), $this->t('@l x @w x @h', ['@l' => uc_length_format($package->getLength(), $units), '@w' => uc_length_format($package->getWidth(), $units), '@h' => uc_length_format($package->getHeight(), $units)]));
    }

    if ($package->getWeight()) {
      $units = $package->getWeightUnits();
      $rows[] = array($this->t('Weight:'), uc_weight_format($package->getWeight(), $units));
    }

    $rows[] = array($this->t('Insured value:'), array('data' => array('#theme' => 'uc_price', '#price' => $package->getValue())));

    if ($package->getTrackingNumber()) {
      $rows[] = array($this->t('Tracking number:'), array('data' => array('#plain_text' => $package->getTrackingNumber())));
    }

    if ($shipment && $package->getLabelImage() &&
        file_exists($package->getLabelImage()->uri)) {
      $rows[] = array(
        $this->t('Label:'),
        array('data' => array(
          '#type' => 'link',
          '#title' => $this->t('Click to view.'),
          '#url' => Url::fromUri('admin/store/orders/' . $package->getOrderId() . '/shipments/labels/' . $shipment->getShippingMethod() . '/' . $package->getLabelImage()->uri),
        )),
      );
    }
    else {
      $rows[] = array($this->t('Label:'), $this->t('n/a'));
    }

    $build['package'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      'attributes' => array('style' => 'width:auto;'),
    );

    return $build;
  }

}
