<?php

namespace Drupal\Tests\google_cse\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Verifies Google CSE configuration functionality.
 *
 * @group google_cse
 */
class GoogleCSEConfigTest extends BrowserTestBase {
  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
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
   * An unauthorized user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUnauthorizedUser;

  /**
   * Create user for the test.
   */
  protected function initializeTestUser() {
    $this->testUser = $this->drupalCreateUser([
      'search Google CSE',
      'administer search',
    ]);
    $this->testUnauthorizedUser = $this->drupalCreateUser();
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
    // Login as our account.
    $this->drupalLogin($this->testUser);

    // Start the session.
    $session = $this->assertSession();

    // Get the settings form path from the route.
    $settings_form_path = Url::fromRoute('search.add_type', ['search_plugin_id' => 'google_cse_search']);

    // Navigate to the settings form.
    $this->drupalGet($settings_form_path);

    // Assure we loaded settings with proper permissions.
    $session->statusCodeEquals(200);

    // Verify the correct field schemae exist.
    $fields = [
      'edit-path',
      'edit-cx',
      'edit-results-display-here',
      'edit-custom-results-display-overlay',
    ];

    foreach ($fields as $field) {
      $session->fieldExists($field);
    }

    // Verify the values can be saved.
    $edit = [
      'edit-id' => 'test_search',
      'edit-path' => 'test-search',
      'edit-cx' => '0000',
      'edit-results-display-here' => 'here',
      'edit-custom-results-display-overlay' => 'overlay',
    ];
    $this->drupalPostForm(NULL, $edit, 'edit-submit');
    $session->statusCodeEquals(200);
    $this->drupalLogout();

    // Verify that the unauthorized user can't access the config.
    $this->drupalLogin($this->testUnauthorizedUser);
    // Navigate to the settings form.
    $this->drupalGet($settings_form_path);
    // Confirm no access for unauthorized user.
    $session->statusCodeEquals(403);
    $this->drupalLogout();

    // Verify that anonymous users can't access the config.
    // Navigate to the settings form.
    $this->drupalGet($settings_form_path);
    // Confirm no access for anonymous user.
    $session->statusCodeEquals(403);

    // Confirm configuration matches.
    $config = $this->config('search.page.test_search');
    $this->assertEquals('0000', $config->get('configuration')['cx']);
    $this->assertEquals('overlay', $config->get('configuration')['custom_results_display']);
    $this->assertEquals('here', $config->get('configuration')['results_display']);
  }

}
