<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\ConvertCase;

/**
 * Tests the convert case plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\ConvertCase
 * @group tamper
 */
class ConvertCaseTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new ConvertCase([], 'convert_case', [], $this->getMockSourceDefinition());
  }

  /**
   * Test convert to upper case.
   */
  public function testUpperCaseWithSingleValue() {
    $config = [
      ConvertCase::SETTING_OPERATION => 'strtoupper',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $this->assertEquals('FOO BAR', $plugin->tamper('foo bar'));
  }

  /**
   * Test convert to upper case.
   */
  public function testUpperCaseWithMultipleValues() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      ConvertCase::SETTING_OPERATION => 'strtoupper',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $plugin->tamper(['foo', 'bar']);
  }

  /**
   * Test convert to lower case.
   */
  public function testLowerCaseWithSingleValue() {
    $config = [
      ConvertCase::SETTING_OPERATION => 'strtolower',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $this->assertEquals('foo bar', $plugin->tamper('fOo BAR'));
  }

  /**
   * Test convert to lower case.
   */
  public function testLowerCaseWithMultipleValues() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      ConvertCase::SETTING_OPERATION => 'strtolower',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $plugin->tamper(['fOo', 'BAR']);
  }

  /**
   * Test convert to upper case first.
   */
  public function testUpperCaseFirstWithSingleValue() {
    $config = [
      ConvertCase::SETTING_OPERATION => 'ucfirst',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $this->assertEquals('Foo bar', $plugin->tamper('foo bar'));
  }

  /**
   * Test convert to upper case first.
   */
  public function testUpperCaseFirstWithMultipleValues() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      ConvertCase::SETTING_OPERATION => 'ucfirst',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $plugin->tamper(['foo bar', 'baz zip']);
  }

  /**
   * Test convert to lower case first.
   */
  public function testLowerCaseFirstWithSingleValue() {
    $config = [
      ConvertCase::SETTING_OPERATION => 'lcfirst',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $this->assertEquals('fOO BAR', $plugin->tamper('FOO BAR'));
  }

  /**
   * Test convert to lower case first.
   */
  public function testLowerCaseFirstWithMultipleValues() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      ConvertCase::SETTING_OPERATION => 'lcfirst',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $plugin->tamper(['FOO', 'BAR']);
  }

  /**
   * Test convert to upper case words.
   */
  public function testUpperCaseWordsWithSingleValue() {
    $config = [
      ConvertCase::SETTING_OPERATION => 'ucwords',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $this->assertEquals('Foo Bar', $plugin->tamper('foo bar'));
  }

  /**
   * Test convert to upper case words.
   */
  public function testUpperCaseWordsWithMultipleValues() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      ConvertCase::SETTING_OPERATION => 'ucwords',
    ];
    $plugin = new ConvertCase($config, 'convert_case', [], $this->getMockSourceDefinition());
    $plugin->tamper(['foo bar', 'bar foo']);
  }

}
