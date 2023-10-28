<?php

namespace Drupal\Tests\feeds_tamper\Functional;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Provides a base class for Feeds Tamper functional tests.
 */
abstract class FeedsTamperBrowserTestBase extends FeedsBrowserTestBase {

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an user with Feeds admin privileges.
    $this->adminUser = $this->drupalCreateUser([
      'administer feeds',
      'administer feeds_tamper',
    ]);
    $this->drupalLogin($this->adminUser);
  }

}
