<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_utils\Unit\RegisterGrid;

use Drupal\saho_utils\RegisterGrid\ClassroomGradesSource;
use Drupal\saho_utils\RegisterGrid\RegisterGridMigration;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the legacy register block mapping and the grade parser.
 *
 * @group saho_utils
 * @coversDefaultClass \Drupal\saho_utils\RegisterGrid\RegisterGridMigration
 */
final class RegisterGridMigrationTest extends UnitTestCase {

  /**
   * The front-page component as stored on node 144647 maps 1:1.
   *
   * @covers ::map
   * @covers ::isLegacy
   */
  public function testHistoryClassroomComponent(): void {
    $stored = [
      'id' => 'history_classroom_block',
      'label' => 'History Classroom Block',
      'label_display' => 0,
      'provider' => 'history_classroom',
      'display_mode' => 'grid',
      'grades_to_show' => 'all',
      'show_content_count' => 1,
      'show_featured_topic' => 1,
      'block_title' => 'History by Grade',
      'intro_text' => '',
      'context_mapping' => [],
    ];
    $this->assertTrue(RegisterGridMigration::isLegacy('history_classroom_block'));
    $this->assertSame([
      'id' => 'register_grid_block',
      'label' => 'History Classroom Block',
      'label_display' => 0,
      'provider' => 'saho_utils',
      'context_mapping' => [],
      'register' => 'classroom_grades',
      'subset' => 'all',
      'block_title' => 'History by Grade',
      'intro_text' => '',
      'display_mode' => 'grid',
      'show_count' => TRUE,
      'show_featured' => TRUE,
    ], RegisterGridMigration::map($stored));
  }

  /**
   * Per-block toggle names and subsets collapse onto the shared keys.
   *
   * @covers ::map
   */
  public function testOtherLegacyBlocks(): void {
    $provinces = RegisterGridMigration::map([
      'id' => 'sa_provinces_block',
      'show_place_count' => 0,
      'show_featured_place' => 1,
      'display_mode' => 'list',
    ]);
    $this->assertSame('sa_provinces', $provinces['register']);
    $this->assertFalse($provinces['show_count']);
    $this->assertTrue($provinces['show_featured']);
    $this->assertSame('list', $provinces['display_mode']);
    $this->assertSame('all', $provinces['subset']);

    $edu = RegisterGridMigration::map([
      'id' => 'educational_resources_block',
      'resources_to_show' => 'docs',
      'display_mode' => 'carousel',
      'show_featured_item' => 0,
    ]);
    $this->assertSame('educational_resources', $edu['register']);
    $this->assertSame('docs', $edu['subset']);
    $this->assertSame('grid', $edu['display_mode'], 'retired carousel renders as grid');
    $this->assertFalse($edu['show_featured']);

    $africa = RegisterGridMigration::map(['id' => 'africa_regions_block', 'show_featured_country' => 1]);
    $this->assertSame('africa_regions', $africa['register']);
    $this->assertTrue($africa['show_count'], 'missing toggle defaults to on');
  }

  /**
   * Non-legacy components are left alone.
   *
   * @covers ::map
   * @covers ::isLegacy
   */
  public function testUnrelatedComponent(): void {
    $this->assertFalse(RegisterGridMigration::isLegacy('saho_classroom_strip'));
    $this->assertNull(RegisterGridMigration::map(['id' => 'saho_classroom_strip']));
    $this->assertNull(RegisterGridMigration::map([]));
  }

  /**
   * Grade numbers are parsed from the classroom term names.
   *
   * @covers \Drupal\saho_utils\RegisterGrid\ClassroomGradesSource::gradeNumber
   * @covers \Drupal\saho_utils\RegisterGrid\ClassroomGradesSource::phase
   */
  public function testGradeParsing(): void {
    $this->assertSame(8, ClassroomGradesSource::gradeNumber('History Classroom Grade Eight'));
    $this->assertSame(12, ClassroomGradesSource::gradeNumber('Grade Twelve'));
    $this->assertSame(0, ClassroomGradesSource::gradeNumber('Teacher resources'));
    $this->assertSame('Intermediate Phase', ClassroomGradesSource::phase(4));
    $this->assertSame('Senior Phase', ClassroomGradesSource::phase(9));
    $this->assertSame('FET Phase', ClassroomGradesSource::phase(12));
  }

}
