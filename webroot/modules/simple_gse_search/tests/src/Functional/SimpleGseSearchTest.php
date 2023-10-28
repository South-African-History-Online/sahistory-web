<?php

namespace Drupal\Tests\simple_gse_search\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests if the search form submit redirects correctly.
 *
 * @group simple_gse_search
 */
class SimpleGseSearchTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'simple_gse_search'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['access gse search page']));
    $this->placeBlock('simple_gse_search_block');
  }

  /**
   * Tests searching redirects as expected.
   */
  public function testSearch() {
    $this->drupalGet('<front>');
    $this->submitForm([
      's' => 'bananas',
    ], 'go');
    $url = Url::fromRoute('simple_gse_search.search_page', [], [
      'query' => [
        's' => 'bananas',
      ],
    ])->setAbsolute()->toString();
    $this->assertEquals($url, $this->getSession()->getCurrentUrl());
    $this->assertSession()->fieldValueEquals('s', 'bananas');
  }

}
