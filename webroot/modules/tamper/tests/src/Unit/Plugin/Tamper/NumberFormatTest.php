<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\NumberFormat;

/**
 * Tests the number format plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\NumberFormat
 * @group tamper
 */
class NumberFormatTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new NumberFormat([], 'number_format', [], $this->getMockSourceDefinition());
  }

  /**
   * Test zero decimal and thousands seperator with string.
   */
  public function testNumberFormatDefault() {
    $config = [
      NumberFormat::SETTING_DECIMALS => '0',
      NumberFormat::SETTING_DEC_POINT => '.',
      NumberFormat::SETTING_THOUSANDS_SEP => ',',
    ];
    $plugin = new NumberFormat($config, 'number_format', [], $this->getMockSourceDefinition());

    $this->assertEquals('1,235', $plugin->tamper('1234.56'));

  }

  /**
   * Test french notation with string.
   */
  public function testNumberFormatFrenchNotation() {
    $config = [
      NumberFormat::SETTING_DECIMALS => '2',
      NumberFormat::SETTING_DEC_POINT => ',',
      NumberFormat::SETTING_THOUSANDS_SEP => ' ',
    ];
    $plugin = new NumberFormat($config, 'number_format', [], $this->getMockSourceDefinition());

    $this->assertEquals('1 234,56', $plugin->tamper('1234.56'));

  }

  /**
   * Test zero decimal and thousands seperator with number.
   */
  public function testNumberFormatDefaultWithNumber() {
    $config = [
      NumberFormat::SETTING_DECIMALS => '2',
      NumberFormat::SETTING_DEC_POINT => '.',
      NumberFormat::SETTING_THOUSANDS_SEP => '',
    ];
    $plugin = new NumberFormat($config, 'number_format', [], $this->getMockSourceDefinition());

    $this->assertEquals('1234.57', $plugin->tamper(1234.5678));

  }

  /**
   * Test french notation with number.
   */
  public function testNumberFormatFrenchNotationWithNumber() {
    $config = [
      NumberFormat::SETTING_DECIMALS => '2',
      NumberFormat::SETTING_DEC_POINT => ',',
      NumberFormat::SETTING_THOUSANDS_SEP => ' ',
    ];
    $plugin = new NumberFormat($config, 'number_format', [], $this->getMockSourceDefinition());

    $this->assertEquals('1 234,57', $plugin->tamper(1234.5678));

  }

}
