<?php

namespace Drupal\uc_catalog\Tests;

use Drupal\Core\Language\Language;
use Drupal\taxonomy\Entity\Term;
use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests for the Ubercart catalog breadcrumbs.
 *
 * @group Ubercart
 */
class CatalogBreadcrumbTest extends UbercartTestBase {

  public static $modules = array('uc_catalog');
  public static $adminPermissions = array('view catalog');

  /**
   * @inheritDoc
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the product node breadcrumb.
   */
  public function testProductBreadcrumb() {
    $this->drupalLogin($this->adminUser);

    $grandparent = $this->createTerm();
    $parent = $this->createTerm(['parent' => $grandparent->id()]);
    $term = $this->createTerm(['parent' => $parent->id()]);
    $product = $this->createProduct(array(
      'taxonomy_catalog' => array($term->id()),
    ));

    $this->drupalGet($product->toUrl());

    // Fetch each node title in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $links = array_map('strval', $links);
    $this->assertEqual(count($links), 5, 'The correct number of links were found.');
    $this->assertEqual($links[0], 'Home');
    $this->assertEqual($links[1], 'Catalog');
    $this->assertEqual($links[2], $grandparent->label());
    $this->assertEqual($links[3], $parent->label());
    $this->assertEqual($links[4], $term->label());
  }

  /**
   * Tests the catalog view breadcrumb.
   */
  public function testCatalogBreadcrumb() {
    $this->drupalLogin($this->adminUser);

    $grandparent = $this->createTerm();
    $parent = $this->createTerm(['parent' => $grandparent->id()]);
    $term = $this->createTerm(['parent' => $parent->id()]);
    $product = $this->createProduct(array(
      'taxonomy_catalog' => array($term->id()),
    ));

    $this->drupalGet('catalog');
    $this->clickLink($grandparent->label());
    $this->clickLink($parent->label());
    $this->clickLink($term->label());

    // Fetch each node title in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $links = array_map('strval', $links);
    $this->assertEqual(count($links), 4, 'The correct number of links were found.');
    $this->assertEqual($links[0], 'Home');
    $this->assertEqual($links[1], 'Catalog');
    $this->assertEqual($links[2], $grandparent->label());
    $this->assertEqual($links[3], $parent->label());
  }

  /**
   * Returns a new term with random properties in the catalog vocabulary.
   */
  protected function createTerm($values = []) {
    $term = Term::create($values + [
      'name' => $this->randomMachineName(),
      'description' => array(
        'value' => $this->randomMachineName(),
        'format' => 'plain_text',
      ),
      'vid' => 'catalog',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();
    return $term;
  }

}
