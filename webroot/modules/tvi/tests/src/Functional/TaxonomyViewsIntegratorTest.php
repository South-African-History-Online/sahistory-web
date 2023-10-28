<?php

namespace Drupal\Tests\tvi\Functional;

/**
 * Tests TaxonomyViewsIntegrator and various term configurations.
 *
 * @group tvi
 */
class TaxonomyViewsIntegratorTest extends TaxonomyViewsIntegratorTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $permissions = [
      'access content',
      'administer site configuration',
      'administer views',
      'administer nodes',
      'administer taxonomy',
      'access administration pages',
      'define view for vocabulary ' . $this->vocabulary1->id(),
      'define view for terms in ' . $this->vocabulary1->id(),
      'define view for vocabulary ' . $this->vocabulary2->id(),
      'define view for terms in ' . $this->vocabulary2->id(),
    ];

    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the user can see the form to set TVI settings on a given term.
   */
  public function testTaxonomyHasTaxonomyViewsIntegratorSettingForm() {
    $this->drupalGet('taxonomy/term/' . $this->term1->id() . '/edit');
    $this->assertSession()->responseContains('Taxonomy Views Integrator Settings');
    $this->assertSession()->responseContains('The default view to use for this term page.');
  }

  /**
   * Test that the user can see the form to set TVI settings on a given term.
   */
  public function testViewLoadsFromTermSettings() {
    // Expect page_1 display of tvi_test_view and not the default view.
    $this->drupalGet('taxonomy/term/' . $this->term1->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // Expect page_2 display of tvi_test_view and not the default view.
    $this->drupalGet('taxonomy/term/' . $this->term2->id());
    $this->assertSession()->responseContains('TVI Bar View');

    // Expect page_1 display of tvi_test_view and not the default view.
    $this->drupalGet('taxonomy/term/' . $this->term3->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // Expect page_1 display of tvi_test_view and not the default view.
    $this->drupalGet('taxonomy/term/' . $this->term4->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // Expect page_1 display, it should override term2 settings.
    $this->drupalGet('taxonomy/term/' . $this->term5->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // Inherit term2 settings.
    $this->drupalGet('taxonomy/term/' . $this->term6->id());
    $this->assertSession()->responseContains('TVI Bar View');

    // Inherit the vocab settings.
    $this->drupalGet('taxonomy/term/' . $this->term7->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // Expect page_1 display of tvi_test_view and not the default view.
    $this->drupalGet('taxonomy/term/' . $this->term8->id());
    $this->assertSession()->responseContains('TVI Foo View');

    // Expect the default taxonomy view.
    $this->drupalGet('taxonomy/term/' . $this->term9->id());
    $this->assertSession()->responseNotContains('TVI Foo View');
    $this->assertSession()->responseContains($this->term9->label());

    // Expect page_2 display of tvi_test_view and not the default view.
    $this->drupalGet('taxonomy/term/' . $this->term10->id());
    $this->assertSession()->responseContains('TVI Bar View');

    // Inherit term10 settings.
    $this->drupalGet('taxonomy/term/' . $this->term11->id());
    $this->assertSession()->responseContains('TVI Bar View');

    // Expect page_1 display of tvi_test_view.
    $this->drupalGet('taxonomy/term/' . $this->term12->id());
    $this->assertSession()->responseContains('TVI Foo View');
  }

}
