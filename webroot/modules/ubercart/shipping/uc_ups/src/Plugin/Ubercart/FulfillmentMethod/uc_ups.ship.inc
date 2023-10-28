<?php

/**
 * @file
 * UPS functions for label generation.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\uc_store\Address;

/**
 * Shipment creation callback.
 *
 * Confirms shipment data before requesting a shipping label.
 *
 * @param $order_id
 *   The order id for the shipment.
 * @param $package_ids
 *   Array of package ids to shipped.
 *
 * @see uc_ups_fulfill_order_validate()
 * @see uc_ups_fulfill_order_submit()
 * @ingroup forms
 */
function uc_ups_fulfill_order($form, FormStateInterface $form_state, $order, $package_ids) {
  $store_config = \Drupal::config('uc_store.settings');
  $pkg_types = \Drupal\uc_ups\UPSUtilities::packageTypes();
  $form['order_id'] = array('#type' => 'value', '#value' => $order->id());
  $packages = array();
  $addresses = array();

  // Container for package data
  $form['packages'] = array(
    '#type' => 'fieldset',
    '#title' => t('Packages'),
    '#tree' => TRUE
  );
  foreach ($package_ids as $id) {
    $package = uc_shipping_package_load($id);
    if ($package) {
      foreach ($package->addresses as $address) {
        if (!in_array($address, $addresses)) {
          $addresses[] = $address;
        }
      }
      // Create list of products and get a representative product (last one in
      // the loop) to use for some default values
      $product_list = array();
      $declared_value = 0;
      foreach ($package->products as $product) {
        $product_list[] = $product->qty . ' x ' . $product->model;
        $declared_value += $product->qty * $product->price;
      }
      // Use last product in package to determine package type
      $ups_data = db_query("SELECT pkg_type FROM {uc_ups_products} WHERE nid = :nid", [':nid' => $product->nid])->fetchAssoc();
      $product->ups = $ups_data;
      $pkg_form = array(
        '#type' => 'fieldset',
        '#title' => t('Package @id', ['@id' => $id]),
      );
      $pkg_form['products'] = array(
        '#theme' => 'item_list',
        '#items' => $product_list,
      );
      $pkg_form['package_id'] = array(
        '#type'  => 'hidden',
        '#value' => $id,
      );
      $pkg_form['pkg_type'] = array(
        '#type'          => 'select',
        '#title'         => t('Package type'),
        '#options'       => $pkg_types,
        '#default_value' => $product->ups['pkg_type'],
        '#required'      => TRUE,
      );
      $pkg_form['declared_value'] = array(
        '#type'          => 'textfield',
        '#title'         => t('Declared value'),
        '#default_value' => $declared_value,
        '#required'      => TRUE,
      );
      $pkg_form['weight'] = array(
        '#type'           => 'container',
        '#attributes'     => array('class' => array('uc-inline-form', 'clearfix')),
        '#description'    => t('Weight of the package. Default value is sum of product weights in the package.'),
        '#weight'         => 15,
      );
      $pkg_form['weight']['weight'] = array(
        '#type'          => 'textfield',
        '#title'         => t('Weight'),
        '#default_value' => isset($package->weight) ? $package->weight : 0,
        '#size'          => 10,
        '#maxlength'     => 15,
      );
      $pkg_form['weight']['units'] = array(
        '#type'          => 'select',
        '#title'         => t('Units'),
        '#options'       => array(
          'lb' => t('Pounds'),
          'kg' => t('Kilograms'),
          'oz' => t('Ounces'),
          'g'  => t('Grams'),
        ),
        '#default_value' => isset($package->weight_units) ? $package->weight_units : $store_config->get('weight.units'),
      );
      $pkg_form['dimensions'] = array(
        '#type'          => 'container',
        '#attributes'    => array('class' => array('uc-inline-form', 'clearfix')),
        '#description'   => t('Physical dimensions of the package.'),
        '#weight'        => 20,
      );
      $pkg_form['dimensions']['length'] = array(
        '#type'          => 'textfield',
        '#title'         => t('Length'),
        '#default_value' => isset($package->length) ? $package->length : 1,
        '#size'          => 10,
      );
      $pkg_form['dimensions']['width'] = array(
        '#type'          => 'textfield',
        '#title'         => t('Width'),
        '#default_value' => isset($package->width) ? $package->width : 1,
        '#size'          => 10,
      );
      $pkg_form['dimensions']['height'] = array(
        '#type'          => 'textfield',
        '#title'         => t('Height'),
        '#default_value' => isset($package->height) ? $package->height : 1,
        '#size'          => 10,
      );
      $pkg_form['dimensions']['units'] = array(
        '#type'          => 'select',
        '#title'         => t('Units'),
        '#options'       => array(
          'in' => t('Inches'),
          'ft' => t('Feet'),
          'cm' => t('Centimeters'),
          'mm' => t('Millimeters'),
        ),
        '#default_value' => isset($package->length_units) ? $package->length_units : $store_config->get('length.units'),
      );

      $form['packages'][$id] = $pkg_form;
    }
  }

  $form = uc_shipping_address_form($form, $form_state, $addresses, $order);

  foreach (array('delivery_email', 'delivery_last_name', 'delivery_street1', 'delivery_city', 'delivery_country', 'delivery_postal_code') as $field) {
    $form['destination'][$field]['#required'] = TRUE;
  }

  // Determine shipping option chosen by the customer
  $method  = $order->quote['method'];
  $methods = \Drupal::moduleHandler()->invokeAll('uc_shipping_method');
  if (isset($methods[$method])) {
    $services = $methods[$method]['quote']['accessorials'];
    $method   = $services[$order->quote['accessorials']];
  }

  // Container for shipment data
  $form['shipment'] = array(
    '#type'        => 'fieldset',
    '#title'       => t('Shipment data'),
  );

  // Inform user of customer's shipping choice
  $form['shipment']['shipping_choice'] = array(
    '#type'   => 'markup',
    '#prefix' => '<div>',
    '#markup' => t('Customer selected "@method" as the shipping method and paid @rate', ['@method' => $method, '@rate' => uc_currency_format($order->quote['rate'])]),
    '#suffix' => '</div>',
  );

  // Pass shipping charge paid information on to validation function so it
  // can be displayed alongside actual costs
  $form['shipment']['paid'] = array(
    '#type'  => 'value',
    '#value' => uc_currency_format($order->quote['rate']),
  );

  $services = _uc_ups_service_list();
  $default_service = '';
  if ($method == 'ups') {
    $default_service = $order->quote['accessorials'];
  }

  $form['shipment']['service'] = array(
    '#type'          => 'select',
    '#title'         => t('UPS service'),
    '#options'       => $services,
    '#default_value' => $default_service,
  );
  $today = getdate();
  $form['shipment']['ship_date'] = array(
    '#type'          => 'date',
    '#title'         => t('Ship date'),
    '#default_value' => array(
      'year'  => $today['year'],
      'month' => $today['mon'],
      'day'   => $today['mday']
    ),
  );
  $form['shipment']['expected_delivery'] = array(
    '#type'          => 'date',
    '#title'         => t('Expected delivery'),
    '#default_value' => array(
      'year'  => $today['year'],
      'month' => $today['mon'],
      'day'   => $today['mday']
    ),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type'  => 'submit',
    '#value' => t('Review shipment')
  );

  return $form;
}

/**
 * Passes final information into shipment object.
 *
 * @see uc_ups_fulfill_order()
 * @see uc_ups_confirm_shipment()
 */
function uc_ups_fulfill_order_validate($form, FormStateInterface $form_state) {

  $ups_config = \Drupal::config('uc_ups.setttings');
  $errors = $form_state->getErrors();
  if (isset($errors)) {
    // Some required elements are missing - don't bother with making
    // a UPS API call until that gets fixed.
    return;
  }

  $origin = new stdClass();
  $destination = new stdClass();
  $packages = array();
  foreach ($form_state->getValues() as $key => $value) {
    if (substr($key, 0, 7) == 'pickup_') {
      $field = substr($key, 7);
      $origin->$field = $value;
    }
    elseif (substr($key, 0, 9) == 'delivery_') {
      $field = substr($key, 9);
      $destination->$field = $value;
    }
  }

  // This is a total hack to work around changes made in the return value
  // from uc_shipping_address_form().  That function needs to be fixed, but
  // until then this should do the trick.
  $origin        = $form_state->getValue('pickup_address');
  $origin->phone = $form_state->getValue('phone');
  $origin->first_name = $form_state->getValue('first_name');
  $origin->last_name = $form_state->getValue('last_name');
  $origin->company = $form_state->getValue('company');
  $origin->street1 = $form_state->getValue('street1');
  $origin->street2 = $form_state->getValue('street2');
  $origin->city = $form_state->getValue('city');
  $origin->zone = $form_state->getValue('zone');
  $origin->country = $form_state->getValue('country');
  $origin->postal_code = $form_state->getValue('postal_code');
  $origin->email = $form_state->getValue('pickup_email');

  $_SESSION['ups'] = array();
  $_SESSION['ups']['origin'] = $origin;

  if (empty($destination->company)) {
    $destination->company = $destination->first_name . ' ' . $destination->last_name;
  }

  // Determine if address is Residential or Commercial
  $destination->residential = $ups_config->get('residential_quotes');

  $_SESSION['ups']['destination'] = $destination;
  foreach ($form_state->getValue('packages') as $id => $pkg_form) {
    $package               = uc_shipping_package_load($id);
    $package->pkg_type     = $pkg_form['pkg_type'];
    $package->value        = $pkg_form['declared_value'];
    $package->weight       = $pkg_form['weight']['weight'];
    $package->weight_units = $pkg_form['weight']['units'];
    $package->length       = $pkg_form['dimensions']['length'];
    $package->width        = $pkg_form['dimensions']['width'];
    $package->height       = $pkg_form['dimensions']['height'];
    $package->length_units = $pkg_form['dimensions']['units'];
    $package->qty          = 1;
    $_SESSION['ups']['packages'][$id] = $package;
  }
  $_SESSION['ups']['service']           = $form_state->getValue('service');
  $_SESSION['ups']['paid']              = $form_state->getValue('paid');
  $_SESSION['ups']['ship_date']         = $form_state->getValue('ship_date');
  $_SESSION['ups']['expected_delivery'] = $form_state->getValue('expected_delivery');
  $_SESSION['ups']['order_id'] = $form_state->getValue('order_id');

  $request = uc_ups_shipment_request($_SESSION['ups']['packages'], $origin, $destination, $form_state->getValue('service'));
  $response_obj = \Drupal::httpClient()
    ->post($ups_config->get('connection_address') . 'ShipConfirm', NULL, $request)
    ->send();
  $response = new SimpleXMLElement($response_obj->getBody(TRUE));
  if (isset($response->Response->Error)) {
    $error = $response->Response->Error;
    $error_msg = (string) $error->ErrorSeverity . ' Error ' . (string) $error->ErrorCode . ': ' . (string) $error->ErrorDescription;
    if (strpos((string) $error->ErrorSeverity, 'Hard') !== FALSE) {
      $form_state->setErrorByName('', $error_msg);
      return FALSE;
    }
    else {
      drupal_set_message($error_msg, 'error');
    }
  }
  $charge = new stdClass();
  // if NegotiatedRates exist, quote based on those, otherwise, use TotalCharges
  if (isset($response->ShipmentCharges)) {
    $charge = $response->ShipmentCharges->TotalCharges;
    $_SESSION['ups']['rate']['type'] = t('Total Charges');
    if (isset($response->NegotiatedRates)) {
      $charge = $response->NegotiatedRates->NetSummaryCharges->GrandTotal;
      $_SESSION['ups']['rate']['type'] = t('Negotiated Rates');
    }
  }
  $_SESSION['ups']['rate']['currency'] = (string) $charge->CurrencyCode;
  $_SESSION['ups']['rate']['amount'] = (string) $charge->MonetaryValue;
  $_SESSION['ups']['digest'] = (string) $response->ShipmentDigest;
}

/**
 * Passes final information into shipment object.
 *
 * @see uc_ups_fulfill_order()
 * @see uc_ups_confirm_shipment()
 */
function uc_ups_fulfill_order_submit($form, FormStateInterface $form_state) {
  $form_state['redirect'] = 'admin/store/orders/' . $form_state->getValue('order_id') . '/shipments/ups';
}

/**
 * Constructs an XML shipment request.
 *
 * @param $packages
 *   Array of packages received from the cart.
 * @param $origin
 *   Delivery origin address information.
 * @param $destination
 *   Delivery destination address information.
 * @param $ups_service
 *   UPS service code (refers to UPS Ground, Next-Day Air, etc.).
 *
 * @return
 *   ShipConfirm XML document to send to UPS.
 */
function uc_ups_shipment_request($packages, $origin, $destination, $ups_service) {
  $ups_config = \Drupal::config('uc_ups.settings');
  $account = $ups_config->get('shipper_number');
  $ua = explode(' ', $_SERVER['HTTP_USER_AGENT']);
  $user_agent = $ua[0];

  $services = _uc_ups_service_list();
  $service = array('code' => $ups_service, 'description' => $services[$ups_service]);

  $pkg_types = \Drupal\uc_ups\UPSUtilities::packageTypes();

  $store_config = \Drupal::config('uc_store.settings');
  $shipper_zone = $store_config->get('address.zone');
  $shipper_country = $store_config->get('address.country');
  $shipper_zip = $store_config->get('address.postal_code');
  $shipto_zone = $destination->zone;
  $shipto_country = $destination->country;
  $shipto_zip = $destination->postal_code;
  $shipfrom_zone = $origin->zone;
  $shipfrom_country = $origin->country;
  $shipfrom_zip = $origin->postal_code;

  $ups_units = $ups_config->get('unit_system');

  $package_schema = '';
  foreach ($packages as $package) {
    // Determine length conversion factor and weight conversion factor
    // for this shipment
    $length_factor = uc_length_conversion($package->length_units, $ups_config->get('unit_system'));
    switch ($ups_units) {
      case 'in':
        $weight_factor = uc_weight_conversion($package->weight_units, 'lb');
        $units = 'LBS';
        $unit_name = 'Pounds';
        break;
      case 'cm':
        $weight_factor = uc_weight_conversion($package->weight_units, 'kg');
        $units = 'KGS';
        $unit_name = 'Kilograms';
        break;
    }

    $qty = $package->qty;
    for ($i = 0; $i < $qty; $i++) {
      $package_type = array('code' => $package->pkg_type, 'description' => $pkg_types[$package->pkg_type]);
      $package_schema .= "<Package>";
      $package_schema .=   "<PackagingType>";
      $package_schema .=     "<Code>" . $package_type['code'] . "</Code>";
      $package_schema .=   "</PackagingType>";
      if ($package->pkg_type == '02' && $package->length && $package->width && $package->height) {
        if ($package->length < $package->width) {
          list($package->length, $package->width) = array($package->width, $package->length);
        }
        $package_schema .= "<Dimensions>";
        $package_schema .=   "<UnitOfMeasurement>";
        $package_schema .=     "<Code>" . strtoupper($ups_config->get('unit_system')) . "</Code>";
        $package_schema .=   "</UnitOfMeasurement>";
        $package_schema .=   "<Length>" . (floor($package->length * $length_factor) + 1) . "</Length>";
        $package_schema .=   "<Width>" . (floor($package->width * $length_factor) + 1) . "</Width>";
        $package_schema .=   "<Height>" . (floor($package->height * $length_factor) + 1) . "</Height>";
        $package_schema .= "</Dimensions>";
      }
      $size = $package->length * $length_factor + 2 * $length_factor * ($package->width + $package->height);
      $weight = max(1, $package->weight * $weight_factor);
      $package_schema .=   "<PackageWeight>";
      $package_schema .=     "<UnitOfMeasurement>";
      $package_schema .=       "<Code>" . $units . "</Code>";
      $package_schema .=       "<Description>" . $unit_name . "</Description>";
      $package_schema .=     "</UnitOfMeasurement>";
      $package_schema .=     "<Weight>" . number_format($weight, 1, '.', '') . "</Weight>";
      $package_schema .=   "</PackageWeight>";
      if ($size > 130 && $size <= 165) {
        $package_schema .= "<LargePackageIndicator/>";
      }
      $package_schema .=   "<PackageServiceOptions>";
      $package_schema .=     "<InsuredValue>";
      $package_schema .=       "<CurrencyCode>" . $store_config->get('currency.code') . "</CurrencyCode>";
      $package_schema .=       "<MonetaryValue>" . number_format($package->value, 2, '.', '') . "</MonetaryValue>";
      $package_schema .=     "</InsuredValue>";
      $package_schema .=   "</PackageServiceOptions>";
      $package_schema .= "</Package>";
    }
  }

  $schema = uc_ups_access_request() . "
<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<ShipmentConfirmRequest xml:lang=\"en-US\">
  <Request>
    <TransactionReference>
      <CustomerContext>Complex Rate Request</CustomerContext>
      <XpciVersion>1.0001</XpciVersion>
    </TransactionReference>
    <RequestAction>ShipConfirm</RequestAction>
    <RequestOption>validate</RequestOption>
  </Request>
  <Shipment>";
    $schema .= "<Shipper>";
      $schema .= "<Name>" . $store_config->get('name') . "</Name>";
      $schema .= "<ShipperNumber>" . $ups_config->get('shipper_number') . "</ShipperNumber>";
      if ($phone = $store_config->get('phone')) {
        $schema .= "<PhoneNumber>" . $phone . "</PhoneNumber>";
      }
      if ($fax = $store_config->get('fax')) {
        $schema .= "<FaxNumber>" . $fax . "</FaxNumber>";
      }
      if ($mail = uc_store_email()) {
        $schema .= "<EMailAddress>" . $mail . "</EMailAddress>";
      }
      $schema .= "<Address>";
        $schema .= "<AddressLine1>" . $store_config->get('address.street1') . "</AddressLine1>";
        if ($street2 = $store_config->get('address.street2')) {
          $schema .= "<AddressLine2>" . $street2 . "</AddressLine2>";
        }
        $schema .= "<City>" . $store_config->get('address.city') . "</City>";
        $schema .= "<StateProvinceCode>$shipper_zone</StateProvinceCode>";
        $schema .= "<PostalCode>$shipper_zip</PostalCode>";
        $schema .= "<CountryCode>$shipper_country</CountryCode>";
      $schema .= "</Address>";
    $schema .= "</Shipper>";
    $schema .= "<ShipTo>";
      $schema .= "<CompanyName>" . $destination->company . "</CompanyName>";
      $schema .= "<AttentionName>" . $destination->first_name . ' ' . $destination->last_name . "</AttentionName>";
      $schema .= "<PhoneNumber>" . $destination->phone . "</PhoneNumber>";
      $schema .= "<EMailAddress>" . $destination->email . "</EMailAddress>";
      $schema .= "<Address>";
        $schema .= "<AddressLine1>" . $destination->street1 . "</AddressLine1>";
        if ($destination->street2) {
          $schema .= "<AddressLine2>" . $destination->street2 . "</AddressLine2>";
        }
        $schema .= "<City>" . $destination->city . "</City>";
        $schema .= "<StateProvinceCode>$shipto_zone</StateProvinceCode>";
        $schema .= "<PostalCode>$shipto_zip</PostalCode>";
        $schema .= "<CountryCode>$shipto_country</CountryCode>";
      if ($destination->residential) {
        $schema .= "<ResidentialAddressIndicator/>";
      }
      $schema .= "</Address>";
    $schema .= "</ShipTo>";
    $schema .= "<ShipFrom>";
      $schema .= "<CompanyName>" . $origin->company . "</CompanyName>";
      $schema .= "<AttentionName>" . $origin->first_name . ' ' . $origin->last_name . "</AttentionName>";
      $schema .= "<PhoneNumber>" . $origin->phone . "</PhoneNumber>";
      $schema .= "<EMailAddress>" . $origin->email . "</EMailAddress>";
      $schema .= "<Address>";
        $schema .= "<AddressLine1>" . $origin->street1 . "</AddressLine1>";
        if ($origin->street2) {
          $schema .= "<AddressLine2>" . $origin->street2 . "</AddressLine2>";
        }
        $schema .= "<City>" . $origin->city . "</City>";
        $schema .= "<StateProvinceCode>$shipfrom_zone</StateProvinceCode>";
        $schema .= "<PostalCode>$shipfrom_zip</PostalCode>";
        $schema .= "<CountryCode>$shipfrom_country</CountryCode>";
      $schema .= "</Address>";
    $schema .= "</ShipFrom>";
    $schema .= "<PaymentInformation>";
      $schema .= "<Prepaid>";
        $schema .= "<BillShipper>";
          $schema .= "<AccountNumber>$account</AccountNumber>";
        $schema .= "</BillShipper>";
      $schema .= "</Prepaid>";
    $schema .= "</PaymentInformation>";
    if ($ups_config->get('negotiated_rates')) {
      $schema .= "<RateInformation>
          <NegotiatedRatesIndicator/>
        </RateInformation>";
    }
    $schema .= "<Service>";
      $schema .= "<Code>{$service['code']}</Code>";
      $schema .= "<Description>{$service['description']}</Description>";
    $schema .= "</Service>";
    $schema .= $package_schema;
  $schema .= "</Shipment>";
  $schema .= "<LabelSpecification>";
    $schema .= "<LabelPrintMethod>";
      $schema .= "<Code>GIF</Code>";
    $schema .= "</LabelPrintMethod>";
    $schema .= "<LabelImageFormat>";
      $schema .= "<Code>GIF</Code>";
    $schema .= "</LabelImageFormat>";
  $schema .= "</LabelSpecification>";
$schema .= "</ShipmentConfirmRequest>";

  return $schema;
}

/**
 * Last chance for user to review shipment.
 *
 * @see uc_ups_confirm_shipment_submit()
 * @see theme_uc_ups_confirm_shipment()
 * @ingroup forms
 */
function uc_ups_confirm_shipment($form, FormStateInterface $form_state, $order) {
  $form['digest'] = array(
    '#type' => 'hidden',
    '#value' => $_SESSION['ups']['digest']
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Request Pickup')
  );

  return $form;
}

/**
 * Displays final shipment information for review.
 *
 * @see uc_ups_confirm_shipment()
 * @ingroup themeable
 */
function theme_uc_ups_confirm_shipment(array $variables) {
  $form = $variables['form'];

  $output = '<div class="shipping-address"><b>' . t('Ship from:') . '</b><br />';
  $address = new Address();
  $address->first_name = SafeMarkup::checkPlain($_SESSION['ups']['origin']->first_name);
  $address->last_name = SafeMarkup::checkPlain($_SESSION['ups']['origin']->last_name);
  $address->company = SafeMarkup::checkPlain($_SESSION['ups']['origin']->company);
  $address->street1 = SafeMarkup::checkPlain($_SESSION['ups']['origin']->street1);
  $address->street1 = SafeMarkup::checkPlain($_SESSION['ups']['origin']->street2);
  $address->city = SafeMarkup::checkPlain($_SESSION['ups']['origin']->city);
  $address->zone = SafeMarkup::checkPlain($_SESSION['ups']['origin']->zone);
  $address->postal_code = SafeMarkup::checkPlain($_SESSION['ups']['origin']->postal_code);
  $address->country = SafeMarkup::checkPlain($_SESSION['ups']['origin']->country);
  $output .= (string) $address;

  $output .= '<br />' . SafeMarkup::checkPlain($_SESSION['ups']['origin']->email);
  $output .= '</div>';

  $output .= '<div class="shipping-address"><b>' . t('Ship to:') . '</b><br />';
  $address = new Address();
  $address->first_name = SafeMarkup::checkPlain($_SESSION['ups']['destination']->first_name);
  $address->last_name = SafeMarkup::checkPlain($_SESSION['ups']['destination']->last_name);
  $address->company = SafeMarkup::checkPlain($_SESSION['ups']['destination']->company);
  $address->street1 = SafeMarkup::checkPlain($_SESSION['ups']['destination']->street1);
  $address->street1 = SafeMarkup::checkPlain($_SESSION['ups']['destination']->street2);
  $address->city = SafeMarkup::checkPlain($_SESSION['ups']['destination']->city);
  $address->zone = SafeMarkup::checkPlain($_SESSION['ups']['destination']->zone);
  $address->postal_code = SafeMarkup::checkPlain($_SESSION['ups']['destination']->postal_code);
  $address->country = SafeMarkup::checkPlain($_SESSION['ups']['destination']->country);
  $output .= (string) $address;

  $output .= '<br />' . SafeMarkup::checkPlain($_SESSION['ups']['destination']->email);
  $output .= '</div>';
  $output .= '<div class="shipment-data">';
  $method = uc_ups_uc_shipping_method();
  $output .= '<b>' . $method['ups']['quote']['accessorials'][$_SESSION['ups']['service']] . '</b><br />';
  $output .= '<i>' . SafeMarkup::checkPlain($_SESSION['ups']['rate']['type']) . '</i>: ' . theme('uc_price', array('price' => $_SESSION['ups']['rate']['amount'])) . ' (' . SafeMarkup::checkPlain($_SESSION['ups']['rate']['currency']) . ') -- ';
  $output .= '<i>' . t('Paid') . '</i>: ' . $_SESSION['ups']['paid'] . '<br />';
  $ship_date = $_SESSION['ups']['ship_date'];
  $output .= 'Ship date: ' . \Drupal::service('date.formatter')->format(gmmktime(12, 0, 0, $ship_date['month'], $ship_date['day'], $ship_date['year']), 'uc_store');
  $exp_delivery = $_SESSION['ups']['expected_delivery'];
  $output .= '<br />Expected delivery: ' . \Drupal::service('date.formatter')->format(gmmktime(12, 0, 0, $exp_delivery['month'], $exp_delivery['day'], $exp_delivery['year']), 'uc_store');
  $output .= "</div>\n<br style=\"clear: both;\" />";
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Generates label and schedules pickup of the shipment.
 *
 * @see uc_ups_confirm_shipment()
 */
function uc_ups_confirm_shipment_submit($form, FormStateInterface $form_state) {
  $ups_config = \Drupal::config('uc_ups.setttings');
  // Request pickup using parameters in form.
  $order_id = $_SESSION['ups']['order_id'];
  $packages = array_keys($_SESSION['ups']['packages']);
  $request = uc_ups_request_pickup($form_state->getValue('digest'), $order_id, $packages);
  $result = \Drupal::httpClient()
    ->post($ups_config->get('connection_address') . 'ShipAccept', NULL, $request)
    ->send();
  $response = new SimpleXMLElement($result->getBody(TRUE));
  $code = (string) $response->Response->ResponseStatusCode;
  if ($code == 0) { // failed request
    $error = $response->Response->Error;
    $error_severity = (string) $error->ErrorSeverity;
    $error_code = (string) $error->ErrorCode;
    $error_description = (string) $error->ErrorDescription;
    drupal_set_message(t('(@severity error @code) @description', ['@severity' => $error_severity, '@code' => $error_code, '@description' => $error_description]), 'error');
    if ($error_severity == 'HardError') {
      $form_state['redirect'] = 'admin/store/orders/' . $order_id . '/shipments/ups/' . implode('/', $packages);
      return;
    }
  }

  $shipment = new stdClass();
  $shipment->order_id = $order_id;
  $shipment->origin = clone $_SESSION['ups']['origin'];
  $shipment->destination = clone $_SESSION['ups']['destination'];
  $shipment->packages = $_SESSION['ups']['packages'];
  $shipment->shipping_method = 'ups';
  $shipment->accessorials = $_SESSION['ups']['service'];
  $shipment->carrier = t('UPS');
  // if NegotiatedRates exist, quote based on those, otherwise, use TotalCharges
  if (isset($response->ShipmentResults->ShipmentCharges)) {
    $charge = $response->ShipmentResults->ShipmentCharges->TotalCharges;
    if (isset($response->ShipmentResults->NegotiatedRates)) {
      $charge = $response->ShipmentResults->NegotiatedRates->NetSummaryCharges->GrandTotal;
    }
  }
  $cost = (string) $charge->MonetaryValue;
  $shipment->cost = $cost;
  $shipment->tracking_number = (string) $response->ShipmentResults->ShipmentIdentificationNumber;
  $ship_date = $_SESSION['ups']['ship_date'];
  $shipment->ship_date = gmmktime(12, 0, 0, $ship_date['month'], $ship_date['day'], $ship_date['year']);
  $exp_delivery = $_SESSION['ups']['expected_delivery'];
  $shipment->expected_delivery = gmmktime(12, 0, 0, $exp_delivery['month'], $exp_delivery['day'], $exp_delivery['year']);

  foreach ($response->ShipmentResults->PackageResults as $package_results) {
    $package =& current($shipment->packages);
    $package->tracking_number = (string) $package_results->TrackingNumber;
    $label_image = (string) $package_results->LabelImage->GraphicImage;
    // Save the label
    $directory = 'public://ups_labels';
    if (file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      $label_path = $directory . '/label-' . $package->tracking_number . '.gif';
      if ($label_file = file_save_data(base64_decode($label_image), $label_path, FILE_EXISTS_REPLACE)) {
        file_usage_add($label_file, 'uc_shipping', 'package', $package->package_id);
        $package->label_image = $label_file;
      }
      else {
        drupal_set_message(t('Could not open a file to save the label image.'), 'error');
      }
    }
    else {
      drupal_set_message(t('Could not find or create the directory %directory in the file system path.', ['%directory' => $directory]), 'error');
    }
    unset($package);
    next($shipment->packages);
  }

  uc_shipping_shipment_save($shipment);

  unset($_SESSION['ups']);
  $form_state['redirect'] = 'admin/store/orders/' . $order_id . '/shipments';
}

/**
 * Constructs an XML label and pickup request.
 *
 * @param $digest
 *   Base-64 encoded shipment request.
 * @param $order_id
 *   The order id of the shipment.
 * @param $packages
 *   An array of package ids to be shipped.
 *
 * @return
 *   ShipmentAcceptRequest XML document to send to UPS.
 */
function uc_ups_request_pickup($digest, $order_id = 0, $packages = array()) {
  $packages = (array)$packages;

  $schema = uc_ups_access_request();
  $schema .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<ShipmentAcceptRequest>
  <Request>
    <RequestAction>ShipAccept</RequestAction>";
    if ($order_id || count($packages)) {
      $schema .= "\n<TransactionReference>
      <CustomerContext>";
      if ($order_id) {
        $schema .= "<OrderId>" . $order_id . "</OrderId>\n";
      }
      foreach ($packages as $pkg_id) {
        $schema .= "<PackageId>" . $pkg_id . "</PackageId>\n";
      }
      $schema .= "</CustomerContext>\n</TransactionReference>\n";
    }
  $schema .= "  </Request>
  <ShipmentDigest>" . $digest . "</ShipmentDigest>
</ShipmentAcceptRequest>";

  return $schema;
}

/**
 * Displays the shipping label for printing.
 *
 * Each argument is a component of the file path to the image.
 *
 * @ingroup themeable
 */
function theme_uc_ups_label_image() {
  $args = explode('/', Url::fromRoute('<current>')->toString(), 8);
  if (count($args) != 8) {
    throw new NotFoundHttpException();
  }

  $image_path = file_create_url(file_stream_wrapper_uri_normalize($args[7]));

  $output = <<<EOLABEL
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 3.2//EN">
<html>
<head>
<title>View/Print Label</title>
<style>
  .small-text {font-size: 80%;}
  .large-text {font-size: 115%;}
</style>
</head>
<body bgcolor="#FFFFFF">
<table border="0" cellpadding="0" cellspacing="0" width="600"><tr>
<td height="410" align="left" valign="top">
<b class="large-text">View/Print Label</b>
&nbsp;<br />
<ol class="small-text"> <li><b>Print the label:</b> &nbsp;
Select Print from the File menu in this browser window to print the label below.<br /><br /><li><b>
Fold the printed label at the dotted line.</b> &nbsp;
Place the label in a UPS Shipping Pouch. If you do not have a pouch, affix the folded label using clear plastic shipping tape over the entire label.<br /><br /><li><b>GETTING YOUR SHIPMENT TO UPS<br />
Customers without a Daily Pickup</b><ul><li>Ground, 3 Day Select, and Standard to Canada shipments must be dropped off at an authorized UPS location, or handed to a UPS driver. Pickup service is not available for these services. To find the nearest drop-off location, select the Drop-off icon from the UPS tool bar.<li>
Air shipments (including Worldwide Express and Expedited) can be picked up or dropped off. To schedule a pickup, or to find a drop-off location, select the Pickup or Drop-off icon from the UPS tool bar.  </ul> <br />
<b>Customers with a Daily Pickup</b><ul><li>
Your driver will pickup your shipment(s) as usual. </ul>
</ol></td></tr></table><table border="0" cellpadding="0" cellspacing="0" width="600">
<tr>
<td class="small-text" align="left" valign="top">
&nbsp;&nbsp;&nbsp;
FOLD HERE</td>
</tr>
<tr>
<td align="left" valign="top"><hr />
</td>
</tr>
</table>

<table>
<tr>
<td height="10">&nbsp;
</td>
</tr>
</table>

<table border="0" cellpadding="0" cellspacing="0" width="650" ><tr>
<td align="left" valign="top">
<img src="$image_path" height="392" width="672">
</td>
</tr></table>
</body>
</html>
EOLABEL;

  print $output;
  exit();
}
