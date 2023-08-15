<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Math;

/**
 * Tests the math plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Math
 * @group tamper
 */
class MathTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Math([], 'math', [], $this->getMockSourceDefinition());
  }

  /**
   * Test addition.
   */
  public function testAddition() {
    $config = [
      Math::SETTING_OPERATION => 'addition',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(4, $plugin->tamper(2));
  }

  /**
   * Test addition with weird values cast to int.
   */
  public function testAdditionWithCasting() {
    $config = [
      Math::SETTING_OPERATION => 'addition',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(3, $plugin->tamper(TRUE));
    $this->assertEquals(2, $plugin->tamper(FALSE));
    $this->assertEquals(2, $plugin->tamper(NULL));
  }

  /**
   * Test subtraction.
   */
  public function testSubtraction() {
    $config = [
      Math::SETTING_OPERATION => 'subtraction',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(0, $plugin->tamper(2));
  }

  /**
   * Test multiplication.
   */
  public function testMultiplication() {
    $config = [
      Math::SETTING_OPERATION => 'multiplication',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(4, $plugin->tamper(2));
  }

  /**
   * Test division.
   */
  public function testDivision() {
    $config = [
      Math::SETTING_OPERATION => 'division',
      Math::SETTING_VALUE => 2,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(1, $plugin->tamper(2));
  }

  /**
   * Test flip out with division.
   */
  public function testFlipDivision() {
    $config = [
      Math::SETTING_OPERATION => 'division',
      Math::SETTING_FLIP => TRUE,
      Math::SETTING_VALUE => 3,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(3 / 2, $plugin->tamper(2));
  }

  /**
   * Test flip out with subtraction.
   */
  public function testFlipSubtraction() {
    $config = [
      Math::SETTING_OPERATION => 'subtraction',
      Math::SETTING_FLIP => TRUE,
      Math::SETTING_VALUE => 3,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $this->assertEquals(1, $plugin->tamper(2));
  }

  /**
   * Test invalid data throws exception.
   */
  public function testInvalidDataUntouched() {
    $this->expectException(TamperException::class, 'Math plugin failed because data was not numeric.');
    $config = [
      Math::SETTING_OPERATION => 'subtraction',
      Math::SETTING_FLIP => TRUE,
      Math::SETTING_VALUE => 3,
    ];

    $plugin = new Math($config, 'math', [], $this->getMockSourceDefinition());
    $plugin->tamper('boo');
  }

}
