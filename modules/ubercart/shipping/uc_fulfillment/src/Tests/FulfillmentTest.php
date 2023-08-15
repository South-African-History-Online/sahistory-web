<?php

namespace Drupal\uc_fulfillment\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests fulfillment backend functionality.
 *
 * @group Ubercart
 */
class FulfillmentTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack', 'uc_fulfillment');
  public static $adminPermissions = array('fulfill orders');

  /**
   * Tests packaging and shipping a simple order with the "Manual" plugin.
   */
  public function testFulfillmentProcess() {
    // Log on as administrator to fulfill order.
    $this->drupalLogin($this->adminUser);

    // A payment method for the order.
    $method = $this->createPaymentMethod('other');

    // Create an anonymous, shippable order.
    $order = $this->createOrder([
      'uid' => 0,
      'payment_method' => $method['id'],
      'primary_email' => $this->randomMachineName() . '@example.org',
    ]);
    $order->products[1]->data->shippable = 1;
    $order->save();

    // Check out with the test product.
    uc_payment_enter($order->id(), $method['id'], $order->getTotal());

    // Check for Packages tab and Shipments tab. BOTH should
    // redirect us to $order->id()/packages/new at this point,
    // because we have no packages or shipments yet.

    // Test Packages tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Test presence of tab to package products.
    $this->assertLinkByHref('admin/store/orders/' . $order->id() . '/packages');
    // Go to packages tab.
    $this->clickLink(t('Packages'));
    $this->assertResponse(200);
    // Check redirected path.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/packages/new');
    $this->assertText(
      'This order\'s products have not been organized into packages.',
      'Packages tab found.'
    );

    // Test Shipments tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Test presence of tab to make shipments.
    $this->assertLinkByHref('admin/store/orders/' . $order->id() . '/shipments');
    // Go to Shipments tab.
    $this->clickLink(t('Shipments'));
    $this->assertResponse(200);
    // Check redirected path.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/packages/new');
    $this->assertText(
      'This order\'s products have not been organized into packages.',
      'Shipments tab found.'
    );

    // Now package the products in this order.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/packages');
    $this->assertText(
      $order->products[1]->title->value,
      'Product title found.'
    );
    $this->assertText(
      $order->products[1]->model->value,
      'Product sku found.'
    );
    $this->assertFieldByName(
      'shipping_types[small_package][table][' . $order->id() . '][checked]',
      0,
      'Product is available for packaging.'
    );

    // Select product and create one package.
    $this->drupalPostForm(
      NULL,
      array('shipping_types[small_package][table][' . $order->id() . '][checked]' => 1),
      t('Create one package')
    );
    // Check that we're now on the package list page.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/packages');
    $this->assertText(
      $order->products[1]->qty->value . ' x ' . $order->products[1]->model->value,
      'Product quantity x SKU found.'
    );

    // Test the Shipments tab.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->clickLink(t('Shipments'));
    $this->assertResponse(200);
    // Check redirected path.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/shipments/new');
    $this->assertText(
      'No shipments have been made for this order.',
      'New shipments page reached.'
    );
    $this->assertText(
      $order->products[1]->qty->value . ' x ' . $order->products[1]->model->value,
      'Product quantity x SKU found.'
    );
    $this->assertFieldByName(
      'method',
      'manual',
      'Manual shipping method selected.'
    );

    // Select all packages and create shipment using the default "Manual" method..
    $this->drupalPostForm(
      NULL,
      array('shipping_types[small_package][table][' . $order->id() . '][checked]' => 1),
      t('Ship packages')
    );
    // Check that we're now on the shipment details page.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/ship?method_id=manual&0=1');
    $this->assertText(
      'Origin address',
      'Origin address pane found.'
    );
    $this->assertText(
      'Destination address',
      'Destination address pane found.'
    );
    $this->assertText(
      'Package 1',
      'Packages data pane found.'
    );
    $this->assertText(
      'Shipment data',
      'Shipment data pane found.'
    );

    $street = array_flip(array(
      'Street',
      'Avenue',
      'Place',
      'Way',
      'Road',
      'Boulevard',
      'Court',
    ));

    // Fill in the details and make the shipment.
    // If we filled the addresses in when we created the order,
    // those values should already be set here so we wouldn't
    // have to fill them in again.
    $form_values = array(
      'pickup_address[first_name]' => $this->randomMachineName(6),
      'pickup_address[last_name]' => $this->randomMachineName(12),
      'pickup_address[company]' => $this->randomMachineName(10) . ', Inc.',
      'pickup_address[street1]' => mt_rand(10, 1000) . ' ' .
                                   $this->randomMachineName(10) . ' ' .
                                   array_rand($street),
      'pickup_address[street2]' => 'Suite ' . mt_rand(100, 999),
      'pickup_address[city]' => $this->randomMachineName(10),
      'pickup_address[postal_code]' => mt_rand(10000, 99999),
      'delivery_address[first_name]' => $this->randomMachineName(6),
      'delivery_address[last_name]' => $this->randomMachineName(12),
      'delivery_address[company]' => $this->randomMachineName(10) . ', Inc.',
      'delivery_address[street1]' => mt_rand(10, 1000) . ' ' .
                                     $this->randomMachineName(10) . ' ' .
                                     array_rand($street),
      'delivery_address[street2]' => 'Suite ' . mt_rand(100, 999),
      'delivery_address[city]' => $this->randomMachineName(10),
      'delivery_address[postal_code]' => mt_rand(10000, 99999),
      'packages[1][pkg_type]' => 'envelope',
      'packages[1][declared_value]' => '1234.56',
      'packages[1][tracking_number]' => '4-8-15-16-23-42',
      'packages[1][weight][weight]' => '3',
      'packages[1][weight][units]' => array_rand(array_flip(array('lb', 'kg', 'oz', 'g'))),
      'packages[1][dimensions][length]' => '1',
      'packages[1][dimensions][width]' => '1',
      'packages[1][dimensions][height]' => '1',
      'packages[1][dimensions][length]' => '1',
      'packages[1][dimensions][units]' => array_rand(array_flip(array('in', 'ft', 'cm', 'mm'))),
      'carrier' => 'FedEx',
      'accessorials' => 'Standard Overnight',
      'transaction_id' => 'THX1138',
      'tracking_number' => '1234567890ABCD',
      'ship_date[date]' => '1985-10-26',
      'expected_delivery[date]' => '2015-10-21',
      'cost' => '12.34',
    );

//@todo fix ajax and uncomment settings country and zone.
    // Find available countries for our select.
    $country_ids = \Drupal::service('country_manager')->getEnabledList();
    $pickup_country = array_rand($country_ids);
//    $this->drupalPostAjaxForm(NULL, ['pickup_address[country]' => $pickup_country], 'pickup_address[country]');

    // Don't try to set the zone unless the country has zones!
    $zone_list = \Drupal::service('country_manager')->getZoneList($pickup_country);
    if (!empty($zone_list)) {
 //     $form_values['pickup_address[zone]'] = array_rand($zone_list);
    }

    $delivery_country = array_rand($country_ids);
//    $this->drupalPostAjaxForm(NULL, ['delivery_address[country]' => $delivery_country], 'delivery_address[country]');
    $zone_list = \Drupal::service('country_manager')->getZoneList($delivery_country);
    if (!empty($zone_list)) {
//      $form_values['delivery_address[zone]'] = array_rand($zone_list);
    }

    // Make the shipment.
    $this->drupalPostForm(NULL, $form_values, t('Save shipment'));

    // Check that we're now on the shipments overview page
    $this->assertUrl('admin/store/orders/' . $order->id() . '/shipments');
    $this->assertText(
      'Shipment ID',
      'Shipment summary found.'
    );
    $this->assertText(
      '1234567890ABCD',
      'Shipment data present.'
    );

    // Check for "Tracking" order pane after this order has
    // been shipped and a tracking number entered.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText(
      t('Tracking numbers:'),
      'Tracking order pane found.'
    );
    $this->assertText(
      '1234567890ABCD',
      'Tracking number found.'
    );

    // Delete Order and check to see that all Package/Shipment data has been removed.
  }

}
