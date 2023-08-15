<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\Plugin\Tamper\Required;

/**
 * Test the required plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Required
 * @group tamper
 */
class RequiredTest extends TamperPluginTestBase {

  /**
   * Instantiates a plugin.
   *
   * @return \Drupal\tamper\TamperInterface
   *   A tamper plugin.
   */
  protected function instantiatePlugin() {
    return $this->getRequiredPlugin();
  }

  /**
   * Plugin instance, configured to require empty values.
   *
   * @var \Drupal\tamper\Plugin\Tamper\Required
   */
  protected $invertedPlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->invertedPlugin = $this->getInvertedPlugin();
    parent::setUp();
  }

  /**
   * Test required with a string.
   */
  public function testRequiredWithSingleValue() {
    $this->assertEquals('Expected', $this->plugin->tamper('Expected'));
  }

  /**
   * Test required with a string.
   */
  public function testInvertedRequiredWithSingleValue() {
    $this->expectException(SkipTamperItemException::class);
    $this->invertedPlugin->tamper('Expected');
  }

  /**
   * Test required with empty string.
   */
  public function testRequiredWithEmptyString() {
    $this->expectException(SkipTamperItemException::class);
    $this->plugin->tamper('');
  }

  /**
   * Test required with empty string.
   */
  public function testInvertedRequiredWithEmptyString() {
    $this->assertEquals('', $this->invertedPlugin->tamper(''));
  }

  /**
   * Test required with an array.
   */
  public function testRequiredWithMultipleValues() {
    $expected = [
      'foo' => 'bar',
      'zip' => '',
      'baz' => FALSE,
    ];
    $this->assertEquals($expected, $this->plugin->tamper($expected));
  }

  /**
   * Test required with an array.
   */
  public function testInvertedRequiredWithMultipleValues() {
    $this->expectException(SkipTamperItemException::class);
    $expected = [
      'foo' => 'bar',
      'zip' => '',
      'baz' => FALSE,
    ];
    $this->invertedPlugin->tamper($expected);
  }

  /**
   * Test required with false.
   */
  public function testRequiredWithBool() {
    $this->expectException(SkipTamperItemException::class);
    $this->plugin->tamper(FALSE);
  }

  /**
   * Test required with false.
   */
  public function testInvertedRequiredWithBool() {
    $this->assertEquals(FALSE, $this->invertedPlugin->tamper(FALSE));
  }

  /**
   * Test required with empty array.
   */
  public function testRequiredWithEmptyArray() {
    $this->expectException(SkipTamperItemException::class);
    $this->plugin->tamper([]);
  }

  /**
   * Test required with empty array.
   */
  public function testInvertedRequiredWithEmptyArray() {
    $this->assertArrayEquals([], $this->invertedPlugin->tamper([]));
  }

  /**
   * Test required with empty array.
   */
  public function testRequiredWithNull() {
    $this->expectException(SkipTamperItemException::class);
    $this->plugin->tamper(NULL);
  }

  /**
   * Test required with null.
   */
  public function testInvertedRequiredWithNull() {
    $this->assertEquals(NULL, $this->invertedPlugin->tamper(NULL));
  }

  /**
   * Get a plugin configured to require items.
   *
   * @return \Drupal\tamper\Plugin\Tamper\Required
   *   Plugin instance.
   */
  protected function getRequiredPlugin() {
    $config = [
      Required::SETTING_INVERT => FALSE,
    ];
    $plugin = new Required($config, 'required', [], $this->getMockSourceDefinition());
    return $plugin;
  }

  /**
   * Get a plugin configured to require empty items.
   *
   * @return \Drupal\tamper\Plugin\Tamper\Required
   *   Plugin instance.
   */
  protected function getInvertedPlugin() {
    $config = [
      Required::SETTING_INVERT => TRUE,
    ];
    $plugin = new Required($config, 'required', [], $this->getMockSourceDefinition());
    return $plugin;
  }

}
