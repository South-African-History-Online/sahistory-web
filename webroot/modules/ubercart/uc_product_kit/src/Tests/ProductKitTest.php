<?php

namespace Drupal\uc_product_kit\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests product kit functionality.
 *
 * @group Ubercart
 */
class ProductKitTest extends UbercartTestBase {

  public static $modules = array('uc_product_kit');
  public static $adminPermissions = array('create product_kit content', 'edit any product_kit content');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need page_title_block because we test page titles
    $this->drupalPlaceBlock('page_title_block');
  }

  public function testProductKitNodeForm() {
    $this->drupalLogin($this->adminUser);

    // Allow the default quantity to be set.
    $edit = array('uc_product_add_to_cart_qty' => TRUE);
    $this->drupalPostForm('admin/store/config/products', $edit, 'Save configuration');

    // Create some test products.
    $products = array();
    for ($i = 0; $i < 3; $i++) {
      $products[$i] = $this->createProduct();
    }

    // Test the product kit fields.
    $this->drupalGet('node/add/product_kit');
    foreach (array('mutable', 'products[]') as $field) {
      $this->assertFieldByName($field);
    }

    // Test creation of a basic kit.
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $edit = array(
      $title_key => $this->randomMachineName(32),
      $body_key => $this->randomMachineName(64),
      'products[]' => array(
        $products[0]->id(),
        $products[1]->id(),
        $products[2]->id(),
      ),
    );
    $this->drupalPostForm('node/add/product_kit', $edit, 'Save');
    $this->assertText(t('Product kit @title has been created.', ['@title' => $edit[$title_key]]));
    $this->assertText($edit[$body_key], 'Product kit body found.');
    $this->assertText('1 × ' . $products[0]->label(), 'Product 1 title found.');
    $this->assertText('1 × ' . $products[1]->label(), 'Product 2 title found.');
    $this->assertText('1 × ' . $products[2]->label(), 'Product 3 title found.');
    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $this->assertText(uc_currency_format($total), 'Product kit total found.');
  }

  public function testProductKitDiscounts() {
    $this->drupalLogin($this->adminUser);

    // Create some test products and a kit.
    $products = array();
    for ($i = 0; $i < 3; $i++) {
      $products[$i] = $this->createProduct();
    }
    $kit = $this->drupalCreateNode(array(
      'type' => 'product_kit',
      'title[0][value]' => $this->randomMachineName(32),
      'products' => array(
        $products[0]->id(),
        $products[1]->id(),
        $products[2]->id(),
      ),
      'mutable' => UC_PRODUCT_KIT_UNMUTABLE_NO_LIST,
    ));

    // Test the product kit extra fields available to configure discounts.
    $this->drupalGet('node/' . $kit->id() . '/edit');
    $this->assertFieldByName('kit_total');
    foreach ($products as $product) {
      $this->assertFieldByName('items[' . $product->id() . '][qty]');
      $this->assertFieldByName('items[' . $product->id() . '][ordering]');
      $this->assertFieldByName('items[' . $product->id() . '][discount]');
    }

    // Set some discounts.
    $discounts = array(
      mt_rand(-100, 100),
      mt_rand(-100, 100),
      mt_rand(-100, 100),
    );
    $edit = array(
      'items[' . $products[0]->id() . '][discount]' => $discounts[0],
      'items[' . $products[1]->id() . '][discount]' => $discounts[1],
      'items[' . $products[2]->id() . '][discount]' => $discounts[2],
    );
    $this->drupalPostForm('node/' . $kit->id() . '/edit', $edit, 'Save');

    // Check the discounted total.
    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $total += array_sum($discounts);
    $this->assertText(uc_currency_format($total), 'Discounted product kit total found.');

    // Check the discounts on the edit page.
    $this->drupalGet('node/' . $kit->id() . '/edit');
    $this->assertText('Currently, the total price is ' . uc_currency_format($total), 'Discounted product kit total found.');
    $this->assertFieldByName('items[' . $products[0]->id() . '][discount]', $discounts[0]);
    $this->assertFieldByName('items[' . $products[1]->id() . '][discount]', $discounts[1]);
    $this->assertFieldByName('items[' . $products[2]->id() . '][discount]', $discounts[2]);

    // Set the kit total.
    $total = 2 * ($products[0]->price->value + $products[1]->price->value + $products[2]->price->value);
    $this->drupalPostForm('node/' . $kit->id() . '/edit', array('kit_total' => $total), 'Save');

    // Check the fixed total.
    $this->assertText(uc_currency_format($total), 'Fixed product kit total found.');

    // Check the discounts on the edit page.
    $this->drupalGet('node/' . $kit->id() . '/edit');
    $this->assertFieldByName('kit_total', $total);
    $this->assertFieldByName('items[' . $products[0]->id() . '][discount]', $products[0]->price->value);
    $this->assertFieldByName('items[' . $products[1]->id() . '][discount]', $products[1]->price->value);
    $this->assertFieldByName('items[' . $products[2]->id() . '][discount]', $products[2]->price->value);

    // Reset the kit prices so the discounts should equal zero.
    $edit = array(
      'price[0][value]' => $total - ($products[1]->price->value + $products[2]->price->value),
    );
    $this->drupalPostForm('node/' . $products[0]->id() . '/edit', $edit, 'Save');

    // Check the kit total is still the same.
    $this->drupalGet('node/' . $kit->id());
    $this->assertText(uc_currency_format($total), 'Fixed product kit total found.');

    // Check the discounts are zeroed on the edit page.
    $this->drupalGet('node/' . $kit->id() . '/edit');
    $this->assertFieldByName('kit_total', $total);
    $this->assertFieldByName('items[' . $products[0]->id() . '][discount]', '0.000');
    $this->assertFieldByName('items[' . $products[1]->id() . '][discount]', '0.000');
    $this->assertFieldByName('items[' . $products[2]->id() . '][discount]', '0.000');
  }

  public function testProductKitMutability() {
    $this->drupalLogin($this->adminUser);

    // Create some test products and prepare a kit.
    $products = array();
    for ($i = 0; $i < 3; $i++) {
      $products[$i] = $this->createProduct();
    }
    $kit_data = array(
      'type' => 'product_kit',
      'title[0][value]' => $this->randomMachineName(32),
      'products' => array(
        $products[0]->id(),
        $products[1]->id(),
        $products[2]->id(),
      ),
    );

    // Test kits with no listing.
    $kit_data['mutable'] = UC_PRODUCT_KIT_UNMUTABLE_NO_LIST;
    $kit = $this->drupalCreateNode($kit_data);
    $this->drupalGet('node/' . $kit->id());
    $this->assertText($kit->label(), 'Product kit title found.');
    $this->assertNoText($products[0]->label(), 'Product 1 title not shown.');
    $this->assertNoText($products[1]->label(), 'Product 2 title not shown.');
    $this->assertNoText($products[2]->label(), 'Product 3 title not shown.');

    $this->addToCart($kit);
    $this->drupalGet('cart');
    $this->assertText($kit->label(), 'Product kit title found.');
    $this->assertNoText($products[0]->label(), 'Product 1 title not shown.');
    $this->assertNoText($products[1]->label(), 'Product 2 title not shown.');
    $this->assertNoText($products[2]->label(), 'Product 3 title not shown.');

    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $this->assertTextPattern('/Subtotal:\s*' . preg_quote(uc_currency_format($total)) . '/', 'Product kit total found.');

    $qty = mt_rand(2, 10);
    $this->drupalPostForm(NULL, array('items[2][qty]' => $qty), 'Update cart');
    $this->assertTextPattern('/Subtotal:\s*' . preg_quote(uc_currency_format($total * $qty)) . '/', 'Updated product kit total found.');

    $this->drupalPostForm(NULL, array(), 'Remove');
    $this->assertText('There are no products in your shopping cart.');

    // Test kits with listing.
    $kit_data['mutable'] = UC_PRODUCT_KIT_UNMUTABLE_WITH_LIST;
    $kit = $this->drupalCreateNode($kit_data);
    $this->drupalGet('node/' . $kit->id());
    $this->assertText($kit->label(), 'Product kit title found.');
    $this->assertText($products[0]->label(), 'Product 1 title shown.');
    $this->assertText($products[1]->label(), 'Product 2 title shown.');
    $this->assertText($products[2]->label(), 'Product 3 title shown.');

    $this->addToCart($kit);
    $this->drupalGet('cart');
    $this->assertText($kit->label(), 'Product kit title found.');
    $this->assertText($products[0]->label(), 'Product 1 title shown.');
    $this->assertText($products[1]->label(), 'Product 2 title shown.');
    $this->assertText($products[2]->label(), 'Product 3 title shown.');

    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $this->assertTextPattern('/Subtotal:\s*' . preg_quote(uc_currency_format($total)) . '/', 'Product kit total found.');

    $qty = mt_rand(2, 10);
    $this->drupalPostForm(NULL, array('items[2][qty]' => $qty), 'Update cart');
    $this->assertTextPattern('/Subtotal:\s*' . preg_quote(uc_currency_format($total * $qty)) . '/', 'Updated product kit total found.');

    $this->drupalPostForm(NULL, array(), 'Remove');
    $this->assertText('There are no products in your shopping cart.');

    // Test mutable kits.
    $kit_data['mutable'] = UC_PRODUCT_KIT_MUTABLE;
    $kit = $this->drupalCreateNode($kit_data);
    $this->drupalGet('node/' . $kit->id());
    $this->assertText($kit->label(), 'Product kit title found.');
    $this->assertText($products[0]->label(), 'Product 1 title shown.');
    $this->assertText($products[1]->label(), 'Product 2 title shown.');
    $this->assertText($products[2]->label(), 'Product 3 title shown.');

    $this->addToCart($kit);
    $this->drupalGet('cart');
    $this->assertNoText($kit->label(), 'Product kit title not shown.');
    $this->assertText($products[0]->label(), 'Product 1 title shown.');
    $this->assertText($products[1]->label(), 'Product 2 title shown.');
    $this->assertText($products[2]->label(), 'Product 3 title shown.');

    $total = $products[0]->price->value + $products[1]->price->value + $products[2]->price->value;
    $this->assertTextPattern('/Subtotal:\s*' . preg_quote(uc_currency_format($total)) . '/', 'Product kit total found.');

    $qty = array(mt_rand(2, 10), mt_rand(2, 10), mt_rand(2, 10));
    $edit = array(
      'items[0][qty]' => $qty[0],
      'items[1][qty]' => $qty[1],
      'items[2][qty]' => $qty[2],
    );
    $this->drupalPostForm(NULL, $edit, 'Update cart');
    $total = $products[0]->price->value * $qty[0];
    $total += $products[1]->price->value * $qty[1];
    $total += $products[2]->price->value * $qty[2];
    $this->assertTextPattern('/Subtotal:\s*' . preg_quote(uc_currency_format($total)) . '/', 'Updated product kit total found.');

    $this->drupalPostForm(NULL, array(), 'Remove');
    $this->drupalPostForm(NULL, array(), 'Remove');
    $this->drupalPostForm(NULL, array(), 'Remove');
    $this->assertText('There are no products in your shopping cart.');
  }

}
