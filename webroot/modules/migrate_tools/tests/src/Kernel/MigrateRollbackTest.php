<?php

namespace Drupal\Tests\migrate_tools\Kernel;

use Drupal\migrate_tools\MigrateExecutable;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests rolling back of imports.
 *
 * @group migrate
 */
class MigrateRollbackTest extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['taxonomy']);
  }

  /**
   * Tests rolling back configuration and content entities.
   */
  public function testRollback() {
    // We use vocabularies to demonstrate importing and rolling back
    // configuration entities.
    $vocabulary_data_rows = [
      ['id' => '1', 'name' => 'categories', 'weight' => '2'],
      ['id' => '2', 'name' => 'tags', 'weight' => '1'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'id' => 'vocabularies',
      'migration_tags' => ['Import and rollback test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $vocabulary_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'vid' => 'id',
        'name' => 'name',
        'weight' => 'weight',
      ],
      'destination' => ['plugin' => 'entity:taxonomy_vocabulary'],
    ];

    /** @var \Drupal\migrate\Plugin\MigrationInterface $vocabulary_migration */
    $vocabulary_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $vocabulary_id_map = $vocabulary_migration->getIdMap();

    // Import and validate vocabulary config entities were created.
    $executable = new MigrateExecutable($vocabulary_migration, $this, []);
    $executable->import();
    foreach ($vocabulary_data_rows as $row) {
      /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
      $vocabulary = Vocabulary::load($row['id']);
      $this->assertTrue($vocabulary);
      $map_row = $vocabulary_id_map->getRowBySource(['id' => $row['id']]);
      $this->assertNotNull($map_row['destid1']);
    }

    // Test id list rollback.
    $rollback_executable = new MigrateExecutable($vocabulary_migration, $this, ['idlist' => 1]);
    $rollback_executable->rollback();
    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = Vocabulary::load(1);
    $this->assertFalse($vocabulary);
    $map_row = $vocabulary_id_map->getRowBySource(['id' => 1]);
    $this->assertFalse($map_row);

    // TODO: remove after 8.6 is sunset.
    // @see https://www.drupal.org/project/migrate_tools/issues/3008316
    include_once $this->root . '/core/includes/install.core.inc';
    $version = _install_get_version_info(\Drupal::VERSION);
    if ($version['minor'] == 6) {
      /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
      $vocabulary = Vocabulary::load(1);
      $this->assertFalse($vocabulary);
      $map_row = $vocabulary_id_map->getRowBySource(['id' => 1]);
      $this->assertFalse($map_row);
    }
    else {
      /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
      $vocabulary = Vocabulary::load(2);
      $this->assertTrue($vocabulary);
      $map_row = $vocabulary_id_map->getRowBySource(['id' => 2]);
      $this->assertNotNull($map_row['destid1']);
    }

  }

}
