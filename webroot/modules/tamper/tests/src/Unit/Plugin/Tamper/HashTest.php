<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\Plugin\Tamper\Hash;
use Drupal\tamper\TamperableItemInterface;

/**
 * Tests the hash plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Hash
 * @group tamper
 */
class HashTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new Hash([], 'hash', [], $this->getMockSourceDefinition());
  }

  /**
   * Get a mock item to use in the test.
   *
   * @return \Drupal\tamper\TamperableItemInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mock of a tamperable item to use in the test.
   */
  protected function getMockItem() {
    $item = $this->createMock(TamperableItemInterface::class);
    $item->expects($this->any())
      ->method('getSource')
      ->willReturn([
        'title' => 'Yay title!',
        'body' => 'Yay body!',
        'foo' => 'bar',
      ]);
    return $item;
  }

  /**
   * Test the hash functionality.
   */
  public function testHash() {
    $hashed_values = md5(serialize([
      'title' => 'Yay title!',
      'body' => 'Yay body!',
      'foo' => 'bar',
    ]));
    $this->assertEquals($hashed_values, $this->plugin->tamper('', $this->getMockItem()));
    $this->assertEquals('foo', $this->plugin->tamper('foo', $this->getMockItem()));
  }

  /**
   * Test the hash functionality.
   */
  public function testHashWithOverride() {
    $plugin = new Hash([Hash::SETTING_OVERRIDE => TRUE], 'hash', [], $this->getMockSourceDefinition());
    $hashed_values = md5(serialize([
      'title' => 'Yay title!',
      'body' => 'Yay body!',
      'foo' => 'bar',
    ]));
    $this->assertEquals($hashed_values, $plugin->tamper('', $this->getMockItem()));
    $this->assertEquals($hashed_values, $plugin->tamper('foo', $this->getMockItem()));
  }

  /**
   * Test the plugin behaviour without a tamperable item.
   */
  public function testEmptyTamperableItem() {
    $this->expectException(TamperException::class);
    $this->expectExceptionMessage('Tamperable item can not be null.');
    $this->plugin->tamper('foo');
  }

}
