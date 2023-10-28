<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\StripTags;

/**
 * Tests the strip tags plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\StripTags
 * @group tamper
 */
class StripTagsTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new StripTags([], 'strip_tags', [], $this->getMockSourceDefinition());
  }

  /**
   * Test the plugin with no tags allowed.
   */
  public function testNoAllowedTags() {
    $config = [
      StripTags::SETTING_ALLOWED_TAGS => NULL,
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('sdfsdfsdfsdfsdfsdfsdfsdf', $this->plugin->tamper('sdfsdfsdfsdf<b>sdfsdf</b>sdfsdf'));
    $this->assertEquals('sdfsdfsdfsdfsdfsdfsdfsdf', $this->plugin->tamper('sdfsdfsdfsdf<b>sdfsdfsdfsdf'));
  }

  /**
   * Test the plugin with tags allowed.
   */
  public function testAllowedTags() {
    $config = [
      StripTags::SETTING_ALLOWED_TAGS => '<i>',
    ];
    $this->plugin->setConfiguration($config);
    $this->assertEquals('sdfsdfsdfsdf<i>sdfsdf</i>sdfsdfsdfsdf', $this->plugin->tamper('sdfsdfsdfsdf<i>sdfsdf</i><b>sdfs</b>dfsdfsdf'));
  }

  /**
   * Test the plugin behaviour without string data.
   */
  public function testNoStringTamper() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $this->plugin->tamper(NULL);
  }

}
