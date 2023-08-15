<?php

namespace Drupal\Tests\google_cse\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;
use Drupal\search\Entity\SearchPage;

/**
 * Verifies Google CSE renders on a search page.
 *
 * @group google_cse
 */
class GoogleCSEDisplayTest extends BrowserTestBase {
  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'google_cse',
    'search',
  ];

  /**
   * A user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * Create user for the test.
   */
  protected function initializeTestUser() {
    $this->testUser = $this->drupalCreateUser([
      'administer blocks',
      'administer search',
      'search content',
      'search Google CSE',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->strictConfigSchema = NULL;
    parent::setUp();
    $this->initializeTestUser();
  }

  /**
   * Test configuration.
   */
  public function testConfiguration() {

    $this->drupalLogin($this->testUser);

    // Start the session.
    $session = $this->assertSession();

    // Get the settings form path from the route.
    $settings_form_path = Url::fromRoute('search.add_type', ['search_plugin_id' => 'google_cse_search']);
    $search_page_path = 'test-search';
    $search_id = "google_cse_search";

    // Put the search block on pages.
    $this->drupalGet('admin/structure/block');
    $this->getSession()->getPage()->findLink('Place block')->click();
    $this->assertLinkByHref(
        '/admin/structure/block/add/search_form_block/classy',
        0,
        'Did not find the search block in block candidate list.'
    );
    $block = $this->drupalPlaceBlock('search_form_block');

    // Navigate to the settings form.
    $this->drupalGet($settings_form_path);

    // Create a Google CSE Search page.
    $edit = [
      'edit-id' => $search_id,
      'edit-path' => $search_page_path,
      'edit-cx' => '0000',
      'edit-results-display-here' => 'here',
      'edit-custom-results-display-overlay' => 'overlay',
    ];
    $this->drupalPostForm(NULL, $edit, 'edit-submit');

    // Set this as the default search.
    $search_page_repository = \Drupal::service('search.search_page_repository');
    $entity = SearchPage::load($search_id);
    $search_page_repository->setDefaultSearchPage($entity);
    $entity_id = $search_page_repository->getDefaultSearchPage();

    // Test the default search via the block form, from the front page.
    $this->drupalGet('');
    $terms = ['keys' => 'test'];
    $this->drupalPostForm('', $terms, t('Search'));

    $this->assertEqual(
    $this->getUrl(),
    \Drupal::url('search.view_' . $entity_id, [], ['query' => ['keys' => $terms['keys']], 'absolute' => TRUE]),
    'Submitted to correct URL.'
    );

    $session->elementExists('css', "div#google-cse-results");
  }

}
