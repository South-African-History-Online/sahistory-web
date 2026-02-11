<?php

namespace Drupal\Tests\saho_utils\Unit\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_utils\Service\SortingService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the SortingService.
 *
 * @group saho_utils
 * @coversDefaultClass \Drupal\saho_utils\Service\SortingService
 */
class SortingServiceTest extends UnitTestCase {

  /**
   * The sorting service.
   *
   * @var \Drupal\saho_utils\Service\SortingService
   */
  protected $sortingService;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->sortingService = new SortingService($this->entityTypeManager);
  }

  /**
   * @covers ::getSortingOptions
   */
  public function testGetSortingOptionsWithDefaults() {
    $options = $this->sortingService->getSortingOptions();

    $this->assertIsArray($options);
    $this->assertArrayHasKey('none', $options);
    $this->assertArrayHasKey('random', $options);
    $this->assertArrayHasKey('latest', $options);
    $this->assertArrayHasKey('oldest', $options);
    $this->assertArrayHasKey('recently_updated', $options);
    $this->assertArrayHasKey('title_asc', $options);
    $this->assertCount(6, $options);
  }

  /**
   * @covers ::getSortingOptions
   */
  public function testGetSortingOptionsWithoutRandom() {
    $options = $this->sortingService->getSortingOptions(FALSE, TRUE);

    $this->assertArrayNotHasKey('random', $options);
    $this->assertArrayHasKey('latest', $options);
    $this->assertCount(5, $options);
  }

  /**
   * @covers ::getSortingOptions
   */
  public function testGetSortingOptionsWithoutNone() {
    $options = $this->sortingService->getSortingOptions(TRUE, FALSE);

    $this->assertArrayNotHasKey('none', $options);
    $this->assertArrayHasKey('random', $options);
    $this->assertCount(5, $options);
  }

  /**
   * @covers ::applySorting
   */
  public function testApplySortingWithLatest() {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('sort')
      ->with('created', 'DESC')
      ->willReturnSelf();

    $result = $this->sortingService->applySorting($query, 'latest');
    $this->assertSame($query, $result);
  }

  /**
   * @covers ::applySorting
   */
  public function testApplySortingWithOldest() {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('sort')
      ->with('created', 'ASC')
      ->willReturnSelf();

    $result = $this->sortingService->applySorting($query, 'oldest');
    $this->assertSame($query, $result);
  }

  /**
   * @covers ::applySorting
   */
  public function testApplySortingWithRecentlyUpdated() {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('sort')
      ->with('changed', 'DESC')
      ->willReturnSelf();

    $result = $this->sortingService->applySorting($query, 'recently_updated');
    $this->assertSame($query, $result);
  }

  /**
   * @covers ::applySorting
   */
  public function testApplySortingWithTitleAsc() {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('sort')
      ->with('title', 'ASC')
      ->willReturnSelf();

    $result = $this->sortingService->applySorting($query, 'title_asc');
    $this->assertSame($query, $result);
  }

  /**
   * @covers ::applySorting
   */
  public function testApplySortingWithRandom() {
    $query = $this->createMock(QueryInterface::class);
    // Random sorting should not call sort().
    $query->expects($this->never())
      ->method('sort');

    $result = $this->sortingService->applySorting($query, 'random');
    $this->assertSame($query, $result);
  }

  /**
   * @covers ::applySorting
   */
  public function testApplySortingWithNone() {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->never())
      ->method('sort');

    $result = $this->sortingService->applySorting($query, 'none');
    $this->assertSame($query, $result);
  }

  /**
   * @covers ::applyRandomWithImages
   */
  public function testApplyRandomWithImages() {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('condition')
      ->with('field_image', NULL, 'IS NOT NULL')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('range')
      ->with(0, 50)
      ->willReturnSelf();

    $result = $this->sortingService->applyRandomWithImages($query, 'field_image');
    $this->assertSame($query, $result);
  }

  /**
   * @covers ::applyRandomWithImages
   */
  public function testApplyRandomWithImagesCustomLimit() {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('condition')
      ->with('field_thumbnail', NULL, 'IS NOT NULL')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('range')
      ->with(0, 100)
      ->willReturnSelf();

    $result = $this->sortingService->applyRandomWithImages($query, 'field_thumbnail', 100);
    $this->assertSame($query, $result);
  }

  /**
   * @covers ::sortLoadedEntities
   */
  public function testSortLoadedEntitiesByTitleAsc() {
    $entity1 = $this->createMock(EntityInterface::class);
    $entity1->method('label')->willReturn('Zebra');

    $entity2 = $this->createMock(EntityInterface::class);
    $entity2->method('label')->willReturn('Apple');

    $entity3 = $this->createMock(EntityInterface::class);
    $entity3->method('label')->willReturn('Mango');

    $entities = [1 => $entity1, 2 => $entity2, 3 => $entity3];
    $sorted = $this->sortingService->sortLoadedEntities($entities, 'title_asc');

    $sorted_labels = [];
    foreach ($sorted as $entity) {
      $sorted_labels[] = $entity->label();
    }

    $this->assertSame(['Apple', 'Mango', 'Zebra'], $sorted_labels);
  }

  /**
   * @covers ::sortLoadedEntities
   */
  public function testSortLoadedEntitiesByCreatedLatest() {
    $entity1 = $this->createMock(NodeInterface::class);
    $entity1->method('getCreatedTime')->willReturn(1000);

    $entity2 = $this->createMock(NodeInterface::class);
    $entity2->method('getCreatedTime')->willReturn(3000);

    $entity3 = $this->createMock(NodeInterface::class);
    $entity3->method('getCreatedTime')->willReturn(2000);

    $entities = [1 => $entity1, 2 => $entity2, 3 => $entity3];
    $sorted = $this->sortingService->sortLoadedEntities($entities, 'latest');

    $sorted_times = [];
    foreach ($sorted as $entity) {
      $sorted_times[] = $entity->getCreatedTime();
    }

    $this->assertSame([3000, 2000, 1000], $sorted_times);
  }

  /**
   * @covers ::sortLoadedEntities
   */
  public function testSortLoadedEntitiesByCreatedOldest() {
    $entity1 = $this->createMock(NodeInterface::class);
    $entity1->method('getCreatedTime')->willReturn(3000);

    $entity2 = $this->createMock(NodeInterface::class);
    $entity2->method('getCreatedTime')->willReturn(1000);

    $entity3 = $this->createMock(NodeInterface::class);
    $entity3->method('getCreatedTime')->willReturn(2000);

    $entities = [1 => $entity1, 2 => $entity2, 3 => $entity3];
    $sorted = $this->sortingService->sortLoadedEntities($entities, 'oldest');

    $sorted_times = [];
    foreach ($sorted as $entity) {
      $sorted_times[] = $entity->getCreatedTime();
    }

    $this->assertSame([1000, 2000, 3000], $sorted_times);
  }

  /**
   * @covers ::sortLoadedEntities
   */
  public function testSortLoadedEntitiesByRecentlyUpdated() {
    $entity1 = $this->createMock(NodeInterface::class);
    $entity1->method('getChangedTime')->willReturn(1500);

    $entity2 = $this->createMock(NodeInterface::class);
    $entity2->method('getChangedTime')->willReturn(2500);

    $entity3 = $this->createMock(NodeInterface::class);
    $entity3->method('getChangedTime')->willReturn(500);

    $entities = [1 => $entity1, 2 => $entity2, 3 => $entity3];
    $sorted = $this->sortingService->sortLoadedEntities($entities, 'recently_updated');

    $sorted_times = [];
    foreach ($sorted as $entity) {
      $sorted_times[] = $entity->getChangedTime();
    }

    $this->assertSame([2500, 1500, 500], $sorted_times);
  }

  /**
   * @covers ::sortLoadedEntities
   */
  public function testSortLoadedEntitiesRandom() {
    $entities = [];
    for ($i = 1; $i <= 10; $i++) {
      $entity = $this->createMock(EntityInterface::class);
      $entity->method('id')->willReturn($i);
      $entities[$i] = $entity;
    }

    $sorted = $this->sortingService->sortLoadedEntities($entities, 'random');

    // Verify we still have 10 entities.
    $this->assertCount(10, $sorted);

    // Verify keys are sequential (0-9) after shuffling.
    $this->assertSame(range(0, 9), array_keys($sorted));
  }

  /**
   * @covers ::sortLoadedEntities
   */
  public function testSortLoadedEntitiesWithNone() {
    $entity1 = $this->createMock(EntityInterface::class);
    $entity2 = $this->createMock(EntityInterface::class);

    $entities = [1 => $entity1, 2 => $entity2];
    $sorted = $this->sortingService->sortLoadedEntities($entities, 'none');

    // Should return the same array unchanged.
    $this->assertSame($entities, $sorted);
  }

  /**
   * @covers ::sortLoadedEntities
   */
  public function testSortLoadedEntitiesWithEmptyArray() {
    $sorted = $this->sortingService->sortLoadedEntities([], 'latest');

    $this->assertIsArray($sorted);
    $this->assertEmpty($sorted);
  }

}
