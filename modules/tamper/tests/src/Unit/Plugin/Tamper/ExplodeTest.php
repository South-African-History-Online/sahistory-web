<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Explode;

/**
 * Tests the explode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Explode
 * @group tamper
 */
class ExplodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return $this->getPluginDefaultConfig();
  }

  /**
   * Test explode.
   */
  public function testExplodeWithSingleValue() {
    $original = 'foo,bar,baz,zip';
    $expected = ['foo', 'bar', 'baz', 'zip'];
    $this->assertArrayEquals($expected, $this->getPluginDefaultConfig()->tamper($original));
  }

  /**
   * Test explode.
   */
  public function testExplodeWithMultipleValues() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $original = ['foo,bar', 'baz,zip'];
    $this->getPluginDefaultConfig()->tamper($original);
  }

  /**
   * Text explode with limit.
   */
  public function testExplodeWithSingleValueAndLimit() {
    $original = 'foo,bar,baz,zip';
    $expected = ['foo', 'bar,baz,zip'];
    $this->assertArrayEquals($expected, $this->getPluginWithLimit()->tamper($original));
  }

  /**
   * Text explode with limit.
   */
  public function testExplodeWithMultipleValuesAndLimit() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $original = ['foo,bar,baz,zip', 'fizz,bang,boop'];
    $this->getPluginWithLimit()->tamper($original);
  }

  /**
   * Returns default configuration for the plugin for this test.
   *
   * @return \Drupal\tamper\Plugin\Tamper\Explode
   *   A explode tamper plugin instance.
   */
  protected function getPluginDefaultConfig() {
    return new Explode([], 'explode', [], $this->getMockSourceDefinition());
  }

  /**
   * Returns default limit setting for the plugin for this test.
   *
   * @return \Drupal\tamper\Plugin\Tamper\Explode
   *   A explode tamper plugin instance.
   */
  protected function getPluginWithLimit() {
    $config = [
      Explode::SETTING_LIMIT => 2,
    ];
    return new Explode($config, 'explode', [], $this->getMockSourceDefinition());
  }

}
