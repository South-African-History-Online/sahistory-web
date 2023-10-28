<?php

namespace Drupal\csv_importer\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests CSV importer.
 *
 * @group csv_importer
 */
class ImporterTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['csv_importer', 'csv_importer_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $account = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'access user profiles',
    ]);

    $this->drupalLogin($account);

    Node::create([
      'nid' => 1111,
      'title' => 'CSV importer reference node',
      'type' => 'csv_importer_test_content',
    ])->save();

    User::create([
      'uid' => 1111,
      'name' => 'John Doe',
      'roles' => [$this->createAdminRole()],
    ])->save();

    Term::create([
      'tid' => 1111,
      'name' => 'CSV importer taxonomy reference',
      'vid' => 'csv_importer_taxonomy',
    ]);
  }

  /**
   * Test node importer.
   */
  public function testNodeCsvImporter() {
    $assert = $this->assertSession();

    $this->processForm('node', 'csv_importer_test_content');

    $this->drupalGet('/csv-importer-node-1');
    $assert->statusCodeEquals(200);

    $assert->elementTextContains('css', '.field--name-title', 'CSV importer node 1');
    $this->assertFields();
  }

  /**
   * Test taxonomy term importer.
   */
  public function testTaxonomyTermCsvImporter() {
    $assert = $this->assertSession();

    $this->processForm('taxonomy_term', 'csv_importer_taxonomy');

    $this->drupalGet('/csv-importer-term-1');
    $assert->statusCodeEquals(200);

    $assert->elementTextContains('css', '.field--name-name', 'CSV importer term 1');
    $this->assertFields();
  }

  /**
   * Test user importer.
   */
  public function testUserCsvImporter() {
    $assert = $this->assertSession();

    $this->processForm('user');

    $this->drupalGet('/user/7');
    $assert->statusCodeEquals(200);

    $assert->elementTextContains('css', '.page-title', 'CSV importer user 1');
    $this->assertFields();

  }

  /**
   * Process form.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string|null $entity_type_bundle
   *   Entity type bundle.
   */
  protected function processForm(string $entity_type, string $entity_type_bundle = NULL) {
    $assert = $this->assertSession();
    $this->drupalGet('admin/config/development/csv-importer');
    $assert->statusCodeEquals(200);

    $page = $this->getSession()->getPage();
    $page->selectFieldOption('entity_type', $entity_type);
    $assert->assertWaitOnAjaxRequest();

    if ($entity_type_bundle) {
      $page->selectFieldOption('entity_type_bundle', $entity_type_bundle);
    }

    $page->attachFileToField('files[csv]', drupal_get_path('module', 'csv_importer_test') . "/content/csv_example_{$entity_type}_test.csv");
    $assert->assertWaitOnAjaxRequest();

    $page->pressButton('CSV import');
    $assert->assertWaitOnAjaxRequest();
  }

  /**
   * Assert fields.
   */
  protected function assertFields() {
    $assert = $this->assertSession();

    $assert->elementTextContains('css', '.field--name-field-boolean', 'On');
    $assert->elementTextContains('css', '.field--name-field-email', 'example@example.com');
    $assert->elementContains('css', '.field--name-field-link', '<a href="http://example.com">CSV importer link title</a>');
    $assert->elementTextContains('css', '.field--name-field-timestamp', 'Fri, 01/12/2018 - 21:45');

    $assert->elementTextContains('css', '.field--name-field-list-float', '17.1');
    $assert->elementTextContains('css', '.field--name-field-list-integer', '18');
    $assert->elementTextContains('css', '.field--name-field-list-text', 'List text 3');

    $assert->elementTextContains('css', '.field--name-field-number-decimal', '17.10');
    $assert->elementTextContains('css', '.field--name-field-float-number', '17.20');
    $assert->elementTextContains('css', '.field--name-field-integer-number', '17');

    $assert->elementContains('css', '.field--name-field-content-reference', '<a href="/node/1111" hreflang="en">CSV importer reference node</a>');
    $assert->elementContains('css', '.field--name-field-user-reference', '<a href="/user/1111" hreflang="en">John Doe</a>');

    $this->assertSession()->elementContains('css', '.field--name-field-text-formatted', '<strong>Formatted text</strong>');
    $this->assertSession()->elementContains('css', '.field--name-field-text-formatted-long', '<strong>Formatted text long</strong>');
    $this->assertSession()->elementContains('css', '.field--name-field-text-formatted-summary', '<strong>Formatted text summary</strong>');
    $assert->elementTextContains('css', '.field--name-field-text-plain', 'Plain text');
    $assert->elementTextContains('css', '.field--name-field-text-plain-long', 'Plain text long');
  }

}
