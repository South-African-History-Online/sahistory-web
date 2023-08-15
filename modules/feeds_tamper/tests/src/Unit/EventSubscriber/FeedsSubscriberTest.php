<?php

namespace Drupal\Tests\feeds_tamper\Unit\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds_tamper\Adapter\TamperableFeedItemAdapter;
use Drupal\feeds_tamper\EventSubscriber\FeedsSubscriber;
use Drupal\feeds_tamper\FeedTypeTamperManagerInterface;
use Drupal\feeds_tamper\FeedTypeTamperMetaInterface;
use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\TamperInterface;
use Drupal\Tests\feeds_tamper\Unit\FeedsTamperTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\feeds_tamper\EventSubscriber\FeedsSubscriber
 * @group feeds_tamper
 */
class FeedsSubscriberTest extends FeedsTamperTestCase {

  /**
   * The subscriber under test.
   *
   * @var \Drupal\feeds_tamper\EventSubscriber\FeedsSubscriber
   */
  protected $subscriber;

  /**
   * The parse event.
   *
   * @var \Drupal\feeds\Event\ParseEvent
   */
  protected $event;

  /**
   * The tamper meta.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperMetaInterface
   */
  protected $tamperMeta;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create parse event.
    $this->event = new ParseEvent($this->getMockFeed(), $this->createMock(FetcherResultInterface::class));
    $this->event->setParserResult(new ParserResult());

    // Create tamper meta.
    $this->tamperMeta = $this->createMock(FeedTypeTamperMetaInterface::class);

    // Create feed type tamper manager.
    $tamper_manager = $this->createMock(FeedTypeTamperManagerInterface::class);
    $tamper_manager->expects($this->any())
      ->method('getTamperMeta')
      ->will($this->returnValue($this->tamperMeta));

    // And finally, create the subscriber to test.
    $this->subscriber = new FeedsSubscriber($tamper_manager);
  }

  /**
   * Creates a tamper mock with a return value for the tamper() method.
   *
   * @param mixed $return_value
   *   (optional) The value that the tamper plugin must return when tamper()
   *   gets called on it.
   *
   * @return \Drupal\tamper\TamperInterface
   *   A mocked tamper plugin.
   */
  protected function createTamperMock($return_value = NULL) {
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->any())
      ->method('tamper')
      ->will($this->returnValue($return_value));

    return $tamper;
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParse() {
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->any())
      ->method('tamper')
      ->will($this->returnValue('Foo'));

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->will($this->returnValue([
        'alpha' => [
          $this->createTamperMock('Foo'),
        ],
      ]));

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 'Bar');
    $this->event->getParserResult()->addItem($item);

    $this->subscriber->afterParse($this->event);
    $this->assertEquals('Foo', $item->get('alpha'));
  }

  /**
   * @covers ::afterParse
   */
  public function testAfterParseWithNoItems() {
    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->will($this->returnValue([
        'alpha' => [
          $this->createTamperMock('Foo'),
        ],
      ]));

    $this->subscriber->afterParse($this->event);
  }

  /**
   * @covers ::afterParse
   */
  public function testAfterParseWithEmptyArray() {
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->any())
      ->method('tamper')
      ->will($this->returnValue('Foo'));

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->will($this->returnValue([
        'alpha' => [
          $this->createTamperMock('Foo'),
        ],
      ]));

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', []);
    $this->event->getParserResult()->addItem($item);

    $this->subscriber->afterParse($this->event);
    $this->assertEquals('Foo', $item->get('alpha'));
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithNoTampers() {
    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->will($this->returnValue([]));

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 'Bar');
    $this->event->getParserResult()->addItem($item);

    // Run event callback.
    $this->subscriber->afterParse($this->event);
    $this->assertEquals('Bar', $item->get('alpha'));
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithMultiValueTampers() {
    // Create a tamper that turns an input value into an array.
    $tamper1 = $this->prophesize(TamperInterface::class);
    $tamper1->tamper('Bar', Argument::type(TamperableFeedItemAdapter::class))
      ->willReturn(['Bar', 'Bar']);
    $tamper1->getPluginDefinition()->willReturn([
      'handle_multiples' => FALSE,
    ]);
    $tamper1->multiple()->willReturn(TRUE);
    $tamper1 = $tamper1->reveal();

    // Create a tamper that returns 'Foo'.
    $tamper2 = $this->prophesize(TamperInterface::class);
    $tamper2->tamper('Bar', Argument::type(TamperableFeedItemAdapter::class))
      ->willReturn('Foo');
    $tamper2->getPluginDefinition()->willReturn([
      'handle_multiples' => FALSE,
    ]);
    $tamper2->multiple()->willReturn(FALSE);
    $tamper2 = $tamper2->reveal();

    // Create a tamper that returns 'FooFoo'.
    $tamper3 = $this->prophesize(TamperInterface::class);
    $tamper3->tamper('Foo', Argument::type(TamperableFeedItemAdapter::class))
      ->willReturn('FooFoo');
    $tamper3->getPluginDefinition()->willReturn([
      'handle_multiples' => FALSE,
    ]);
    $tamper3->multiple()->willReturn(FALSE);
    $tamper3 = $tamper3->reveal();

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->will($this->returnValue([
        'alpha' => [$tamper1, $tamper2, $tamper3],
      ]));

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 'Bar');
    $this->event->getParserResult()->addItem($item);

    // Run event callback.
    $this->subscriber->afterParse($this->event);
    $this->assertEquals(['FooFoo', 'FooFoo'], $item->get('alpha'));
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithTamperItem() {
    // Create a tamper plugin that manipulates the whole item.
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->once())
      ->method('tamper')
      ->will($this->returnCallback([$this, 'callbackWithTamperItem']));

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->will($this->returnValue([
        'alpha' => [$tamper],
      ]));

    // Add an item to the parser result.
    $item = new DynamicItem();
    $item->set('alpha', 'Foo');
    $item->set('beta', 'Bar');
    $item->set('gamma', 'Qux');
    $this->event->getParserResult()->addItem($item);

    // Run event callback.
    $this->subscriber->afterParse($this->event);
    $this->assertEquals('Fooing', $item->get('alpha'));
    $this->assertEquals('Baring', $item->get('beta'));
    $this->assertEquals('Quxing', $item->get('gamma'));
  }

  /**
   * Callback for testAfterParseWithTamperItem().
   */
  public function callbackWithTamperItem($data, TamperableFeedItemAdapter $item) {
    // Add "ing" to each property.
    foreach ($item->getSource() as $key => $value) {
      $item->setSourceProperty($key, $value . 'ing');
    }

    // Make sure that "ing" is also added to the field that is being tampered.
    return $data . 'ing';
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithSkippingItem() {
    // Create a tamper plugin that will throw a SkipTamperItemException for some
    // values.
    $tamper = $this->createMock(TamperInterface::class);
    $tamper->expects($this->exactly(2))
      ->method('tamper')
      ->will($this->returnCallback([$this, 'callbackSkipItem']));

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->will($this->returnValue([
        'alpha' => [$tamper],
      ]));

    // Create three items. The first item should get removed.
    $item1 = new DynamicItem();
    $item1->set('alpha', 'Foo');
    $this->event->getParserResult()->addItem($item1);
    $item2 = new DynamicItem();
    $item2->set('alpha', 'Bar');
    $this->event->getParserResult()->addItem($item2);

    $this->subscriber->afterParse($this->event);

    // Assert that only item 2 still exists.
    $this->assertEquals(1, $this->event->getParserResult()->count());
    $this->assertSame($item2, $this->event->getParserResult()->offsetGet(0));
  }

  /**
   * Callback for testAfterParseWithSkippingItem().
   */
  public function callbackSkipItem($data, TamperableFeedItemAdapter $item) {
    if ($data == 'Foo') {
      throw new SkipTamperItemException();
    }
  }

  /**
   * @covers ::afterParse
   * @covers ::alterItem
   */
  public function testAfterParseWithSkippingData() {
    // Create a tamper plugin that will throw a SkipTamperDataException for some
    // values.
    $tamper1 = $this->createMock(TamperInterface::class);
    $tamper1->expects($this->exactly(2))
      ->method('tamper')
      ->will($this->returnCallback([$this, 'callbackSkipData']));

    // Create a second tamper plugin that will just set the value to 'Qux'.
    $tamper2 = $this->createMock(TamperInterface::class);
    $tamper2->expects($this->once())
      ->method('tamper')
      ->will($this->returnValue('Qux'));

    // Create a third tamper plugin that operates on the 'beta' field, to ensure
    // skipping on the 'alpha' field does not skip the 'beta' field.
    $tamper3 = $this->createMock(TamperInterface::class);
    $tamper3->expects($this->exactly(2))
      ->method('tamper')
      ->will($this->returnValue('Baz'));

    $this->tamperMeta->expects($this->once())
      ->method('getTampersGroupedBySource')
      ->will($this->returnValue([
        'alpha' => [$tamper1, $tamper2],
        'beta' => [$tamper3],
      ]));

    // Create two items. The first item should get the value unset.
    $item1 = new DynamicItem();
    $item1->set('alpha', 'Foo');
    $item1->set('beta', 'Foo');
    $this->event->getParserResult()->addItem($item1);
    $item2 = new DynamicItem();
    $item2->set('alpha', 'Bar');
    $item2->set('beta', 'Bar');
    $this->event->getParserResult()->addItem($item2);

    $this->subscriber->afterParse($this->event);

    // Assert that 2 items still exist.
    $this->assertEquals(2, $this->event->getParserResult()->count());
    // And assert that item 1 no longer has an alpha value.
    $this->assertNull($item1->get('alpha'));
    // Assert other values.
    $this->assertEquals($item1->get('beta'), 'Baz');
    $this->assertEquals($item2->get('alpha'), 'Qux');
    $this->assertEquals($item2->get('beta'), 'Baz');
  }

  /**
   * Callback for testAfterParseWithSkippingData().
   */
  public function callbackSkipData($data, TamperableFeedItemAdapter $item) {
    if ($data == 'Foo') {
      throw new SkipTamperDataException();
    }
  }

}
