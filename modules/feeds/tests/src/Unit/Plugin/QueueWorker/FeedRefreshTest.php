<?php

namespace Drupal\Tests\feeds\Unit\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\feeds\FeedsExecutableInterface;
use Drupal\feeds\FeedsQueueExecutable;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\State\CleanState;
use Drupal\feeds\Plugin\QueueWorker\FeedRefresh;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Prophecy\Argument;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\Plugin\QueueWorker\FeedRefresh
 * @group feeds
 */
class FeedRefreshTest extends FeedsUnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * The QueueWorker plugin.
   *
   * @var Drupal\feeds\Plugin\QueueWorker\FeedRefresh
   */
  protected $plugin;

  /**
   * The feed.
   *
   * @var Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->dispatcher = new EventDispatcher();
    $queue_factory = $this->createMock(QueueFactory::class, [], [], '', FALSE);
    $queue_factory->expects($this->any())
      ->method('get')
      ->with('feeds_feed_refresh:')
      ->will($this->returnValue($this->createMock(QueueInterface::class)));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $messenger = $this->createMock(MessengerInterface::class);

    $executable = new FeedsQueueExecutable($entity_type_manager, $this->dispatcher, $this->getMockedAccountSwitcher(), $messenger, $queue_factory);
    $executable->setStringTranslation($this->getStringTranslationStub());

    $this->plugin = $this->getMockBuilder(FeedRefresh::class)
      ->setMethods(['feedExists', 'getExecutable'])
      ->setConstructorArgs([
        [],
        'feeds_feed_refresh',
        [],
        $queue_factory,
        $this->dispatcher,
        $this->getMockedAccountSwitcher(),
        $entity_type_manager,
      ])
      ->getMock();
    $this->plugin->expects($this->any())
      ->method('feedExists')
      ->will($this->returnValue(TRUE));
    $this->plugin->expects($this->any())
      ->method('getExecutable')
      ->will($this->returnValue($executable));

    $connection = $this->prophesize(Connection::class);
    $connection->query(Argument::type('string'), Argument::type('array'))->willReturn($this->createMock(StatementInterface::class));

    $this->feed = $this->getMockFeed();
    $this->feed->expects($this->any())
      ->method('getState')
      ->with(StateInterface::CLEAN)
      ->will($this->returnValue(new CleanState(1, $connection->reveal())));
  }

  /**
   * Tests initiating an import.
   */
  public function testBeginStage() {
    $this->plugin->processItem(NULL);
    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::BEGIN,
      [],
    ]);
  }

  /**
   * Tests that an import cannot start when the feed is locked.
   */
  public function testLockException() {
    $this->feed->expects($this->once())
      ->method('lock')
      ->will($this->throwException(new LockException()));
    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::BEGIN,
      [],
    ]);
  }

  /**
   * Tests that a fetch event is dispatched when initiating an import.
   */
  public function testExceptionOnFetchEvent() {
    $this->dispatcher->addListener(FeedsEvents::FETCH, function ($parse_event) {
      throw new RuntimeException();
    });

    $this->expectException(RuntimeException::class);
    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::FETCH,
      [],
    ]);
  }

  /**
   * Tests the parse stage of an import.
   */
  public function testParseStage() {
    $this->dispatcher->addListener(FeedsEvents::PARSE, function ($parse_event) {
      $parser_result = new ParserResult();
      $parser_result->addItem(new DynamicItem());
      $parse_event->setParserResult($parser_result);
    });

    $fetcher_result = new FetcherResult('');

    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::PARSE, [
        'fetcher_result' => $fetcher_result,
      ],
    ]);
  }

  /**
   * Tests dispatching a parse event when running a queue task.
   *
   * When running a queue task at the parse stage, a parse event should get
   * dispatched.
   */
  public function testExceptionOnParseEvent() {
    $this->dispatcher->addListener(FeedsEvents::PARSE, function ($parse_event) {
      throw new RuntimeException();
    });

    $this->expectException(RuntimeException::class);
    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::PARSE, [
        'fetcher_result' => new FetcherResult(''),
      ],
    ]);
  }

  /**
   * Tests the process stage of an import.
   */
  public function testProcessStage() {
    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::PROCESS, [
        'item' => new DynamicItem(),
      ],
    ]);
  }

  /**
   * Tests dispatching a process event when running a queue task.
   *
   * When running a queue task at the process stage, a process event should get
   * dispatched.
   */
  public function testExceptionOnProcessEvent() {
    $this->dispatcher->addListener(FeedsEvents::PROCESS, function ($parse_event) {
      throw new RuntimeException();
    });

    $this->expectException(RuntimeException::class);
    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::PROCESS, [
        'item' => new DynamicItem(),
      ],
    ]);
  }

  /**
   * Tests the final stage of an import.
   */
  public function testFinalPass() {
    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::FINISH, [
        'fetcher_result' => new FetcherResult(''),
      ],
    ]);

    $this->feed->expects($this->exactly(2))
      ->method('progressParsing')
      ->will($this->returnValue(StateInterface::BATCH_COMPLETE));

    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::FINISH, [
        'fetcher_result' => new FetcherResult(''),
      ],
    ]);
    $this->feed->expects($this->once())
      ->method('progressFetching')
      ->will($this->returnValue(StateInterface::BATCH_COMPLETE));
    $this->plugin->processItem([
      $this->feed,
      FeedsExecutableInterface::FINISH, [
        'fetcher_result' => new FetcherResult(''),
      ],
    ]);
  }

}
