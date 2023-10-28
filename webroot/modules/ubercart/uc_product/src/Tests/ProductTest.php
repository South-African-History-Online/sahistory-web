<?php

namespace Drupal\uc_product\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the product content type.
 *
 * @group Ubercart
 */
class ProductTest extends UbercartTestBase {

  public static $modules = array('path', 'uc_product');
  public static $adminPermissions = array('administer content types');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  public function testProductAdmin() {
    $this->drupalGet('admin/store/products/view');
    $this->assertText('Title');
    $this->assertText($this->product->getTitle());
    $this->assertText('Price');
    $this->assertText(uc_currency_format($this->product->price->value));
  }

  public function testProductNodeForm() {
    $this->drupalGet('node/add/product');

    $fields = array(
      'model[0][value]',
      'price[0][value]',
      'shippable[value]',
      'weight[0][value]',
      'weight[0][units]',
      'dimensions[0][length]',
      'dimensions[0][width]',
      'dimensions[0][height]',
      'dimensions[0][units]',
      'files[uc_product_image_0][]',
    );
    foreach ($fields as $field) {
      $this->assertFieldByName($field, NULL);
    }

    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';

    // Make a node with those fields.
    $edit = array(
      $title_key => $this->randomMachineName(32),
      $body_key => $this->randomMachineName(64),
      'model[0][value]' => $this->randomMachineName(8),
      'price[0][value]' => mt_rand(1, 150),
      'shippable[value]' => mt_rand(0, 1),
      'weight[0][value]' => mt_rand(1, 50),
      'weight[0][units]' => array_rand(array(
        'lb' => t('Pounds'),
        'kg' => t('Kilograms'),
        'oz' => t('Ounces'),
        'g'  => t('Grams'),
      )),
      'dimensions[0][length]' => mt_rand(1, 50),
      'dimensions[0][width]' => mt_rand(1, 50),
      'dimensions[0][height]' => mt_rand(1, 50),
      'dimensions[0][units]' => array_rand(array(
        'in' => t('Inches'),
        'ft' => t('Feet'),
        'cm' => t('Centimeters'),
        'mm' => t('Millimeters'),
      )),
    );
    $this->drupalPostForm('node/add/product', $edit, 'Save');

    $this->assertText(t('Product @title has been created.', ['@title' => $edit[$title_key]]), 'Product created.');
    $this->assertText($edit[$body_key], 'Product body found.');
    $this->assertText($edit['model[0][value]'], 'Product model found.');
    $this->assertNoUniqueText(uc_currency_format($edit['price[0][value]']), 'Product price found.');
    $this->assertText(uc_weight_format($edit['weight[0][value]'], $edit['weight[0][units]']), 'Product weight found.');
    $this->assertText(uc_length_format($edit['dimensions[0][length]'], $edit['dimensions[0][units]']), 'Product length found.');
    $this->assertText(uc_length_format($edit['dimensions[0][width]'], $edit['dimensions[0][units]']), 'Product width found.');
    $this->assertText(uc_length_format($edit['dimensions[0][height]'], $edit['dimensions[0][units]']), 'Product height found.');

    $elements = $this->xpath('//body[contains(@class, "uc-product-node")]');
    $this->assertEqual(count($elements), 1, 'Product page contains body CSS class.');

    // Update the node fields.
    $edit = array(
      $title_key => $this->randomMachineName(32),
      $body_key => $this->randomMachineName(64),
      'model[0][value]' => $this->randomMachineName(8),
      'price[0][value]' => mt_rand(1, 150),
      'shippable[value]' => mt_rand(0, 1),
      'weight[0][value]' => mt_rand(1, 50),
      'weight[0][units]' => array_rand(array(
        'lb' => t('Pounds'),
        'kg' => t('Kilograms'),
        'oz' => t('Ounces'),
        'g'  => t('Grams'),
      )),
      'dimensions[0][length]' => mt_rand(1, 50),
      'dimensions[0][width]' => mt_rand(1, 50),
      'dimensions[0][height]' => mt_rand(1, 50),
      'dimensions[0][units]' => array_rand(array(
        'in' => t('Inches'),
        'ft' => t('Feet'),
        'cm' => t('Centimeters'),
        'mm' => t('Millimeters'),
      )),
    );
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->assertText(t('Product @title has been updated.', ['@title' => $edit[$title_key]]), 'Product updated.');
    $this->assertText($edit[$body_key], 'Updated product body found.');
    $this->assertText($edit['model[0][value]'], 'Updated product model found.');
    $this->assertNoUniqueText(uc_currency_format($edit['price[0][value]']), 'Updated product price found.');
    $this->assertText(uc_weight_format($edit['weight[0][value]'], $edit['weight[0][units]']), 'Product weight found.');
    $this->assertText(uc_length_format($edit['dimensions[0][length]'], $edit['dimensions[0][units]']), 'Product length found.');
    $this->assertText(uc_length_format($edit['dimensions[0][width]'], $edit['dimensions[0][units]']), 'Product width found.');
    $this->assertText(uc_length_format($edit['dimensions[0][height]'], $edit['dimensions[0][units]']), 'Product height found.');

    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertText(t('Product @title has been deleted.', ['@title' => $edit[$title_key]]), 'Product deleted.');
  }

  public function testZeroProductWeightAndDimensions() {
    $edit = array(
      'title[0][value]' => $this->randomMachineName(32),
      'model[0][value]' => $this->randomMachineName(8),
      'price[0][value]' => mt_rand(1, 150),
      'shippable[value]' => mt_rand(0, 1),
      'weight[0][value]' => 0,
      'weight[0][units]' => array_rand(array(
        'lb' => t('Pounds'),
        'kg' => t('Kilograms'),
        'oz' => t('Ounces'),
        'g'  => t('Grams'),
      )),
      'dimensions[0][length]' => 0,
      'dimensions[0][width]' => 0,
      'dimensions[0][height]' => 0,
      'dimensions[0][units]' => array_rand(array(
        'in' => t('Inches'),
        'ft' => t('Feet'),
        'cm' => t('Centimeters'),
        'mm' => t('Millimeters'),
      )),
    );
    $this->drupalPostForm('node/add/product', $edit, 'Save');

    $this->assertText(t('Product @title has been created.', ['@title' => $edit['title[0][value]']]), 'Product created.');
    $this->assertNoText('Weight', 'Zero weight not shown.');
    $this->assertNoText('Dimensions', 'Zero dimensions not shown.');
  }

  public function testProductClassForm() {
    // Try making a new product class.
    $class = strtolower($this->randomMachineName(12));
    $edit = array(
      'type' => $class,
      'name' => $class,
      'description' => $this->randomMachineName(32),
      'uc_product[product]' => 1,
    );
    $this->drupalPostForm('admin/structure/types/add', $edit, 'Save content type');
    $this->assertTrue(uc_product_is_product($class), 'The new content type is a product class.');

    // Make an existing node type a product class.
    $type = $this->drupalCreateContentType(array(
      'description' => $this->randomMachineName(),
    ));
    $edit = array(
      'uc_product[product]' => 1,
    );

    $this->drupalPostForm('admin/structure/types/manage/' . $type->getOriginalId(), $edit, 'Save content type');
    $this->assertTrue(uc_product_is_product($type->getOriginalId()), 'The updated content type is a product class.');

    // Check the product classes page
    $this->drupalGet('admin/store/products/classes');
    $this->assertText($type->getOriginalId(), 'Product class is listed.');
    $this->assertText($type->getDescription(), 'Product class description is listed.');
    $this->assertLinkByHref('admin/structure/types/manage/' . $type->getOriginalId(), 0, 'Product class edit link is shown.');
    $this->assertLinkByHref('admin/structure/types/manage/' . $type->getOriginalId() . '/delete', 0, 'Product class delete link is shown.');

    // Remove the product class again.
    $edit = array(
      'uc_product[product]' => 0,
    );

    $this->drupalPostForm('admin/structure/types/manage/' . $class, $edit, 'Save content type');
    $this->assertTrue(uc_product_is_product($class), 'The updated content type is no longer a product class.');
  }

  public function testProductQuantity() {
    $edit = array('uc_product_add_to_cart_qty' => TRUE);
    $this->drupalPostForm('admin/store/config/products', $edit, 'Save configuration');

    // Check zero quantity message.
    $this->addToCart($this->product, array('qty' => 0));
    $this->assertText('The quantity cannot be zero.');

    // Check invalid quantity messages.
    $this->addToCart($this->product, array('qty' => 'x'));
    $this->assertText('The quantity must be an integer.');

    $this->addToCart($this->product, array('qty' => '1a'));
    $this->assertText('The quantity must be an integer.');

    // Check cart add message.
    $this->addToCart($this->product, array('qty' => 1));
    $this->assertText($this->product->getTitle() . ' added to your shopping cart.');

    // Check cart update message.
    $this->addToCart($this->product, array('qty' => 1));
    $this->assertText('Your item(s) have been updated.');
  }

}
