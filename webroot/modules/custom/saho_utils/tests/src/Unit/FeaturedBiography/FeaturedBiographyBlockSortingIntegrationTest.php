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

/**
 * Integration tests for Featured Biography Block sorting behavior.
 *
 * These tests verify that the block correctly uses the sorted results
 * from the sorting service and doesn't discard them.
 *
 * @group featured_biography
 * @coversDefaultClass \Drupal\featured_biography\Plugin\Block\FeaturedBiographyBlock
 */
class FeaturedBiographyBlockSortingIntegrationTest extends UnitTestCase {

  /**
   * Tests that the block uses sorted results in the correct order.
   *
   * This is the critical test that verifies the fix for the sorting bug:
   * The block MUST capture and use the return value from sortLoadedEntities().
   *
   * @covers ::getBiographyItem
   */
  public function testBlockUsesSortedResultsInCorrectOrder() {
    // Set up services.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $current_user = $this->createMock(AccountProxyInterface::class);
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);

    // Use REAL SortingService to test actual sorting behavior.
    $sorting_service = new SortingService($entity_type_manager);

    $image_extractor = $this->createMock(ImageExtractorService::class);
    $entity_item_builder = $this->createMock(EntityItemBuilderService::class);
    $config_form_helper = $this->createMock(ConfigurationFormHelperService::class);
    $cache_helper = $this->createMock(CacheHelperService::class);

    // Set up container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Create block with title_asc sorting.
    $config = [
      'selection_method' => 'specific',
      'specific_nids' => '3,1,2',
      'entity_count' => 3,
      'sort_by' => 'title_asc',
      'display_title' => 'Test',
      'display_mode' => 'full',
      'highlight_category' => FALSE,
      'enable_carousel' => FALSE,
    ];

    $block = new FeaturedBiographyBlock(
      $config,
      'featured_biography_block',
      ['provider' => 'featured_biography'],
      $entity_type_manager,
      $current_user,
      $entity_field_manager,
      $sorting_service,
      $image_extractor,
      $entity_item_builder,
      $config_form_helper,
      $cache_helper
    );

    // Mock storages.
    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_type_storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
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

    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([3, 1, 2]);

    // Create mock nodes with specific titles for sorting test.
    // Load order: Charlie (3), Alice (1), Bob (2).
    $node1 = $this->createMock(NodeInterface::class);
    $node1->method('label')->willReturn('Alice Biography');
    $node1->method('id')->willReturn(1);
    $node1->method('getCreatedTime')->willReturn(1000);

    $node2 = $this->createMock(NodeInterface::class);
    $node2->method('label')->willReturn('Bob Biography');
    $node2->method('id')->willReturn(2);
    $node2->method('getCreatedTime')->willReturn(2000);

    $node3 = $this->createMock(NodeInterface::class);
    $node3->method('label')->willReturn('Charlie Biography');
    $node3->method('id')->willReturn(3);
    $node3->method('getCreatedTime')->willReturn(3000);

    // Nodes loaded in this order (by NID).
    $loaded_nodes = [3 => $node3, 1 => $node1, 2 => $node2];
    $node_storage->method('loadMultiple')->with([3, 1, 2])->willReturn($loaded_nodes);

    // Mock entity item builder to return items with IDs and titles.
    $entity_item_builder->method('buildItemWithImage')
      ->willReturnCallback(function ($node) {
        return [
          'id' => $node->id(),
          'nid' => $node->id(),
          'title' => $node->label(),
          'url' => '/node/' . $node->id(),
        ];
      });

    $cache_helper->method('buildNodeListCache')->willReturn(['contexts' => []]);

    // Build the block.
    $build = $block->build();

    // Verify the output.
    $this->assertIsArray($build);
    $this->assertArrayHasKey('#biography_data', $build);
    $this->assertArrayHasKey('items', $build['#biography_data']);

    $items = $build['#biography_data']['items'];

    // CRITICAL ASSERTION: Items must be in alphabetically sorted order.
    // If the block doesn't capture the sorted array, they'll be in
    // load order (Charlie, Alice, Bob).
    // If the fix works, they'll be in sorted order (Alice, Bob, Charlie).
    $this->assertCount(3, $items);
    $this->assertEquals('Alice Biography', $items[0]['title'], 'First item should be Alice (alphabetically first)');
    $this->assertEquals('Bob Biography', $items[1]['title'], 'Second item should be Bob (alphabetically second)');
    $this->assertEquals('Charlie Biography', $items[2]['title'], 'Third item should be Charlie (alphabetically third)');
  }

  /**
   * Tests that block respects 'latest' sorting with created dates.
   *
   * Verifies that 'latest' uses created date (not changed date) and
   * sorts newest first.
   *
   * @covers ::getBiographyItem
   */
  public function testBlockRespectsLatestSortingWithCreatedDates() {
    // Set up services.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $current_user = $this->createMock(AccountProxyInterface::class);
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);

    // Use REAL SortingService.
    $sorting_service = new SortingService($entity_type_manager);

    $image_extractor = $this->createMock(ImageExtractorService::class);
    $entity_item_builder = $this->createMock(EntityItemBuilderService::class);
    $config_form_helper = $this->createMock(ConfigurationFormHelperService::class);
    $cache_helper = $this->createMock(CacheHelperService::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Create block with 'latest' sorting.
    $config = [
      'selection_method' => 'specific',
      'specific_nids' => '1,2,3',
      'entity_count' => 3,
      'sort_by' => 'latest',
      'display_title' => 'Test',
      'display_mode' => 'full',
      'highlight_category' => FALSE,
      'enable_carousel' => FALSE,
    ];

    $block = new FeaturedBiographyBlock(
      $config,
      'featured_biography_block',
      ['provider' => 'featured_biography'],
      $entity_type_manager,
      $current_user,
      $entity_field_manager,
      $sorting_service,
      $image_extractor,
      $entity_item_builder,
      $config_form_helper,
      $cache_helper
    );

    // Mock storages.
    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_type_storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
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

    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    // Create nodes with different created times.
    // Oldest to newest: node1 (1000), node2 (2000), node3 (3000).
    $node1 = $this->createMock(NodeInterface::class);
    $node1->method('label')->willReturn('Old Biography');
    $node1->method('id')->willReturn(1);
    $node1->method('getCreatedTime')->willReturn(1000);

    $node2 = $this->createMock(NodeInterface::class);
    $node2->method('label')->willReturn('Middle Biography');
    $node2->method('id')->willReturn(2);
    $node2->method('getCreatedTime')->willReturn(2000);

    $node3 = $this->createMock(NodeInterface::class);
    $node3->method('label')->willReturn('New Biography');
    $node3->method('id')->willReturn(3);
    $node3->method('getCreatedTime')->willReturn(3000);

    $loaded_nodes = [1 => $node1, 2 => $node2, 3 => $node3];
    $node_storage->method('loadMultiple')->with([1, 2, 3])->willReturn($loaded_nodes);

    $entity_item_builder->method('buildItemWithImage')
      ->willReturnCallback(function ($node) {
        return [
          'id' => $node->id(),
          'nid' => $node->id(),
          'title' => $node->label(),
          'url' => '/node/' . $node->id(),
        ];
      });

    $cache_helper->method('buildNodeListCache')->willReturn(['contexts' => []]);

    // Build the block.
    $build = $block->build();

    $items = $build['#biography_data']['items'];

    // Verify 'latest' sorting: newest first (3, 2, 1).
    $this->assertCount(3, $items);
    $this->assertEquals('New Biography', $items[0]['title'], 'First item should be newest (created 3000)');
    $this->assertEquals('Middle Biography', $items[1]['title'], 'Second item should be middle (created 2000)');
    $this->assertEquals('Old Biography', $items[2]['title'], 'Third item should be oldest (created 1000)');
  }

}
