<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Unique;

/**
 * Test the unique plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Unique
 * @group tamper
 */
class UniqueTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Unique([], 'unique', [], $this->getMockSourceDefinition());
  }

  /**
   * Tests unique with a single value.
   */
  public function testUniqueWithSingleValue() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be an array.');
    $this->plugin->tamper('foo');
  }

  /**
   * Tests unique with multiple values.
   */
  public function testUniqueWithMultipleValues() {
    $original = ['foo', 'foo', 'bar', 'baz', 'baz'];
    $expected = ['foo', 'bar', 'baz'];
    $this->assertArrayEquals($expected, $this->plugin->tamper($original));
  }

}
