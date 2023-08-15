<?php

namespace Drupal\Tests\feeds\Functional\Update;

use Drupal\feeds\Entity\FeedType;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Provides tests for updating deprecated action ID's in feed types.
 *
 * @group feeds
 * @group Update
 * @group legacy
 */
class UpdateNonExistentActionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['feeds', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/feeds-8.x-3.0-alpha6-feeds_installed.php',
      __DIR__ . '/../../../fixtures/feed_type.deprecated-action-ids.php',
    ];
  }

  /**
   * Tests replacing use of deprecated action ID's with the new ones.
   */
  public function testUpdateActionsUpdateNonExistent() {
    // Run the updates.
    $this->runUpdates();

    // Ensure the action ID's were updated for all feed types.
    $expected = [
      'article_importer1' => 'entity:unpublish_action:node',
      'article_importer2' => 'entity:publish_action:node',
      'article_importer3' => '_delete',
      'article_importer4' => 'entity:unpublish_action:node',
    ];
    foreach ($expected as $feed_type_id => $expected_action_id) {
      $update_non_existent_action = FeedType::load($feed_type_id)
        ->getProcessor()
        ->getConfiguration('update_non_existent');
      $this->assertEquals($expected_action_id, $update_non_existent_action);
    }
  }

}
