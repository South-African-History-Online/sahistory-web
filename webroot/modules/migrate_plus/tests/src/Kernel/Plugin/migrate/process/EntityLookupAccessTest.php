<?php

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;

/**
 * Tests Entity Lookup access check.
 *
 * @group migrate_plus
 *
 * @requires entity
 * @requires profile
 */
class EntityLookupAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity',
    'field',
    'migrate',
    'migrate_plus',
    'node',
    'profile',
    'system',
    'user',
    'views',
    'text',
  ];

  /**
   * The mocked migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $migration;

  /**
   * The mocked migrate executable.
   *
   * @var \Drupal\migrate\MigrateExecutable|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $executable;

  /**
   * The migrate row.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('user');
    $this->installConfig(['profile', 'system']);

    $known_user = $this->createUser([], 'lucuma');

    // Create a profile entity.
    ProfileType::create(['id' => 'default']);
    Profile::create([
      'uid' => $known_user->id(),
      'type' => 'default',
    ])->save();

    $migration_prophecy = $this->prophesize(MigrationInterface::class);
    $migrate_destination_prophecy = $this->prophesize(MigrateDestinationInterface::class);
    $migrate_destination_prophecy->getPluginId()->willReturn('profile');
    $migrate_destination = $migrate_destination_prophecy->reveal();
    $migration_prophecy->getDestinationPlugin()
      ->willReturn($migrate_destination);
    $migration_prophecy->getProcess()->willReturn([]);
    $this->migration = $migration_prophecy->reveal();
    $this->executable = $this->prophesize(MigrateExecutableInterface::class)
      ->reveal();
    $this->row = new Row();
  }

  /**
   * Tests entity_lookup access_check configuration key.
   */
  public function testEntityLookupAccessCheck() {
    $configuration_base = [
      'entity_type' => 'profile',
      'value_key' => 'profile_id',
    ];

    // Set access_check true.
    $configuration = $configuration_base +
      [
        'access_check' => TRUE,
      ];
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('entity_lookup', $configuration, $this->migration);

    // Check the profile is not found.
    $value = $plugin->transform('1', $this->executable, $this->row, 'profile_id');
    $this->assertNull($value);

    // Retest with access check false.
    $configuration = $configuration_base +
      [
        'access_check' => FALSE,
      ];
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('entity_lookup', $configuration, $this->migration);

    // Check the profile is found.
    $value = $plugin->transform('1', $this->executable, $this->row, 'profile_id');
    $this->assertSame('1', $value);
  }

}
