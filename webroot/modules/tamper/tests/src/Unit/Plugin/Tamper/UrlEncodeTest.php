<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\UrlEncode;

/**
 * Tests the url encode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\UrlEncode
 * @group tamper
 */
class UrlEncodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new UrlEncode([], 'url_encode', [], $this->getMockSourceDefinition());
  }

  /**
   * Tests url encoding using the 'legacy' method.
   *
   * The legacy method uses the PHP function urlencode().
   *
   * The following cases are tested:
   * - encoding symbols;
   * - encoding a string with spaces;
   * - encoding special characters.
   */
  public function testUrlEncodeString() {
    $config = [
      UrlEncode::SETTING_METHOD => 'urlencode',
    ];
    $plugin = new UrlEncode($config, 'url_encode', [], $this->getMockSourceDefinition());
    $this->assertEquals('%24+%26+%3C+%3E+%3F+%3B+%23+%3A+%3D+%2C+%22+%27+%7E+%2B+%25', $plugin->tamper('$ & < > ? ; # : = , " \' ~ + %'));
    $this->assertEquals('String+with+spaces', $plugin->tamper('String with spaces'));
    $this->assertEquals('special+chars%3A+%26%25%2A', $plugin->tamper('special chars: &%*'));
  }

  /**
   * Tests url encoding of array input using the legacy method.
   */
  public function testUrlEncodeArray() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      UrlEncode::SETTING_METHOD => 'urlencode',
    ];
    $plugin = new UrlEncode($config, 'url_encode', [], $this->getMockSourceDefinition());
    $plugin->tamper(['fOo', 'BAR']);
  }

  /**
   * Tests url encoding of numeric input using the legacy method.
   */
  public function testUrlEncodeNumeric() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      UrlEncode::SETTING_METHOD => 'urlencode',
    ];
    $plugin = new UrlEncode($config, 'url_encode', [], $this->getMockSourceDefinition());
    $plugin->tamper(14567);
  }

  /**
   * Tests url encoding using the 'raw' method.
   *
   * The raw method uses the PHP function rawurlencode().
   *
   * The following cases are tested:
   * - encoding symbols;
   * - encoding a string with spaces;
   * - encoding special characters.
   */
  public function testRawUrlEncodeString() {
    $config = [
      UrlEncode::SETTING_METHOD => 'rawurlencode',
    ];
    $plugin = new UrlEncode($config, 'url_encode', [], $this->getMockSourceDefinition());
    $this->assertEquals('%24%20%26%20%3C%20%3E%20%3F%20%3B%20%23%20%3A%20%3D%20%2C%20%22%20%27%20~%20%2B%20%25', $plugin->tamper('$ & < > ? ; # : = , " \' ~ + %'));
    $this->assertEquals('String%20with%20spaces', $plugin->tamper('String with spaces'));
    $this->assertEquals('special%20chars%3A%20%26%25%2A', $plugin->tamper('special chars: &%*'));
  }

  /**
   * Tests url encoding of array input using the raw method.
   */
  public function testRawUrlEncodeArray() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      UrlEncode::SETTING_METHOD => 'rawurlencode',
    ];
    $plugin = new UrlEncode($config, 'url_encode', [], $this->getMockSourceDefinition());
    $plugin->tamper(['fOo', 'BAR']);
  }

  /**
   * Tests url encoding of number input using the raw method.
   */
  public function testRawUrlEncodeNumeric() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Input should be a string.');
    $config = [
      UrlEncode::SETTING_METHOD => 'rawurlencode',
    ];
    $plugin = new UrlEncode($config, 'url_encode', [], $this->getMockSourceDefinition());
    $plugin->tamper(14567);
  }

}
