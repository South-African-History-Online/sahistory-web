<?php

namespace Drupal\uc_quote\Tests;

use Drupal\uc_country\Entity\Country;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_quote\Entity\ShippingQuoteMethod;
use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests shipping quote functionality.
 *
 * @group Ubercart
 */
class QuoteTest extends UbercartTestBase {

  public static $modules = array(/*'rules_admin', */'uc_payment', 'uc_payment_pack', 'uc_quote');
  public static $adminPermissions = array('configure quotes'/*, 'administer rules', 'bypass rules access'*/);

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->createPaymentMethod('check');

    // In order to test zone-based conditions, this particular test class
    // assumes that US is enabled as default, and CA is also enabled.
    Country::load('US')->enable()->save();
    Country::load('CA')->enable()->save();
    \Drupal::configFactory()->getEditable('uc_store.settings')->set('address.country', 'US')->save();
  }

  /**
   * Creates a flat rate shipping quote with optional conditions.
   *
   * @param array $edit
   *   Data to use to create shipping quote.
   * @param bool $condition
   *   If specified, a RulesAnd component defining the conditions to apply
   *   for this method.
   *
   * @return \Drupal\uc_quote\Entity\ShippingQuoteMethod
   *   The shipping quote configuration entity.
   */
  protected function createQuote($edit = [], $condition = FALSE) {
    $edit += [
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
      'status' => 1,
      'weight' => 0,
      'plugin' => 'flatrate',
      'settings' => [
        'base_rate' => mt_rand(1, 10),
        'product_rate' => mt_rand(1, 10),
      ],
    ];
    $method = ShippingQuoteMethod::create($edit);
    $method->save();
    // if ($condition) {
    //   $name = 'get_quote_from_flatrate_' . $method->mid;
    //   $condition['LABEL'] = $edit['label'] . ' conditions';
    //   $oldconfig = rules_config_load($name);
    //   $newconfig = rules_import(array($name => $condition));
    //   $newconfig->id = $oldconfig->id;
    //   unset($newconfig->is_new);
    //   $newconfig->status = ENTITY_CUSTOM;
    //   $newconfig->save();
    //   entity_flush_caches();
    // }
    return $method;
  }

  /**
   * Simulates selection of a delivery country on the checkout page.
   *
   * @param $country_id
   *   The ISO 3166 2-character country code to select. Country must
   *   be enabled for this to work.
   */
  protected function selectCountry($country_id) {
    $country_name = \Drupal::service('country_manager')->getCountry($country_id)->name;
    $dom = new \DOMDocument();
    $dom->loadHTML($this->content);
    $parent = $dom->getElementById('edit-panes-delivery-delivery-country');
    $options = $parent->getElementsByTagName('option');
    for ($i = 0; $i < $options->length; $i++) {
      if ($options->item($i)->textContent == $country_name) {
        $options->item($i)->setAttribute('selected', 'selected');
      }
      else {
        $options->item($i)->removeAttribute('selected');
      }
    }
    $this->setRawContent($dom->saveHTML());
    return $this->drupalPostAjaxForm(NULL, [], 'panes[delivery][country]');
  }

  /**
   * Simulates selection of a quote on the checkout page.
   *
   * @param $n
   *   The index of the quote to select.
   */
  protected function selectQuote($n) {
    // Get the list of available quotes.
    $xpath = '//*[@name="panes[quotes][quotes][quote_option]"]';
    $elements = $this->xpath($xpath);
    $vals = array();
    foreach ($elements as $element) {
      $vals[(string) $element['id']] = (string) $element['value'];
    }

    // Set the checked attribute of the chosen quote.
    $dom = new \DOMDocument();
    $dom->loadHTML($this->content);
    $i = 0;
    $selected = '';
    foreach ($vals as $id => $value) {
      if ($i == $n) {
        $dom->getElementById($id)->setAttribute('checked', 'checked');
        $selected = $value;
      }
      else {
        $dom->getElementById($id)->removeAttribute('checked');
      }
      $i++;
    }
    $this->setRawContent($dom->saveHTML());

    // Post the selection via Ajax.
    $option = array('panes[quotes][quotes][quote_option]' => $selected);
    return $this->drupalPostAjaxForm(NULL, [], $option);
  }

  /**
   * Verifies shipping pane is hidden when there are no shippable items.
   */
  public function testNoQuote() {
    $product = $this->createProduct(array('shippable' => 0));
    $quote = $this->createQuote();
    $this->addToCart($product);
    $this->drupalPostForm('cart', array('items[0][qty]' => 1), t('Checkout'));
    $this->assertNoText('Calculate shipping cost', 'Shipping pane is not present with no shippable item.');
  }

  /**
   * Tests basic flatrate shipping quote functionality.
   */
  public function testQuote() {
    // Create product and quotes.
    $product = $this->createProduct();
    $quote1 = $this->createQuote();
    $quote2 = $this->createQuote(['weight' => 1], array(
      'LABEL' => 'quote_conditions',
      'PLUGIN' => 'and',
      'REQUIRES' => array('rules'),
      'USES VARIABLES' => array(
        'order' => array(
          'type' => 'uc_order',
          'label' => 'Order'
        ),
      ),
      'AND' => array( array(
        'data_is' => array(
          'data' => array('order:delivery-address:country'),
          'value' => 'US',
        ),
      )),
    ));
    // Define strings to test for.
    $qty = mt_rand(2, 100);
    foreach ([$quote1, $quote2] as $quote) {
      $configuration = $quote->getPluginConfiguration();
      $quote->amount = uc_currency_format($configuration['base_rate'] + $configuration['product_rate'] * $qty);
      $quote->option_text = $quote->label() . ': ' . $quote->amount;
      $quote->total = uc_currency_format($product->price->value * $qty + $configuration['base_rate'] + $configuration['product_rate'] * $qty);
    }

    // Add product to cart, update qty, and go to checkout page.
    $this->addToCart($product);
    $this->drupalPostForm('cart', array('items[0][qty]' => $qty), t('Checkout'));
    $this->assertText($quote1->label(), 'The default quote option is available');
    $this->assertText($quote2->label(), 'The second quote option is available');
    $this->assertText($quote1->total, 'Order total includes the default quote.');

    // Select a different quote and ensure the total updates correctly.  Currently, we have to do this
    // by examining the ajax return value directly (rather than the page contents) because drupalPostAjaxForm() can
    // only handle replacements via the 'wrapper' property, and the ajax callback may use a command with a selector.
    $edit = array('panes[quotes][quotes][quote_option]' => $quote2->id() . '---0');
    $edit = $this->populateCheckoutForm($edit);
    $result = $this->ucPostAjax(NULL, $edit, $edit);
    $this->assertText($quote2->total, 'The order total includes the selected quote.');

    // @todo Re-enable when shipping quote conditions are available.
    // Switch to a different country and ensure the ajax updates the page correctly.
    // $edit['panes[delivery][country]'] = 'CA';
    // $result = $this->ucPostAjax(NULL, $edit, 'panes[delivery][country]');
    // $this->assertText($quote1->option_text, 'The default quote is still available after changing the country.');
    // $this->assertNoText($quote2->option_text, 'The second quote is no longer available after changing the country.');
    // $this->assertText($quote1->total, 'The total includes the default quote.');

    // Proceed to review page and ensure the correct quote is present.
    $edit['panes[quotes][quotes][quote_option]'] = $quote1->id() . '---0';
    $edit = $this->populateCheckoutForm($edit);
    $this->drupalPostForm(NULL, $edit, t('Review order'));
    $this->assertRaw(t('Your order is almost complete.'));
    $this->assertText($quote1->total, 'The total is correct on the order review page.');

    // Submit the review order page.
    $this->drupalPostForm(NULL, [], t('Submit order'));
    $order_ids = \Drupal::entityQuery('uc_order')
      ->condition('delivery_first_name', $edit['panes[delivery][first_name]'])
      ->execute();
    $order_id = reset($order_ids);

    if ($order_id) {
      $order = Order::load($order_id);
      foreach ($order->line_items as $line) {
        if ($line['type'] == 'shipping') {
          break;
        }
      }
      // Verify line item is correct.
      $this->assertEqual($line['type'], 'shipping', 'The shipping line item was saved to the order.');
      $this->assertEqual($quote1->amount, uc_currency_format($line['amount']), 'Stored shipping line item has the correct amount.');

      // Verify order total is correct on order-view form.
      $this->drupalGet('admin/store/orders/' . $order_id);
      $this->assertText($quote1->total, 'The total is correct on the order admin view page.');

      // Verify shipping line item is correct on order edit form.
      $this->drupalGet('admin/store/orders/' . $order_id . '/edit');
      $this->assertFieldByName('line_items[' . $line['line_item_id'] . '][title]', $quote1->label(), 'Found the correct shipping line item title.');
      $this->assertFieldByName('line_items[' . $line['line_item_id'] . '][amount]', substr($quote1->amount, 1), 'Found the correct shipping line item title.');

      // Verify that the "get quotes" button works as expected.
      $result = $this->ucPostAjax('admin/store/orders/' . $order_id . '/edit', [], ['op' => t('Get shipping quotes')]);
      $this->assertText($quote1->option_text, 'The default quote is available on the order-edit page.');
      // @todo Change to assertNoText when shipping quote conditions are available.
      $this->assertText($quote2->option_text, 'The second quote is available on the order-edit page.');
    }
    else {
      $this->fail('No order was created.');
    }

  }
}
