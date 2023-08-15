<?php

namespace Drupal\Tests\migrate_tools\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Test the CSV column alias edit form.
 *
 * @requires module migrate_source_csv
 *
 * @group migrate_tools
 */
class SourceCsvFormTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * Temporary store for column assignment changes.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $store;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'csv_source_test',
    'migrate',
    'migrate_plus',
    'migrate_source_csv',
    'migrate_tools',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * The migration group for the test migration.
   *
   * @var string
   */
  protected $group;

  /**
   * The test migration id.
   *
   * @var string
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);

    // Setup the file system so we create the source CSV.
    $this->container->get('stream_wrapper_manager')->registerWrapper('public', PublicStream::class, StreamWrapperInterface::NORMAL);
    $fs = \Drupal::service('file_system');
    $fs->mkdir('public://sites/default/files', NULL, TRUE);

    // The source data for this test.
    $source_data = <<<'EOD'
vid,name,description,hierarchy,weight
tags,Tags,Use tags to group articles,0,0
forums,Sujet de discussion,Forum navigation vocabulary,1,0
test_vocabulary,Test Vocabulary,This is the vocabulary description,1,0
genre,Genre,Genre description,1,0
EOD;

    // Write the data to the filepath given in the test migration.
    file_put_contents('public://test.csv', $source_data);

    // Get the store.
    $tempStoreFactory = \Drupal::service('tempstore.private');
    $this->store = $tempStoreFactory->get('migrate_tools');

    // Select the group and migration to test.
    $this->group = 'csv_test';
    $this->migration = 'csv_source_test';
  }

  /**
   * Tests the form to edit CSV column aliases.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSourceCsvForm() {
    // Define the paths to be used.
    $executeUrlPath = "/admin/structure/migrate/manage/{$this->group}/migrations/{$this->migration}/execute";
    $editUrlPath = "/admin/structure/migrate/manage/{$this->group}/migrations/{$this->migration}/source/edit";

    // Assert the test migration is listed.
    $this->drupalGet("/admin/structure/migrate/manage/{$this->group}/migrations");
    $session = $this->assertSession();
    $session->responseContains('Test edit of column aliases for CSV source plugin');

    // Proceed to the edit page.
    $this->drupalGet($editUrlPath);
    $session->responseContains('You can change the columns to be used by this migration for each source property.');

    // Test that there are 3 select fields available which match the number of
    // properties in the process pipeline.
    $this->assertTrue($session->optionExists('edit-vid', 'vid')
      ->isSelected());
    $this->assertTrue($session->optionExists('edit-name', 'name')
      ->isSelected());
    $this->assertTrue($session->optionExists('edit-description', 'description')
      ->isSelected());
    $session->responseNotContains('edit-hierarchy');
    $session->responseNotContains('edit-weight');

    // Test that all 5 columns in the CSV source are available as options on
    // one of the select fields.
    $this->assertTrue($session->optionExists('edit-description', 'vid'));
    $this->assertTrue($session->optionExists('edit-description', 'name'));
    $this->assertTrue($session->optionExists('edit-description', 'description'));
    $this->assertTrue($session->optionExists('edit-description', 'hierarchy'));
    $this->assertTrue($session->optionExists('edit-description', 'weight'));

    // Test that two aliases can not be the same.
    $edit = [
      'edit-vid' => 2,
      'edit-name' => 1,
      'edit-description' => 1,
    ];
    $this->drupalPostForm($editUrlPath, $edit, $this->t('Submit'));
    $session->responseContains('Source properties can not share the same source column.');
    $this->assertTrue($session->optionExists('edit-vid', 'description')
      ->isSelected());
    $this->assertTrue($session->optionExists('edit-name', 'name')
      ->isSelected());
    $this->assertTrue($session->optionExists('edit-description', 'name')
      ->isSelected());

    // Test that changes to all the column aliases are saved.
    $edit = [
      'edit-vid' => 4,
      'edit-name' => 0,
      'edit-description' => 1,
    ];
    $this->drupalPostForm($editUrlPath, $edit, $this->t('Submit'));
    $this->assertTrue($session->optionExists('edit-vid', 'weight')
      ->isSelected());
    $this->assertTrue($session->optionExists('edit-name', 'vid')
      ->isSelected());
    $this->assertTrue($session->optionExists('edit-description', 'name')
      ->isSelected());

    // Test that the changes are saved to store.
    $columnConfiguration = $this->store->get('csv_source_test');
    $migrationsChanged = $this->store->get('migrations_changed');
    $this->assertSame(['csv_source_test'], $migrationsChanged);
    $expected =
      [
        'original' =>
          [
            0 => ['vid' => 'Vocabulary Id'],
            1 => ['name' => 'Name'],
            2 => ['description' => 'Description'],
          ],
        'changed' =>
          [
            4 => ['vid' => 'weight'],
            0 => ['name' => 'vid'],
            1 => ['description' => 'name'],
          ],
      ];
    $this->assertSame($expected, $columnConfiguration);

    // Test the migration with incorrect column aliases. Flush the cache to
    // ensure the plugin alter is run.
    drupal_flush_all_caches();
    $edit = [
      'operation' => 'import',
    ];
    $this->drupalPostForm($executeUrlPath, $edit, $this->t('Execute'));
    $session->responseContains("Processed 1 item (1 created, 0 updated, 0 failed, 0 ignored) - done with 'csv_source_test'");

    // Rollback.
    $edit = [
      'operation' => 'rollback',
    ];
    $this->drupalPostForm($executeUrlPath, $edit, $this->t('Execute'));

    // Restore to an order that will successfully migrate.
    $edit = [
      'edit-vid' => 0,
      'edit-name' => 1,
      'edit-description' => 2,
    ];
    $this->drupalPostForm($editUrlPath, $edit, $this->t('Submit'));
    $this->assertTrue($session->optionExists('edit-vid', 'vid')
      ->isSelected());
    $this->assertTrue($session->optionExists('edit-name', 'name')
      ->isSelected());
    $this->assertTrue($session->optionExists('edit-description', 'description')
      ->isSelected());

    // Test the vocabulary migration.
    $edit = [
      'operation' => 'import',
    ];
    drupal_flush_all_caches();
    $this->drupalPostForm($executeUrlPath, $edit, $this->t('Execute'));
    $session->responseContains("Processed 4 items (4 created, 0 updated, 0 failed, 0 ignored) - done with 'csv_source_test'");
    $this->assertEntity('tags', 'Tags', 'Use tags to group articles');
    $this->assertEntity('forums', 'Sujet de discussion', 'Forum navigation vocabulary');
    $this->assertEntity('test_vocabulary', 'Test Vocabulary', 'This is the vocabulary description');
    $this->assertEntity('genre', 'Genre', 'Genre description');
  }

  /**
   * Validate a migrated vocabulary contains the expected values.
   *
   * @param string $id
   *   Entity ID to load and check.
   * @param string $expected_name
   *   The name the migrated entity should have.
   * @param string $expected_description
   *   The description the migrated entity should have.
   */
  protected function assertEntity($id, $expected_name, $expected_description) {
    /** @var \Drupal\taxonomy\VocabularyInterface $entity */
    $entity = Vocabulary::load($id);
    $this->assertTrue($entity instanceof VocabularyInterface);
    $this->assertSame($expected_name, $entity->label());
    $this->assertSame($expected_description, $entity->getDescription());
  }

}
