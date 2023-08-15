<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\TruncateText;

/**
 * Tests the Truncate Text plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\TruncateText
 * @group tamper
 */
class TruncateTextTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new TruncateText([], 'truncate_text', [], $this->getMockSourceDefinition());
  }

  /**
   * Test Truncate Text Only By Characters.
   */
  public function testTruncateTextOnlyByChar() {
    $config = [
      TruncateText::SETTING_NUM_CHAR => 5,
      TruncateText::SETTING_ELLIPSE => FALSE,
      TruncateText::SETTING_WORDSAFE => FALSE,
    ];
    $plugin = new TruncateText($config, 'truncate_text', [], $this->getMockSourceDefinition());
    $this->assertEquals('Hello', $plugin->tamper('Hello, how are you today?'));
  }

  /**
   * Test Truncate Text By Characters and Ellipses.
   */
  public function testTruncateTextByCharAndEllipses() {
    $config = [
      TruncateText::SETTING_NUM_CHAR => 5,
      TruncateText::SETTING_ELLIPSE => TRUE,
      TruncateText::SETTING_WORDSAFE => TRUE,
    ];
    $plugin = new TruncateText($config, 'truncate_text', [], $this->getMockSourceDefinition());
    $this->assertEquals('Hell…', $plugin->tamper('Hello, how are you today?'));
    $this->assertEquals('Hello', $plugin->tamper('Hello'));
  }

  /**
   * Test Truncate Text By Characters And WordSafe.
   */
  public function testTruncateTextByCharAndWordSafe() {
    $config = [
      TruncateText::SETTING_NUM_CHAR => 12,
      TruncateText::SETTING_ELLIPSE => FALSE,
      TruncateText::SETTING_WORDSAFE => TRUE,
    ];
    $plugin = new TruncateText($config, 'truncate_text', [], $this->getMockSourceDefinition());
    $this->assertEquals('Hello, how', $plugin->tamper('Hello, how are you today?'));
  }

}
