<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\StrToTime;

/**
 * Tests the strtotime plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StrToTime
 * @group tamper
 */
class StrToTimeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new StrToTime([], 'strtotime', [], $this->getMockSourceDefinition());
  }

  /**
   * Test converting string to time.
   *
   * @covers ::tamper
   */
  public function testStrToTimeFormat() {
    $this->assertEquals(515995200, $this->plugin->tamper('1986-05-09 04:00:00 GMT'));
    $this->assertEquals(515995200, $this->plugin->tamper('May 9, 1986 04:00:00 GMT'));
    $this->assertEquals(515995200, $this->plugin->tamper('Fri, 09 May 1986 04:00:00 GMT'));
  }

  /**
   * @covers ::tamper
   */
  public function testTamperExceptionWithInvalidInput() {
    $this->expectException(TamperException::class);
    $this->plugin->tamper(new \stdClass());
  }

}
