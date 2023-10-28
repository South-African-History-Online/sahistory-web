<?php

namespace Drupal\Tests\tamper\Kernel;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tamper\SourceDefinition;
use Drupal\tamper\TamperInterface;
use Drupal\tamper\TamperPluginCollection;

/**
 * Tests config schema of each tamper plugin.
 *
 * @group tamper
 */
class TamperConfigSchemaTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'tamper', 'tamper_test'];

  /**
   * The config entity to add third party settings to.
   *
   * @var \Drupal\entity_test\Entity\EntityTestWithBundle
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_bundle');

    $this->entity = EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ]);
    $this->entity->save();
  }

  /**
   * Tests instantiating each plugin.
   */
  public function testCreateInstance() {
    $tamper_manager = \Drupal::service('plugin.manager.tamper');
    $plugin_collection = new TamperPluginCollection($tamper_manager, new SourceDefinition([]), []);
    foreach ($tamper_manager->getDefinitions() as $plugin_id => $plugin_definition) {
      // Create instance. DefaultLazyPluginCollection uses 'id' as plugin key.
      $plugin_collection->addInstanceId($plugin_id, [
        'id' => $plugin_id,
      ]);

      // Assert that the instance implements TamperInterface.
      $tamper = $plugin_collection->get($plugin_id);
      $this->assertInstanceOf(TamperInterface::class, $tamper);

      // Add tamper instances to the entity so that the config schema checker
      // runs.
      $this->entity->setThirdPartySetting('tamper_test', 'tampers', $plugin_collection->getConfiguration());
      $this->entity->save();
    }
  }

}
