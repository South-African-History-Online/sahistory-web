<?php

namespace Drupal\uc_usps\Plugin\Ubercart\ShippingQuote;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\uc_order\OrderInterface;

/**
 * Provides a percentage rate shipping quote plugin.
 *
 * @UbercartShippingQuote(
 *   id = "usps_intl",
 *   admin_label = @Translation("USPS International")
 * )
 */
class USPSInternationalRate extends USPSRateBase {
//   id = "usps_intl_env",

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'base_rate' => 0,
      'product_rate' => 0,
      'field' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $fields = ['' => $this->t('- None -')];
    $result = \Drupal::entityQuery('field_config')
      ->condition('field_type', 'number')
      ->execute();
    foreach (FieldConfig::loadMultiple($result) as $field) {
      $fields[$field->getName()] = $field->label();
    }

    $form['base_rate'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Base price'),
      '#description' => $this->t('The starting price for shipping costs.'),
      '#default_value' => $this->configuration['base_rate'],
      '#required' => TRUE,
    );
    $form['product_rate'] = array(
      '#type' => 'number',
      '#title' => $this->t('Default product shipping rate'),
      '#min' => 0,
      '#step' => 'any',
      '#description' => $this->t('The percentage of the item price to add to the shipping cost for an item.'),
      '#default_value' => $this->configuration['product_rate'],
      '#field_suffix' => $this->t('% (percent)'),
      '#required' => TRUE,
    );
    $form['field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Product shipping rate override field'),
      '#description' => $this->t('Overrides the default shipping rate per product for this percentage rate shipping method, when the field is attached to a product content type and has a value.'),
      '#options' => $fields,
      '#default_value' => $this->configuration['field'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['base_rate'] = $form_state->getValue('base_rate');
    $this->configuration['product_rate'] = $form_state->getValue('product_rate');
    $this->configuration['field'] = $form_state->getValue('field');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('USPS Web Tools® rate');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuotes(OrderInterface $order) {
    $rate = $this->configuration['base_rate'];
    $field = $this->configuration['field'];

    foreach ($order->products as $product) {
      if (isset($product->nid->entity->$field->value)) {
        $product_rate = $product->nid->entity->$field->value * $product->qty->value;
      }
      else {
        $product_rate = $this->configuration['product_rate'] * $product->qty->value;
      }

      $rate += $product->price->value * floatval($product_rate) / 100;
    }


    return [$rate];
  }

  /**
   * Callback for retrieving USPS shipping quote.
   *
   * @param $products
   *   Array of cart contents.
   * @param $details
   *   Order details other than product information.
   * @param $method
   *   The shipping method to create the quote.
   *
   * @return
   *   JSON object containing rate, error, and debugging information.
   */
  //public function getQuotes(OrderInterface $order) {
  public function quote($products, $details, $method) {
    $usps_config = \Drupal::config('uc_usps.settings');
    $quote_config = \Drupal::config('uc_quote.settings');
    // The uc_quote AJAX query can fire before the customer has completely
    // filled out the destination address, so check to see whether the address
    // has all needed fields. If not, abort.
    $destination = (object) $details;

    // Country code is always needed.
    if (empty($destination->country)) {
      // Skip this shipping method.
      return array();
    }

    // Shipments to the US also need zone and postal_code.
    if (($destination->country == 'US') &&
        (empty($destination->zone) || empty($destination->postal_code))) {
      // Skip this shipping method.
      return array();
    }

    // USPS Production server.
    $connection_url = 'http://production.shippingapis.com/ShippingAPI.dll';

    // Initialize $debug_data to prevent PHP notices here and in uc_quote.
    $debug_data = array('debug' => NULL, 'error' => array());
    $services = array();
    $addresses = array($quote_config->get('store_default_address'));
    $packages = $this->packageProducts($products, $addresses);
    if (!count($packages)) {
      return array();
    }

    foreach ($packages as $key => $ship_packages) {
      $orig = $addresses[$key];
      $orig->email = uc_store_email();

      if (strpos($method['id'], 'intl') && ($destination->country != 'US')) {
        // Build XML for international rate request.
        $request = $this->intlRateRequest($ship_packages, $orig, $destination);
      }
      elseif ($destination->country == 'US') {
        // Build XML for domestic rate request.
        $request = $this->rateRequest($ship_packages, $orig, $destination);
      }

      $account = \Drupal::currentUser();
      if ($account->hasPermission('configure quotes') && $quote_config->get('display_debug')) {
        $debug_data['debug'] .= htmlentities(urldecode($request)) . "<br />\n";
      }

      // Send request
      $result = \Drupal::httpClient()
        ->post($connection_url, NULL, $request)
        ->send();

      if ($account->hasPermission('configure quotes') && $quote_config->get('display_debug')) {
        $debug_data['debug'] .= htmlentities($result->getBody(TRUE)) . "<br />\n";
      }

      $rate_type = $usps_config->get('online_rates');
      $response = new SimpleXMLElement($result->getBody(TRUE));

      // Map double-encoded HTML markup in service names to Unicode characters.
      $service_markup = array(
        '&lt;sup&gt;&amp;reg;&lt;/sup&gt;'   => '®',
        '&lt;sup&gt;&amp;trade;&lt;/sup&gt;' => '™',
        '&lt;sup&gt;&#174;&lt;/sup&gt;'      => '®',
        '&lt;sup&gt;&#8482;&lt;/sup&gt;'     => '™',
        '**'                                 => '',
      );
      // Use this map to fix USPS service names.
      if (strpos($method['id'], 'intl')) {
        // Find and replace markup in International service names.
        foreach ($response->xpath('//Service') as $service) {
          $service->SvcDescription = str_replace(array_keys($service_markup), $service_markup, $service->SvcDescription);
        }
      }
      else {
        // Find and replace markup in Domestic service names.
        foreach ($response->xpath('//Postage') as $postage) {
          $postage->MailService = str_replace(array_keys($service_markup), $service_markup, $postage->MailService);
        }
      }


      if (isset($response->Package)) {
        foreach ($response->Package as $package) {
          if (isset($package->Error)) {
            $debug_data['error'][] = (string)$package->Error[0]->Description . '<br />';
          }
          else {
            if (strpos($method['id'], 'intl')) {
              foreach ($package->Service as $service) {
                $id = (string)$service['ID'];
                $services[$id]['label'] = t('U.S.P.S. @service', array('@service' => (string)$service->SvcDescription));
                // Markup rate before customer sees it.
                if (!isset($services[$id]['rate'])) {
                  $services[$id]['rate'] = 0;
                }
                $services[$id]['rate'] += $this->rateMarkup((string)$service->Postage);
              }
            }
            else {
              foreach ($package->Postage as $postage) {
                $classid = (string)$postage['CLASSID'];
                if ($classid === '0') {
                  if ((string)$postage->MailService == "First-Class Mail® Parcel") {
                    $classid = 'zeroParcel';
                  }
                  elseif ((string)$postage->MailService == "First-Class Mail® Letter") {
                    $classid = 'zeroFlat';
                  }
                  else {
                    $classid = 'zero';
                  }
                }
                if (!isset($services[$classid]['rate'])) {
                  $services[$classid]['rate'] = 0;
                }
                $services[$classid]['label'] = t('U.S.P.S. @service', array('@service' => (string)$postage->MailService));
                // Markup rate before customer sees it.
                // Rates are stored differently if ONLINE $rate_type is requested.
                // First Class doesn't have online rates, so if CommercialRate
                // is missing use Rate instead.
                if ($rate_type && !empty($postage->CommercialRate)) {
                  $services[$classid]['rate'] += $this->rateMarkup((string)$postage->CommercialRate);
                }
                else {
                  $services[$classid]['rate'] += $this->rateMarkup((string)$postage->Rate);
                }
              }
            }
          }
        }
      }
    }

    // Strip leading 'usps_'
    $method_services = substr($method['id'] . '_services', 5);
//$method_services is the name of the callback function
//  array_keys($method['quote']['accessorials'])

    $usps_services = array_filter($usps_config->get($method_services));
    foreach ($services as $service => $quote) {
      if (!in_array($service, $usps_services)) {
        unset($services[$service]);
      }
    }
    foreach ($services as $key => $quote) {
      if (isset($quote['rate'])) {
        $services[$key]['rate'] = $quote['rate'];
        $services[$key]['option_label'] = $this->getDisplayLabel($quote['label']);
      }
    }

    uasort($services, 'uc_quote_price_sort');

    // Merge debug data into $services.  This is necessary because
    // $debug_data is not sortable by a 'rate' key, so it has to be
    // kept separate from the $services data until this point.
    if (isset($debug_data['debug']) ||
        (isset($debug_data['error']) && count($debug_data['error']))) {
      $services['data'] = $debug_data;
    }

    return $services;
  }

  /**
   * Constructs a quote request for international shipments.
   *
   * @param $packages
   *   Array of packages received from the cart.
   * @param $origin
   *   Delivery origin address information.
   * @param $destination
   *   Delivery destination address information.
   *
   * @return
   *   IntlRateRequest XML document to send to USPS.
   */
  public function intlRateRequest($packages, $origin, $destination) {
    $usps_config = \Drupal::config('uc_usps.settings');
    module_load_include('inc', 'uc_usps', 'uc_usps.countries');
    $request  = '<IntlRateV2Request USERID="' . $usps_config->get('user_id') . '">';
    $request .= '<Revision>2</Revision>';

    // USPS does not use ISO 3166 country name in some cases so we
    // need to transform ISO country name into one USPS understands.
    $shipto_country = uc_usps_country_map($destination->country);

    $package_id = 0;
    foreach ($packages as $package) {
      $qty = $package->qty;
      for ($i = 0; $i < $qty; $i++) {
        $request .= '<Package ID="' . $package_id . '">' .
          '<Pounds>' . intval($package->pounds) . '</Pounds>' .
          '<Ounces>' . ceil($package->ounces) . '</Ounces>' .
          '<MailType>All</MailType>' .
          '<ValueOfContents>' . $package->price . '</ValueOfContents>' .
          '<Country>' . $shipto_country . '</Country>' .
          '<Container>' . 'RECTANGULAR' . '</Container>' .
          '<Size>' . 'REGULAR' . '</Size>' .
          '<Width>' . '</Width>' .
          '<Length>' . '</Length>' .
          '<Height>' . '</Height>' .
          '<Girth>' . '</Girth>' .
          '<OriginZip>' . substr(trim($origin->postal_code), 0, 5) . '</OriginZip>';

          // Check if we need to add any special services to this package.
          if ($usps_config->get('insurance')) {
            $request .= '<ExtraServices><ExtraService>1</ExtraService></ExtraServices>';
          }

          // Close off Package tag.
          $request .= '</Package>';

        $package_id++;
      }
    }
    $request .= '</IntlRateV2Request>';

    $request = 'API=IntlRateV2&XML=' . UrlHelper::encodePath($request);
    return $request;
  }
}
