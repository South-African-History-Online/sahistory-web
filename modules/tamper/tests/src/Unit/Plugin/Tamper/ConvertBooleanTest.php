<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\ConvertBoolean;

/**
 * Tests the convert boolean plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\ConvertBoolean
 * @group tamper
 */
class ConvertBooleanTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => FALSE,
      ConvertBoolean::SETTING_NO_MATCH => 'No match',
      ConvertBoolean::SETTING_OTHER_TEXT => '',
    ];
    return new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
  }

  /**
   * Test convert to boolean basic functionality.
   */
  public function testConvertBooleanBasicFunctionality() {
    $this->assertEquals(TRUE, $this->plugin->tamper('A'));
    $this->assertEquals(TRUE, $this->plugin->tamper('a'));
    $this->assertEquals(FALSE, $this->plugin->tamper('B'));
    $this->assertEquals(FALSE, $this->plugin->tamper('b'));
    $this->assertEquals('No match', $this->plugin->tamper('c'));
    $this->assertEquals('No match', $this->plugin->tamper('C'));
  }

  /**
   * Test convert to boolean no match false case.
   */
  public function testConvertBooleanNoMatchFalse() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => FALSE,
      ConvertBoolean::SETTING_NO_MATCH => 'pass',
      ConvertBoolean::SETTING_OTHER_TEXT => '',
    ];
    $plugin = new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
    $this->assertEquals(TRUE, $plugin->tamper('A'));
    $this->assertEquals(TRUE, $plugin->tamper('a'));
    $this->assertEquals(FALSE, $plugin->tamper('B'));
    $this->assertEquals(FALSE, $plugin->tamper('b'));
    $this->assertEquals('c', $plugin->tamper('c'));
    $this->assertEquals('C', $plugin->tamper('C'));
  }

  /**
   * Test convert to boolean no match true case.
   */
  public function testConvertBooleanNoMatchTrue() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => TRUE,
      ConvertBoolean::SETTING_NO_MATCH => 'No match',
      ConvertBoolean::SETTING_OTHER_TEXT => '',
    ];
    $plugin = new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
    $this->assertEquals(TRUE, $plugin->tamper('A'));
    $this->assertNotEquals(TRUE, $plugin->tamper('a'));
    $this->assertEquals(FALSE, $plugin->tamper('B'));
    $this->assertNotEquals(FALSE, $plugin->tamper('b'));
  }

  /**
   * Test convert to boolean no match true case.
   */
  public function testConvertBooleanNoMatchNull() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => TRUE,
      ConvertBoolean::SETTING_NO_MATCH => NULL,
      ConvertBoolean::SETTING_OTHER_TEXT => '',
    ];
    $plugin = new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
    $this->assertEquals(TRUE, $plugin->tamper('A'));
    $this->assertEquals(NULL, $plugin->tamper('a'));
    $this->assertEquals(FALSE, $plugin->tamper('B'));
    $this->assertEquals(NULL, $plugin->tamper('b'));
    $this->assertEquals(NULL, $plugin->tamper('c'));
    $this->assertEquals(NULL, $plugin->tamper('C'));
  }

  /**
   * Test convert to boolean other text case.
   */
  public function testConvertBooleanOtherText() {
    $config = [
      ConvertBoolean::SETTING_TRUTH_VALUE => 'A',
      ConvertBoolean::SETTING_FALSE_VALUE => 'B',
      ConvertBoolean::SETTING_MATCH_CASE => TRUE,
      ConvertBoolean::SETTING_NO_MATCH => 'other text',
      ConvertBoolean::SETTING_OTHER_TEXT => 'other text',
    ];
    $plugin = new ConvertBoolean($config, 'convert_boolean', [], $this->getMockSourceDefinition());
    $this->assertEquals('other text', $plugin->tamper('a'));
  }

}
