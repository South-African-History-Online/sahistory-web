<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\HtmlEntityDecode;

/**
 * Tests the html entity decode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\HtmlEntityDecode
 * @group tamper
 */
class HtmlEntityDecodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new HtmlEntityDecode([], 'html_entity_decode', [], $this->getMockSourceDefinition());
  }

  /**
   * Test HTML entity decode.
   */
  public function testHtmlEntityDecode() {
    $this->assertEquals('<html>asdfsadfasf<b>asfasf</b></html>', $this->plugin->tamper('&lt;html&gt;asdfsadfasf&lt;b&gt;asfasf&lt;/b&gt;&lt;/html&gt;'));
  }

  /**
   * Test non string input throws an exception.
   */
  public function testNoStringException() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $this->plugin->tamper(43);

    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $this->plugin->tamper(['awesomes4uc3!']);

    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $this->plugin->tamper(NULL);
  }

}
