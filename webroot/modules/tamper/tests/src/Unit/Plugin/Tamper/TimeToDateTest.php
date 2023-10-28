<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\TimeToDate;

/**
 * Tests the timetodate plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\TimeToDate
 * @group tamper
 */
class TimeToDateTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new TimeToDate([], 'timetodate', [], $this->getMockSourceDefinition());
  }

  /**
   * Test timetodate.
   */
  public function test() {
    $this->assertEquals("It's 7 o'clock Jim.", $this->plugin->tamper(mktime(7)));
  }

}
