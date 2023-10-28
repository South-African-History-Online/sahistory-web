<?php

namespace Drupal\Tests\tamper\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\SourceDefinitionInterface;

/**
 * Tests chaining multiple tampers together.
 *
 * @group tamper
 */
class ChainedTamperTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['tamper'];

  /**
   * Tests the outcome of chaining multiple tamper plugins together.
   *
   * @param mixed $expected
   *   The expected outcome.
   * @param mixed $value
   *   The input.
   * @param array $tampers
   *   A list of tampers to apply along with their config. Each item in the
   *   array should consist of the following:
   *   - plugin: (string) the ID of the Tamper plugin to apply.
   *   - config: (array) Configuration for the plugin.
   *   - expected_exception: (string, optional) if applying a plugin should
   *     result into an exception, this key should be set to the expected
   *     Exception class.
   *
   * @dataProvider chainedTampersDataProvider
   */
  public function testChainedTampers($expected, $value, array $tampers) {
    $manager = \Drupal::service('plugin.manager.tamper');
    $multiple = FALSE;

    foreach ($tampers as $plugin_data) {
      $plugin_data['config']['source_definition'] = $this->createMock(SourceDefinitionInterface::class);
      $tamper = $manager->createInstance($plugin_data['plugin'], $plugin_data['config']);

      if (isset($plugin_data['expected_exception'])) {
        $this->expectException($plugin_data['expected_exception']);
      }

      $definition = $tamper->getPluginDefinition();
      // Many plugins expect a scalar value but the current value of the
      // pipeline might be multiple scalars (this is set by the previous
      // plugin) and in this case the current value needs to be iterated
      // and each scalar separately transformed.
      if ($multiple && !$definition['handle_multiples']) {
        $new_value = [];
        foreach ($value as $scalar_value) {
          $new_value[] = $tamper->tamper($scalar_value);
        }
        $value = $new_value;
      }
      else {
        $value = $tamper->tamper($value);
        $multiple = $tamper->multiple();
      }
    }

    $this->assertEquals($expected, $value);
  }

  /**
   * Data provider for testChainedTampers().
   */
  public function chainedTampersDataProvider() {
    return [
      [
        'expected' => 'a|b|c',
        'input' => 'a,b,c',
        'tampers' => [
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ',',
            ],
          ],
          [
            'plugin' => 'implode',
            'config' => [
              'glue' => '|',
            ],
          ],
        ],
      ],
      [
        'expected' => NULL,
        'input' => 'a,b,c',
        'tampers' => [
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ',',
            ],
          ],
          [
            'plugin' => 'implode',
            'config' => [
              'glue' => '|',
            ],
          ],
          [
            'plugin' => 'implode',
            'config' => [
              'glue' => ';',
            ],
            'expected_exception' => TamperException::class,
          ],
        ],
      ],
      [
        'expected' => [
          ['a', 'b', 'c'],
          [1, 2],
        ],
        'input' => 'a,b,c;1,2',
        'tampers' => [
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ';',
            ],
          ],
          [
            'plugin' => 'explode',
            'config' => [
              'separator' => ',',
            ],
          ],
        ],
      ],
    ];
  }

}
