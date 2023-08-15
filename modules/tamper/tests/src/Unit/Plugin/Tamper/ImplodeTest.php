<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Implode;

/**
 * Tests the implode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Implode
 * @group tamper
 */
class ImplodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      Implode::SETTING_GLUE => ',',
    ];
    return new Implode($config, 'implode', [], $this->getMockSourceDefinition());
  }

  /**
   * Tests imploding with a single value.
   */
  public function testImplodeWithSingleValue() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be an array.');
    $original = 'foo';
    $this->plugin->tamper($original);
  }

  /**
   * Tests imploding with multiple values.
   */
  public function testImplodeWithMultipleValues() {
    $original = ['foo', 'bar', 'baz'];
    $expected = 'foo,bar,baz';
    $this->assertEquals($expected, $this->plugin->tamper($original));
  }

}
