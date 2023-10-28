<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\HtmlEntityEncode;

/**
 * Tests the html entity encode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\HtmlEntityEncode
 * @group tamper
 */
class HtmlEntityEncodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new HtmlEntityEncode([], 'html_entity_encode', [], $this->getMockSourceDefinition());
  }

  /**
   * Test HTML entity encode.
   */
  public function testHtmlEntityEncode() {
    $this->assertEquals('&lt;html&gt;asdfsadfasf&lt;b&gt;asfasf&lt;/b&gt;&lt;/html&gt;', $this->plugin->tamper('<html>asdfsadfasf<b>asfasf</b></html>'));
  }

  /**
   * Test explode.
   */
  public function testHtmlEntityEncodeWithMultipleValues() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $original = ['foo,bar', 'baz,zip'];
    $this->plugin->tamper($original);
  }

}
