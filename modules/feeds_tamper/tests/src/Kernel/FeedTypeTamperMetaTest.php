<?php

namespace Drupal\Tests\feeds_tamper\Kernel;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds_tamper\FeedTypeTamperMeta;
use Drupal\tamper\Plugin\Tamper\ConvertCase;

/**
 * @coversDefaultClass \Drupal\feeds_tamper\FeedTypeTamperMeta
 * @group feeds_tamper
 */
class FeedTypeTamperMetaTest extends FeedsTamperKernelTestBase {

  /**
   * The Tamper manager for a feed type.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperMeta
   */
  protected $feedTypeTamperMeta;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $container = \Drupal::getContainer();

    // Mock the UUID generator and let it always return 'uuid3'.
    $uuid_generator = $this->createMock(UuidInterface::class);
    $uuid_generator->expects($this->any())
      ->method('generate')
      ->will($this->returnValue('uuid3'));

    // Get the tamper manager.
    $tamper_manager = $container->get('plugin.manager.tamper');

    // Mock the feed type and let it always return two tampers.
    $feed_type = $this->createMock(FeedTypeInterface::class);
    $feed_type->expects($this->any())
      ->method('getThirdPartySetting')
      ->with('feeds_tamper', 'tampers')
      ->will($this->returnValue([
        'uuid1' => [
          'uuid' => 'uuid1',
          'plugin' => 'explode',
          'separator' => '|',
          'source' => 'alpha',
          'description' => 'Explode with pipe character',
        ],
        'uuid2' => [
          'uuid' => 'uuid2',
          'plugin' => 'convert_case',
          'operation' => 'strtoupper',
          'source' => 'beta',
          'description' => 'Convert all characters to uppercase',
        ],
      ]));

    $feed_type->expects($this->any())
      ->method('getMappingSources')
      ->will($this->returnValue([
        'alpha' => [
          'label' => 'Alpha',
        ],
        'beta' => [
          'label' => 'Beta',
        ],
      ]));

    // Instantiate a feeds type tamper meta object.
    $this->feedTypeTamperMeta = new FeedTypeTamperMeta($uuid_generator, $tamper_manager, $feed_type);
  }

  /**
   * @covers ::getTamper
   */
  public function testGetTamper() {
    $tamper = $this->feedTypeTamperMeta->getTamper('uuid2');
    $this->assertInstanceOf(ConvertCase::class, $tamper);
  }

  /**
   * @covers ::getTampers
   */
  public function testGetTampers() {
    $tampers = iterator_to_array($this->feedTypeTamperMeta->getTampers());
    // Assert that two tampers exist in total.
    $this->assertCount(2, $tampers);
    // Assert that tampers with uuid 'uuid1' and 'uuid2' exist.
    $this->assertArrayHasKey('uuid1', $tampers);
    $this->assertArrayHasKey('uuid2', $tampers);
  }

  /**
   * @covers ::getTampersGroupedBySource
   */
  public function testGetTampersGroupedBySource() {
    // Add a second tamper to 'alpha' source.
    $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'convert_case',
      'operation' => 'ucfirst',
      'source' => 'alpha',
      'description' => 'Start text with uppercase character',
    ]);

    $tampers_by_source = $this->feedTypeTamperMeta->getTampersGroupedBySource();

    // Assert tampers for two sources.
    $this->assertCount(2, $tampers_by_source);
    $this->assertArrayHasKey('alpha', $tampers_by_source);
    $this->assertArrayHasKey('beta', $tampers_by_source);

    // Assert that for the first source two tampers exist.
    $this->assertCount(2, $tampers_by_source['alpha']);
    // And one for the second.
    $this->assertCount(1, $tampers_by_source['beta']);
  }

  /**
   * @covers ::addTamper
   */
  public function testAddTamper() {
    $uuid = $this->feedTypeTamperMeta->addTamper([
      'plugin' => 'convert_case',
      'operation' => 'ucfirst',
      'source' => 'gamma',
      'description' => 'Start text with uppercase character',
    ]);
    $this->assertEquals('uuid3', $uuid);

    $tamper = $this->feedTypeTamperMeta->getTamper($uuid);
    $this->assertInstanceOf(ConvertCase::class, $tamper);

    // Assert that three tampers exist in total.
    $this->assertCount(3, $this->feedTypeTamperMeta->getTampers());
  }

  /**
   * @covers ::setTamperConfig
   */
  public function testSetTamperConfig() {
    $separator = ':';
    $description = 'Explode with colon character (updated)';
    $this->feedTypeTamperMeta->setTamperConfig('uuid1', [
      'separator' => $separator,
      'description' => $description,
    ]);
    $tampers_config = $this->feedTypeTamperMeta->getTampers()->getConfiguration();
    $config = $tampers_config['uuid1'];

    $this->assertEquals($separator, $config['separator']);
    $this->assertEquals($description, $config['description']);
  }

  /**
   * @covers ::removeTamper
   */
  public function testRemoveTamper() {
    $this->feedTypeTamperMeta->removeTamper('uuid1');
    $tampers = iterator_to_array($this->feedTypeTamperMeta->getTampers());

    // Assert that uuid1 is removed, but uuid2 still exists.
    $this->assertArrayNotHasKey('uuid1', $tampers);
    $this->assertArrayHasKey('uuid2', $tampers);

    // Assert that one tamper exists in total.
    $this->assertCount(1, $tampers);
  }

}
