<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\CastToInt;

/**
 * Tests the cast to int plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\CastToInt
 * @group tamper
 */
class CastToIntTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new CastToInt([], 'cast_to_int', [], $this->getMockSourceDefinition());
  }

  /**
   * Test converting string '1' to int.
   */
  public function testStringOneToInt() {
    $this->assertEquals(1, $this->plugin->tamper('1'));
  }

  /**
   * Test converting alphabetic string to int.
   */
  public function testAlphabeticStringToInt() {
    $this->assertEquals(0, $this->plugin->tamper('asdfsdf'));
  }

  /**
   * Test converting decimal string to int.
   */
  public function testDecimalStringToInt() {
    $this->assertEquals(1, $this->plugin->tamper('1.2324'));
  }

  /**
   * Test converting decimal to int.
   */
  public function testDecimalToInt() {
    $this->assertEquals(1, $this->plugin->tamper(1.2324));
  }

  /**
   * Test converting TRUE to int.
   */
  public function testTrueToInt() {
    $this->assertEquals(1, $this->plugin->tamper(TRUE));
  }

  /**
   * Test converting FALSE to int.
   */
  public function testFalseToInt() {
    $this->assertEquals(0, $this->plugin->tamper(FALSE));
  }

  /**
   * Test converting string int to int.
   */
  public function testStringIntToInt() {
    $this->assertEquals(23456, $this->plugin->tamper('23456'));
  }

}
