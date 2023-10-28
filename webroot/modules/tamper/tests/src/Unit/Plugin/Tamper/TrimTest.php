<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\Trim;

/**
 * Tests the trim plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Trim
 * @group tamper
 */
class TrimTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      Trim::SETTING_CHARACTER => '',
      Trim::SETTING_SIDE => 'trim',
    ];
    return new Trim($config, 'trim', [], $this->getMockSourceDefinition());
  }

  /**
   * Test trimming left side.
   */
  public function testTrimLeftSide() {
    $config = [
      Trim::SETTING_CHARACTER => '',
      Trim::SETTING_SIDE => 'ltrim',
    ];
    $plugin = new Trim($config, 'trim', [], $this->getMockSourceDefinition());
    $this->assertEquals('asdfasf  ', $plugin->tamper('  asdfasf  '));
  }

  /**
   * Test trimming right side.
   */
  public function testTrimRightSide() {
    $config = [
      Trim::SETTING_CHARACTER => '',
      Trim::SETTING_SIDE => 'rtrim',
    ];
    $plugin = new Trim($config, 'trim', [], $this->getMockSourceDefinition());
    $this->assertEquals('  asdfasf', $plugin->tamper('  asdfasf  '));
  }

  /**
   * Test trimming both sides.
   */
  public function testTrimBothSides() {
    $this->assertEquals('asdfasf', $this->plugin->tamper('  asdfasf  '));
  }

  /**
   * Test trimming with character mask.
   */
  public function testTrimWithCharacterMask() {
    $config = [
      Trim::SETTING_CHARACTER => '$',
      Trim::SETTING_SIDE => 'trim',
    ];
    $plugin = new Trim($config, 'trim', [], $this->getMockSourceDefinition());
    $this->assertEquals('asdfasf', $plugin->tamper('$$asdfasf$$'));
  }

}
