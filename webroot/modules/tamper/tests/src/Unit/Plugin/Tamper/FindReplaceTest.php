<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\FindReplace;

/**
 * Tests the find and replace plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\FindReplace
 * @group tamper
 */
class FindReplaceTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      FindReplace::SETTING_FIND => '',
      FindReplace::SETTING_REPLACE => '',
      FindReplace::SETTING_CASE_SENSITIVE => FALSE,
      FindReplace::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplace::SETTING_WHOLE => FALSE,
    ];
    return new FindReplace($config, 'find_replace', [], $this->getMockSourceDefinition());
  }

  /**
   * Test the plugin with a single value.
   */
  public function testSingleValue() {
    $config = [
      FindReplace::SETTING_FIND => 'cat',
      FindReplace::SETTING_REPLACE => 'dog',
      FindReplace::SETTING_CASE_SENSITIVE => FALSE,
      FindReplace::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplace::SETTING_WHOLE => FALSE,
    ];
    $plugin = new FindReplace($config, 'find_replace', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The Cat went to the park.'));
    $this->assertEquals('The dogwent to the park.', $plugin->tamper('The Catwent to the park.'));
  }

  /**
   * Test the plugin as case sensitve.
   */
  public function testSingleValueCaseSensitive() {
    $config = [
      FindReplace::SETTING_FIND => 'cat',
      FindReplace::SETTING_REPLACE => 'dog',
      FindReplace::SETTING_CASE_SENSITIVE => TRUE,
      FindReplace::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplace::SETTING_WHOLE => FALSE,
    ];
    $plugin = new FindReplace($config, 'find_replace', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('The Cat went to the park.', $plugin->tamper('The Cat went to the park.'));
    $this->assertEquals('The dogwent to the park.', $plugin->tamper('The catwent to the park.'));
  }

  /**
   * Test the plugin as respecting word boundaries.
   */
  public function testSingleValueWordBoundaries() {
    $config = [
      FindReplace::SETTING_FIND => 'cat',
      FindReplace::SETTING_REPLACE => 'dog',
      FindReplace::SETTING_CASE_SENSITIVE => FALSE,
      FindReplace::SETTING_WORD_BOUNDARIES => TRUE,
      FindReplace::SETTING_WHOLE => FALSE,
    ];
    $plugin = new FindReplace($config, 'find_replace', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The Cat went to the park.'));
    $this->assertEquals('The catwent to the park.', $plugin->tamper('The catwent to the park.'));
  }

  /**
   * Test the plugin as replace whole words only.
   */
  public function testSingleValueWhole() {
    $config = [
      FindReplace::SETTING_FIND => 'cat',
      FindReplace::SETTING_REPLACE => 'dog',
      FindReplace::SETTING_CASE_SENSITIVE => FALSE,
      FindReplace::SETTING_WORD_BOUNDARIES => FALSE,
      FindReplace::SETTING_WHOLE => TRUE,
    ];
    $plugin = new FindReplace($config, 'find_replace', [], $this->getMockSourceDefinition());
    $this->assertEquals('The cat went to the park.', $plugin->tamper('The cat went to the park.'));
    $this->assertEquals('dog', $plugin->tamper('cat'));
    $this->assertEquals('dog', $plugin->tamper('Cat'));
  }

  /**
   * Test the plugin with a multiple values.
   */
  public function testMultipleValues() {
    $plugin = new FindReplace([], 'find_replace', [], $this->getMockSourceDefinition());
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $plugin->tamper(['foo', 'bar', 'baz']);
  }

}
