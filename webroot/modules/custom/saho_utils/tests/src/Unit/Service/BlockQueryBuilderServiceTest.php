<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_utils\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\saho_utils\Service\BlockQueryBuilderService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the image filter of BlockQueryBuilderService.
 *
 * @group saho_utils
 * @coversDefaultClass \Drupal\saho_utils\Service\BlockQueryBuilderService
 */
final class BlockQueryBuilderServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  private BlockQueryBuilderService $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->builder = new BlockQueryBuilderService($this->createMock(EntityTypeManagerInterface::class));
  }

  /**
   * A single field is a plain exists() condition.
   *
   * @covers ::addImageFilter
   */
  public function testSingleFieldUsesExists(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())->method('exists')->with('field_bio_pic')->willReturnSelf();
    $query->expects($this->never())->method('orConditionGroup');
    $query->expects($this->never())->method('condition');
    $this->assertSame($query, $this->builder->addImageFilter($query, 'field_bio_pic'));
  }

  /**
   * Several candidates become an OR group of exists() conditions.
   *
   * @covers ::addImageFilter
   */
  public function testCandidateListUsesOrGroup(): void {
    $fields = ['field_article_image', 'field_image', 'field_main_image'];
    $seen = [];
    $group = $this->createMock(ConditionInterface::class);
    $group->method('exists')->willReturnCallback(function (string $field) use (&$seen, $group) {
      $seen[] = $field;
      return $group;
    });
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())->method('orConditionGroup')->willReturn($group);
    $query->expects($this->once())->method('condition')->with($group)->willReturnSelf();
    $query->expects($this->never())->method('exists');

    $this->builder->addImageFilter($query, $fields);
    $this->assertSame($fields, $seen);
  }

  /**
   * Empty input leaves the query untouched.
   *
   * @covers ::addImageFilter
   */
  public function testEmptyInputIsNoop(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->never())->method('exists');
    $query->expects($this->never())->method('condition');
    $this->builder->addImageFilter($query, []);
    $this->builder->addImageFilter($query, '');
  }

}
