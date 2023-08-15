<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\Sprintf;

/**
 * Tests the sprintf plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Sprintf
 * @group tamper
 */
class SprintfTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Sprintf([], 'sprintf', [], $this->getMockSourceDefinition());
  }

  /**
   * Test using text format %s.
   */
  public function testSprintfString() {
    $this->assertEquals('abc0123def', $this->plugin->tamper('abc0123def'));
  }

  /**
   * Test using text format %08d.
   */
  public function testSprintfLeadingZeroes() {
    $config = [
      Sprintf::SETTING_TEXT_FORMAT => '%08d',
    ];
    $plugin = new Sprintf($config, 'sprintf', [], $this->getMockSourceDefinition());

    $this->assertEquals('00000123', $plugin->tamper('0123'));
  }

  /**
   * Test using text format %c.
   */
  public function testSprintfChar() {
    $config = [
      Sprintf::SETTING_TEXT_FORMAT => '%c',
    ];
    $plugin = new Sprintf($config, 'sprintf', [], $this->getMockSourceDefinition());

    $this->assertEquals('A', $plugin->tamper('65'));
  }

}
