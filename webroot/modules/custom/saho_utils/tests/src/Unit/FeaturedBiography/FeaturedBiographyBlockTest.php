<?php

namespace Drupal\Tests\saho_utils\Unit\FeaturedBiography;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\featured_biography\Plugin\Block\FeaturedBiographyBlock;
use Drupal\node\NodeInterface;
use Drupal\saho_utils\Service\CacheHelperService;
use Drupal\saho_utils\Service\ConfigurationFormHelperService;
use Drupal\saho_utils\Service\EntityItemBuilderService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\saho_utils\Service\SortingService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the Featured Biography Block plugin.
 *
 * @group featured_biography
 * @coversDefaultClass \Drupal\featured_biography\Plugin\Block\FeaturedBiographyBlock
 */
class FeaturedBiographyBlockTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The mocked entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The mocked sorting service.
   *
   * @var \Drupal\saho_utils\Service\SortingService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sortingService;

  /**
   * The mocked image extractor service.
   *
   * @var \Drupal\saho_utils\Service\ImageExtractorService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $imageExtractor;

  /**
   * The mocked entity item builder service.
   *
   * @var \Drupal\saho_utils\Service\EntityItemBuilderService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityItemBuilder;

  /**
   * The mocked configuration form helper service.
   *
   * @var \Drupal\saho_utils\Service\ConfigurationFormHelperService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFormHelper;

  /**
   * The mocked cache helper service.
   *
   * @var \Drupal\saho_utils\Service\CacheHelperService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheHelper;

  /**
   * The block plugin instance.
   *
   * @var \Drupal\featured_biography\Plugin\Block\FeaturedBiographyBlock
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock all dependencies.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->sortingService = $this->createMock(SortingService::class);
    $this->imageExtractor = $this->createMock(ImageExtractorService::class);
    $this->entityItemBuilder = $this->createMock(EntityItemBuilderService::class);
    $this->configFormHelper = $this->createMock(ConfigurationFormHelperService::class);
    $this->cacheHelper = $this->createMock(CacheHelperService::class);

    // Set up a container for string translation.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Creates a block instance with given configuration.
   *
   * @param array $configuration
   *   The block configuration.
   *
   * @return \Drupal\featured_biography\Plugin\Block\FeaturedBiographyBlock
   *   The block instance.
   */
  protected function createBlockInstance(array $configuration = []): FeaturedBiographyBlock {
    $default_config = [
      'display_title' => 'Featured Biography',
      'block_description' => '',
      'selection_method' => 'specific',
      'specific_nid' => '',
      'specific_nids' => '',
      'category' => '',
      'display_mode' => 'full',
      'highlight_category' => FALSE,
      'entity_count' => 1,
      'category_label' => '',
      'enable_carousel' => FALSE,
      'sort_by' => 'none',
    ];

    $merged_config = array_merge($default_config, $configuration);

    return new FeaturedBiographyBlock(
      $merged_config,
      'featured_biography_block',
      ['provider' => 'featured_biography'],
      $this->entityTypeManager,
      $this->currentUser,
      $this->entityFieldManager,
      $this->sortingService,
      $this->imageExtractor,
      $this->entityItemBuilder,
      $this->configFormHelper,
      $this->cacheHelper
    );
  }

  /**
   * Tests that sorting is not applied when sort_by is 'none'.
   *
   * @covers ::getBiographyItem
   */
  public function testSortingNotAppliedWhenSortByNone() {
    $block = $this->createBlockInstance([
      'selection_method' => 'specific',
      'specific_nids' => '1,2,3',
      'entity_count' => 3,
      'sort_by' => 'none',
    ]);

    // Mock node storage and query.
    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_type_storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entity_type) use ($node_storage, $node_type_storage) {
        if ($entity_type === 'node') {
          return $node_storage;
        }
        if ($entity_type === 'node_type') {
          return $node_type_storage;
        }
        return NULL;
      });

    $node_type_storage->method('load')->willReturn((object) []);
    $node_storage->method('getQuery')->willReturn($query);

    // Set up query expectations.
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    // Create mock nodes.
    $nodes = [
      1 => $this->createMock(NodeInterface::class),
      2 => $this->createMock(NodeInterface::class),
      3 => $this->createMock(NodeInterface::class),
    ];
    $node_storage->method('loadMultiple')->with([1, 2, 3])->willReturn($nodes);

    // Sorting service should NOT be called when sort_by is 'none'.
    $this->sortingService->expects($this->never())->method('sortLoadedEntities');

    // Mock entity item builder.
    $this->entityItemBuilder->method('buildItemWithImage')->willReturn([
      'id' => 1,
      'title' => 'Test',
      'url' => '/node/1',
    ]);

    // Mock cache helper.
    $this->cacheHelper->method('buildNodeListCache')->willReturn(['contexts' => []]);

    // Build the block.
    $build = $block->build();

    $this->assertIsArray($build);
  }

  /**
   * Tests that sorting IS applied when sort_by is not 'none'.
   *
   * @covers ::getBiographyItem
   */
  public function testSortingAppliedWhenSortBySpecified() {
    $block = $this->createBlockInstance([
      'selection_method' => 'specific',
      'specific_nids' => '1,2,3',
      'entity_count' => 3,
      'sort_by' => 'latest',
    ]);

    // Mock node storage and query.
    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_type_storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entity_type) use ($node_storage, $node_type_storage) {
        if ($entity_type === 'node') {
          return $node_storage;
        }
        if ($entity_type === 'node_type') {
          return $node_type_storage;
        }
        return NULL;
      });

    $node_type_storage->method('load')->willReturn((object) []);
    $node_storage->method('getQuery')->willReturn($query);

    // Set up query expectations.
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    // Create mock nodes.
    $nodes = [
      1 => $this->createMock(NodeInterface::class),
      2 => $this->createMock(NodeInterface::class),
      3 => $this->createMock(NodeInterface::class),
    ];
    $node_storage->method('loadMultiple')->with([1, 2, 3])->willReturn($nodes);

    // Create sorted nodes (reversed order for testing).
    $sorted_nodes = [
      3 => $nodes[3],
      2 => $nodes[2],
      1 => $nodes[1],
    ];

    // Sorting service SHOULD be called when sort_by is 'latest'.
    $this->sortingService->expects($this->once())
      ->method('sortLoadedEntities')
      ->with($nodes, 'latest')
      ->willReturn($sorted_nodes);

    // Mock entity item builder.
    $this->entityItemBuilder->method('buildItemWithImage')->willReturn([
      'id' => 1,
      'title' => 'Test',
      'url' => '/node/1',
    ]);

    // Mock cache helper.
    $this->cacheHelper->method('buildNodeListCache')->willReturn(['contexts' => []]);

    // Build the block.
    $build = $block->build();

    $this->assertIsArray($build);
  }

  /**
   * Tests that sorting returns the sorted array correctly.
   *
   * @covers ::getBiographyItem
   */
  public function testSortingReturnsCorrectOrder() {
    $block = $this->createBlockInstance([
      'selection_method' => 'specific',
      'specific_nids' => '1,2,3',
      'entity_count' => 3,
      'sort_by' => 'title_asc',
    ]);

    // Mock node storage and query.
    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_type_storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entity_type) use ($node_storage, $node_type_storage) {
        if ($entity_type === 'node') {
          return $node_storage;
        }
        if ($entity_type === 'node_type') {
          return $node_type_storage;
        }
        return NULL;
      });

    $node_type_storage->method('load')->willReturn((object) []);
    $node_storage->method('getQuery')->willReturn($query);

    // Set up query expectations.
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    // Create mock nodes with labels.
    $node1 = $this->createMock(NodeInterface::class);
    $node1->method('label')->willReturn('Charlie');
    $node1->method('id')->willReturn(1);

    $node2 = $this->createMock(NodeInterface::class);
    $node2->method('label')->willReturn('Alice');
    $node2->method('id')->willReturn(2);

    $node3 = $this->createMock(NodeInterface::class);
    $node3->method('label')->willReturn('Bob');
    $node3->method('id')->willReturn(3);

    $nodes = [1 => $node1, 2 => $node2, 3 => $node3];
    $node_storage->method('loadMultiple')->with([1, 2, 3])->willReturn($nodes);

    // Sorted alphabetically: Alice, Bob, Charlie.
    $sorted_nodes = [2 => $node2, 3 => $node3, 1 => $node1];

    // Sorting service returns sorted array.
    $this->sortingService->expects($this->once())
      ->method('sortLoadedEntities')
      ->with($nodes, 'title_asc')
      ->willReturn($sorted_nodes);

    // Mock entity item builder to return items with IDs.
    $this->entityItemBuilder->method('buildItemWithImage')
      ->willReturnCallback(function ($node) {
        return [
          'id' => $node->id(),
          'title' => $node->label(),
          'url' => '/node/' . $node->id(),
        ];
      });

    // Mock cache helper.
    $this->cacheHelper->method('buildNodeListCache')->willReturn(['contexts' => []]);

    // Build the block.
    $build = $block->build();

    // Verify the build output contains items in sorted order.
    $this->assertIsArray($build);
    $this->assertArrayHasKey('#biography_data', $build);
    $this->assertArrayHasKey('items', $build['#biography_data']);

    $items = $build['#biography_data']['items'];
    $this->assertCount(3, $items);

    // Check order: Alice (ID 2), Bob (ID 3), Charlie (ID 1).
    $this->assertEquals(2, $items[0]['id']);
    $this->assertEquals('Alice', $items[0]['title']);

    $this->assertEquals(3, $items[1]['id']);
    $this->assertEquals('Bob', $items[1]['title']);

    $this->assertEquals(1, $items[2]['id']);
    $this->assertEquals('Charlie', $items[2]['title']);
  }

  /**
   * Tests that empty sort_by value doesn't trigger sorting.
   *
   * @covers ::getBiographyItem
   */
  public function testEmptySortByDoesNotTriggerSorting() {
    $block = $this->createBlockInstance([
      'selection_method' => 'specific',
      'specific_nids' => '1,2',
      'entity_count' => 2,
      'sort_by' => '',
    ]);

    // Mock node storage and query.
    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_type_storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entity_type) use ($node_storage, $node_type_storage) {
        if ($entity_type === 'node') {
          return $node_storage;
        }
        if ($entity_type === 'node_type') {
          return $node_type_storage;
        }
        return NULL;
      });

    $node_type_storage->method('load')->willReturn((object) []);
    $node_storage->method('getQuery')->willReturn($query);

    // Set up query expectations.
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    // Create mock nodes.
    $nodes = [
      1 => $this->createMock(NodeInterface::class),
      2 => $this->createMock(NodeInterface::class),
    ];
    $node_storage->method('loadMultiple')->with([1, 2])->willReturn($nodes);

    // Sorting service should NOT be called when sort_by is empty.
    $this->sortingService->expects($this->never())->method('sortLoadedEntities');

    // Mock entity item builder.
    $this->entityItemBuilder->method('buildItemWithImage')->willReturn([
      'id' => 1,
      'title' => 'Test',
      'url' => '/node/1',
    ]);

    // Mock cache helper.
    $this->cacheHelper->method('buildNodeListCache')->willReturn(['contexts' => []]);

    // Build the block.
    $build = $block->build();

    $this->assertIsArray($build);
  }

  /**
   * Tests single biography display (entity_count = 1) with sorting.
   *
   * Note: Sorting is applied even for single items (though it's a no-op).
   * This could be optimized to skip sorting when count is 1.
   *
   * @covers ::getBiographyItem
   */
  public function testSingleBiographyWithSorting() {
    $block = $this->createBlockInstance([
      'selection_method' => 'specific',
      'specific_nids' => '1',
      'entity_count' => 1,
      'sort_by' => 'latest',
    ]);

    // Mock node storage and query.
    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_type_storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($entity_type) use ($node_storage, $node_type_storage) {
        if ($entity_type === 'node') {
          return $node_storage;
        }
        if ($entity_type === 'node_type') {
          return $node_type_storage;
        }
        return NULL;
      });

    $node_type_storage->method('load')->willReturn((object) []);
    $node_storage->method('getQuery')->willReturn($query);

    // Set up query expectations.
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    // Create single mock node.
    $node = $this->createMock(NodeInterface::class);
    $nodes = [1 => $node];
    $node_storage->method('loadMultiple')->with([1])->willReturn($nodes);

    // Sorting IS called even for single item (returns same array).
    $this->sortingService->expects($this->once())
      ->method('sortLoadedEntities')
      ->with($nodes, 'latest')
      ->willReturn($nodes);

    // Mock entity item builder.
    $this->entityItemBuilder->method('buildItemWithImage')->willReturn([
      'id' => 1,
      'title' => 'Test Biography',
      'url' => '/node/1',
    ]);

    // Mock cache helper.
    $this->cacheHelper->method('buildNodeListCache')->willReturn(['contexts' => []]);

    // Build the block.
    $build = $block->build();

    $this->assertIsArray($build);
    $this->assertArrayHasKey('#biography_item', $build);
  }

}
