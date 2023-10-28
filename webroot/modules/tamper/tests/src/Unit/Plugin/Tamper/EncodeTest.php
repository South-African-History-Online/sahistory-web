<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Encode;

/**
 * Tests the encode / decode plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Encode
 *
 * @group tamper
 */
class EncodeTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Encode([], 'encode', [], $this->getMockSourceDefinition());
  }

  /**
   * Test serialize.
   */
  public function testSerializeArray() {
    $config = [
      Encode::SETTING_MODE => 'serialize',
    ];
    $plugin = new Encode($config, 'encode', [], $this->getMockSourceDefinition());
    $this->assertEquals('a:0:{}', $plugin->tamper([]));
  }

  /**
   * Test unserialize.
   */
  public function testUnserializeArray() {
    $config = [
      Encode::SETTING_MODE => 'unserialize',
    ];
    $plugin = new Encode($config, 'encode', [], $this->getMockSourceDefinition());
    $this->assertEquals([], $plugin->tamper('a:0:{}'));
  }


  /**
   * Test serialize on complex string.
   */
  public function testSerializeCrazyString() {
    $config = [
      Encode::SETTING_MODE => 'serialize',
    ];
    $plugin = new Encode($config, 'encode', [], $this->getMockSourceDefinition());
    $this->assertEquals('s:28:"abcdef 123 @#`|\"$%&/()=?\'^*";', $plugin->tamper('abcdef 123 @#`|\\"$%&/()=?\'^*'));
  }

  /**
   * Test unserialize on complex string.
   */
  public function testUnserializeCrazyString() {
    $config = [
      Encode::SETTING_MODE => 'unserialize',
    ];
    $plugin = new Encode($config, 'encode', [], $this->getMockSourceDefinition());
    $this->assertEquals('abcdef 123 @#`|\"$%&/()=?\'^*', $plugin->tamper('s:28:"abcdef 123 @#`|\"$%&/()=?\'^*";'));
  }

  /**
   * Test base64_encode.
   */
  public function testBase64Encode() {
    $config = [
      Encode::SETTING_MODE => 'base64_encode',
    ];
    $plugin = new Encode($config, 'encode', [], $this->getMockSourceDefinition());
    $this->assertEquals('YWJjZGVmIDEyMyBAI2B8XCIkJSYvKCk9PydeKg==', $plugin->tamper('abcdef 123 @#`|\\"$%&/()=?\'^*'));
  }

  /**
   * Test base64_decode.
   */
  public function testBase64Decode() {
    $config = [
      Encode::SETTING_MODE => 'base64_decode',
    ];
    $plugin = new Encode($config, 'encode', [], $this->getMockSourceDefinition());
    $this->assertEquals('abcdef 123 @#`|\\"$%&/()=?\'^*', $plugin->tamper('YWJjZGVmIDEyMyBAI2B8XCIkJSYvKCk9PydeKg=='));
  }

}
