<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\FindReplaceRegex;

/**
 * Tests the find and replace regex plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\FindReplaceRegex
 * @group tamper
 */
class FindReplaceRegexTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new FindReplaceRegex([], 'find_replace_regex', [], $this->getMockSourceDefinition());
  }

  /**
   * Test the plugin with a single value.
   */
  public function testSingleValue() {
    $config = [
      FindReplaceRegex::SETTING_FIND => '/cat/',
      FindReplaceRegex::SETTING_REPLACE => 'dog',
      FindReplaceRegex::SETTING_LIMIT => '',
    ];
    $plugin = new FindReplaceRegex($config, 'find_replace_regex', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The cat went to the park.'));
  }

  /**
   * Test the plugin as case sensitive.
   */
  public function testSingleValueCaseSensitive() {
    $config = [
      FindReplaceRegex::SETTING_FIND => '/cat/i',
      FindReplaceRegex::SETTING_REPLACE => 'dog',
      FindReplaceRegex::SETTING_LIMIT => '',
    ];
    $plugin = new FindReplaceRegex($config, 'find_replace_regex', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper('The Cat went to the park.'));
  }

  /**
   * Test the plugin as respecting word boundaries.
   */
  public function testSingleValueWordBoundaries() {
    $config = [
      FindReplaceRegex::SETTING_FIND => '/cat\b/i',
      FindReplaceRegex::SETTING_REPLACE => 'dog',
      FindReplaceRegex::SETTING_LIMIT => '',
    ];
    $plugin = new FindReplaceRegex($config, 'find_replace_regex', [], $this->getMockSourceDefinition());
    $this->assertEquals('The Catwent to the park.', $plugin->tamper('The Catwent to the park.'));
  }

  /**
   * Test the plugin with line break.
   */
  public function testSingleValueLineBreak() {
    $config = [
      FindReplaceRegex::SETTING_FIND => '/cat\n/',
      FindReplaceRegex::SETTING_REPLACE => 'dog',
      FindReplaceRegex::SETTING_LIMIT => '',
    ];
    $plugin = new FindReplaceRegex($config, 'find_replace_regex', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper("The cat\n went to the park."));

    $config = [
      FindReplaceRegex::SETTING_FIND => '/cat\r\n/',
      FindReplaceRegex::SETTING_REPLACE => 'dog',
      FindReplaceRegex::SETTING_LIMIT => '',
    ];
    $plugin = new FindReplaceRegex($config, 'find_replace_regex', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper("The cat\r\n went to the park."));

  }

  /**
   * Test the plugin with whitespace.
   */
  public function testSingleValueWhiteSpace() {
    $config = [
      FindReplaceRegex::SETTING_FIND => '/cat\s/',
      FindReplaceRegex::SETTING_REPLACE => 'dog',
      FindReplaceRegex::SETTING_LIMIT => '',
    ];
    $plugin = new FindReplaceRegex($config, 'find_replace_regex', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper("The cat\n went to the park."));

    $config = [
      FindReplaceRegex::SETTING_FIND => '/cat\t/',
      FindReplaceRegex::SETTING_REPLACE => 'dog',
      FindReplaceRegex::SETTING_LIMIT => '',
    ];
    $plugin = new FindReplaceRegex($config, 'find_replace_regex', [], $this->getMockSourceDefinition());
    $this->assertEquals('The dog went to the park.', $plugin->tamper("The cat\t went to the park."));
  }

}
