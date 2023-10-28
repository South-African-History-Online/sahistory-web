<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\ArrayFilter;

/**
 * Tests the array filter plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\ArrayFilter
 * @group tamper
 */
class ArrayFilterTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new ArrayFilter([], 'array_filter', [], $this->getMockSourceDefinition());
  }

  /**
   * Test the array filter plugin with a single value.
   */
  public function testArrayFilterWithSingleValue() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be an array.');
    $this->plugin->tamper('foo');
  }

  /**
   * Test the array filter plugin with a multiple values.
   */
  public function testArrayFilterWithMultipleValues() {
    $original = ['foo', 0, '', 'bar', FALSE, 'baz', [], 'zip'];
    $expected = ['foo', 'bar', 'baz', 'zip'];
    $this->assertArrayEquals($expected, $this->plugin->tamper($original));
  }

}
