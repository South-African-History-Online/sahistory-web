<?php

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Drupal\migrate_plus\Plugin\migrate\process\ArrayShift;
use Drupal\migrate\MigrateException;

/**
 * Tests the array shift process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\ArrayShift
 */
class ArrayShiftTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = new ArrayShift([], 'array_shift', []);
    parent::setUp();
  }

  /**
   * Data provider for testArrayShift().
   *
   * @return array
   *   An array containing input values and expected output values.
   */
  public function arrayShiftDataProvider() {
    return [
      'indexed array' => [
        'input' => ['v1', 'v2', 'v3'],
        'expected_output' => 'v1',
      ],
      'associative array' => [
        'input' => ['i1' => 'v1', 'i2' => 'v2', 'i3' => 'v3'],
        'expected_output' => 'v1',
      ],
      'empty array' => [
        'input' => [],
        'expected_output' => NULL,
      ],
    ];
  }

  /**
   * Test array shift plugin.
   *
   * @param array $input
   *   The input values.
   * @param mixed $expected_output
   *   The expected output.
   *
   * @dataProvider arrayShiftDataProvider
   */
  public function testArrayShift(array $input, $expected_output) {
    $output = $this->plugin->transform($input, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($output, $expected_output);
  }

  /**
   * Test invalid input.
   */
  public function testArrayShiftFromString() {
    $this->setExpectedException(MigrateException::class, 'Input should be an array.');
    $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
