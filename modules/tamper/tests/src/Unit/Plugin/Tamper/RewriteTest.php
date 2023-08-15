<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\Rewrite;
use Drupal\tamper\TamperableItemInterface;

/**
 * Tests the rewrite plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\Rewrite
 * @group tamper
 */
class RewriteTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    $config = [
      Rewrite::SETTING_TEXT => '[title] - [body]',
    ];
    return new Rewrite($config, 'rewrite', [], $this->getMockSourceDefinition());
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
   * Tests the rewrite functionality.
   */
  public function testRewrite() {
    $this->assertEquals('Yay title! - Yay body!', $this->plugin->tamper('foo', $this->getMockItem()));
  }

  /**
   * Tests if no rewrite takes place when there's no tamperable item.
   */
  public function testWithoutTamperableItem() {
    $this->assertEquals('foo', $this->instantiatePlugin()->tamper('foo'));
  }

}
