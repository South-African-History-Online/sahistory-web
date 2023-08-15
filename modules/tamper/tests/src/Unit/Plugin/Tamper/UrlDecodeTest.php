<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\UrlDecode;

/**
 * Tests the url decode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\UrlDecode
 * @group tamper
 */
class UrlDecodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new UrlDecode([], 'url_decode', [], $this->getMockSourceDefinition());
  }

  /**
   * Tests url decoding using the 'legacy' method.
   *
   * The legacy method uses the PHP function urldecode().
   *
   * The following cases are tested:
   * - decoding symbols;
   * - decoding a string with spaces;
   * - decoding special characters.
   */
  public function testUrlDecodeLegacyMethod() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      UrlDecode::SETTING_METHOD => 'urldecode',
    ];
    $plugin = new UrlDecode($config, 'url_decode', [], $this->getMockSourceDefinition());
    $this->assertEquals('$ & < > ? ; # : = , " \' ~ + %', $plugin->tamper('%24+%26+%3C+%3E+%3F+%3B+%23+%3A+%3D+%2C+%22+%27+%7E+%2B+%25'));
    $this->assertEquals('String with spaces', $plugin->tamper('String+with+spaces'));
    $this->assertEquals('special chars: &%*', $plugin->tamper('special+chars%3A+%26%25%2A'));
    $plugin->tamper(['fOo', 'BAR']);
    $plugin->tamper(14567);
  }

  /**
   * Tests url decoding using the 'raw' method.
   *
   * The raw method uses the PHP function rawurldecode().
   *
   * The following cases are tested:
   * - decoding symbols;
   * - decoding a string with spaces;
   * - decoding special characters.
   */
  public function testUrlDecodeRawMethod() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      UrlDecode::SETTING_METHOD => 'rawurldecode',
    ];
    $plugin = new UrlDecode($config, 'url_decode', [], $this->getMockSourceDefinition());
    $this->assertEquals('$ & < > ? ; # : = , " \' ~ + %', $plugin->tamper('%24%20%26%20%3C%20%3E%20%3F%20%3B%20%23%20%3A%20%3D%20%2C%20%22%20%27%20%7E%20%2B%20%25'));
    $this->assertEquals('String with spaces', $plugin->tamper('String%20with%20spaces'));
    $this->assertEquals('special chars: &%*', $plugin->tamper('special%20chars%3A%20%26%25%2A'));
    $plugin->tamper(['fOo', 'BAR']);
    $plugin->tamper(14567);
  }

}
