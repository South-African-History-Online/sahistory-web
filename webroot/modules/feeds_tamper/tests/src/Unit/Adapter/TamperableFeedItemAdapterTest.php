<?php

namespace Drupal\Tests\feeds_tamper\Unit\Adapter;

use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds_tamper\Adapter\TamperableFeedItemAdapter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds_tamper\Adapter\TamperableFeedItemAdapter
 * @group feeds_tamper
 */
class TamperableFeedItemAdapterTest extends UnitTestCase {

  /**
   * A feed item.
   *
   * @var \Drupal\feeds\Feeds\Item\ItemInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $feedItem;

  /**
   * Wrapper around feed item to use it as a tamperable item.
   *
   * @var \Drupal\feeds_tamper\Adapter\TamperableFeedItemAdapter
   */
  protected $adapter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->feedItem = $this->createMock(ItemInterface::class);
    $this->adapter = new TamperableFeedItemAdapter($this->feedItem);
  }

  /**
   * @covers ::getSource
   */
  public function testGetSource() {
    $this->feedItem->expects($this->once())
      ->method('toArray');

    $this->adapter->getSource();
  }

  /**
   * @covers ::getSourceProperty
   */
  public function testGetSourceProperty() {
    $this->feedItem->expects($this->once())
      ->method('get')
      ->with('foo');

    $this->adapter->getSourceProperty('foo');
  }

  /**
   * @covers ::setSourceProperty
   */
  public function testSetSourceProperty() {
    $this->feedItem->expects($this->once())
      ->method('set')
      ->with('foo', 'bar');

    $this->adapter->setSourceProperty('foo', 'bar');
  }

}
