<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\StrPad;

/**
 * Tests the StrPad plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrPad
 * @group tamper
 */
class StrPadTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new StrPad([], 'StrPad', [], $this->getMockSourceDefinition());
  }

  /**
   * Test String pad Left.
   *
   * @covers ::tamper
   */
  public function testStrPadLeft() {
    $config = [
      StrPad::SETTING_PAD_LENGTH => '10',
      StrPad::SETTING_PAD_STRING => ' ',
      StrPad::SETTING_PAD_TYPE => STR_PAD_LEFT,
    ];
    $plugin = new StrPad($config, 'StrPad', [], $this->getMockSourceDefinition());

    $this->assertEquals('        hi', $plugin->tamper('hi'));
  }

  /**
   * Test String pad Right.
   *
   * @covers ::tamper
   */
  public function testStrPadRight() {
    $config = [
      StrPad::SETTING_PAD_LENGTH => '10',
      StrPad::SETTING_PAD_STRING => ' ',
      StrPad::SETTING_PAD_TYPE => STR_PAD_RIGHT,
    ];
    $plugin = new StrPad($config, 'StrPad', [], $this->getMockSourceDefinition());

    $this->assertEquals('hi        ', $plugin->tamper('hi'));
  }

  /**
   * Test String pad Right With Pad String Zero.
   *
   * @covers ::tamper
   */
  public function testStrPadRightWithPadStringZero() {
    $config = [
      StrPad::SETTING_PAD_LENGTH => '5',
      StrPad::SETTING_PAD_STRING => '0',
      StrPad::SETTING_PAD_TYPE => STR_PAD_RIGHT,
    ];
    $plugin = new StrPad($config, 'StrPad', [], $this->getMockSourceDefinition());
    // Can't use 1.0 since 1.0 == 1.000.
    $this->assertEquals('A.000', $plugin->tamper('A.0'));
  }

  /**
   * @covers ::tamper
   */
  public function testTamperExceptionWithInvalidInput() {
    $this->expectException(TamperException::class);
    $this->plugin->tamper(new \stdClass());
  }

}
