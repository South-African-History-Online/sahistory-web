<?php

namespace Drupal\uc_product\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the product edit page tabs.
 *
 * @group Ubercart
 */
class ProductTabsTest extends UbercartTestBase {

  public static $modules = array('uc_product', 'uc_attribute', 'uc_stock');
  public static $adminPermissions = array(
    'bypass node access',
    'administer attributes',
    'administer product attributes',
    'administer product options',
    'administer product stock',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  public function testProductTabs() {
    $product = $this->createProduct();
    $this->drupalGet('node/' . $product->id() . '/edit');

    // Check we are on the edit page.
    $this->assertFieldByName('title[0][value]', $product->getTitle());

    // Check that each of the tabs exist.
    $this->assertLink('Product');
    $this->assertLink('Attributes');
    $this->assertLink('Options');
    $this->assertLink('Adjustments');
    $this->assertLink('Features');
    $this->assertLink('Stock');
  }

  public function testNonProductTabs() {
    $this->drupalCreateContentType(['type' => 'page']);
    $page = $this->drupalCreateNode(['type' => 'page']);
    $this->drupalGet('node/' . $page->id() . '/edit');

    // Check we are on the edit page.
    $this->assertFieldByName('title[0][value]', $page->getTitle());

    // Check that each of the tabs do not exist.
    $this->assertNoLink('Product');
    $this->assertNoLink('Attributes');
    $this->assertNoLink('Options');
    $this->assertNoLink('Adjustments');
    $this->assertNoLink('Features');
    $this->assertNoLink('Stock');
  }

  public function testProductTypeTabs() {
    $this->drupalGet('admin/structure/types/manage/product');

    // Check we are on the node type page.
    $this->assertFieldByName('name', 'Product');

    // Check that each of the tabs exist.
    $this->assertLink('Product attributes');
    $this->assertLink('Product options');
  }

  public function testNonProductTypeTabs() {
    $type = $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalGet('admin/structure/types/manage/' . $type->id());

    // Check we are on the node type page.
    $this->assertFieldByName('name', $type->label());

    // Check that each of the tabs do not exist.
    $this->assertNoLink('Product attributes');
    $this->assertNoLink('Product options');
  }

}
