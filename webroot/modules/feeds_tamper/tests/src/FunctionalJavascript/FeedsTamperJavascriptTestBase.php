<?php

namespace Drupal\Tests\feeds_tamper\FunctionalJavascript;

use Drupal\Tests\feeds\FunctionalJavascript\FeedsJavascriptTestBase;

/**
 * Base class for Feeds Tamper javascript tests.
 */
abstract class FeedsTamperJavascriptTestBase extends FeedsJavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'feeds',
    'feeds_tamper',
    'node',
    'user',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogout();

    // Create an user with Feeds admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
      'administer feeds_tamper',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Asserts that the logs do not contain PHP errors.
   */
  protected function assertNoPhpErrorsInLog() {
    $logs = \Drupal::database()->select('watchdog', 'w')
      ->fields('w')
      ->condition('w.type', 'php', '=')
      ->execute()
      ->fetchAll();

    $message = 'There were no PHP errors.';
    if (!empty($logs)) {
      $errors = [];
      foreach ($logs as $log_entry) {
        // Format the error message.
        $log_entry->variables = unserialize($log_entry->variables);
        $errors[] = strtr($log_entry->message, $log_entry->variables);
      }
      $message = implode("\n", $errors);
    }
    $this->assertEmpty($logs, $message);
  }

}
