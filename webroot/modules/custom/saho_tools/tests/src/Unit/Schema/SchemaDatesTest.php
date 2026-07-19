<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_tools\Unit\Schema;

use Drupal\Tests\UnitTestCase;
use Drupal\saho_tools\Schema\SchemaDates;

/**
 * Normalisation of SAHO's legacy date shapes into schema-safe ISO dates.
 *
 * @group saho_tools
 * @coversDefaultClass \Drupal\saho_tools\Schema\SchemaDates
 */
final class SchemaDatesTest extends UnitTestCase {

  /**
   * @covers ::normalize
   * @dataProvider provideDates
   */
  public function testNormalize(string $input, ?string $expected): void {
    $this->assertSame($expected, SchemaDates::normalize($input));
  }

  /**
   * Date shapes seen in field_dob/field_dod/field_publication_date_archive.
   */
  public static function provideDates(): array {
    return [
      'full iso' => ['1925-09-19', '1925-09-19'],
      'iso with T time' => ['2001-05-04T00:00:00', '2001-05-04'],
      'iso with space time' => ['2001-05-04 10:30:00', '2001-05-04'],
      'zeroed day partial' => ['1993-12-00', '1993-12'],
      'zeroed month and day' => ['1993-00-00', '1993'],
      'year month partial' => ['1993-12', '1993-12'],
      'legacy day-monthname-year' => ['19-September-1925', '1925-09-19'],
      'legacy with spaces' => ['9 September 1925', '1925-09-09'],
      'bare year' => ['1918', '1918'],
      'junk zero date' => ['0000-00-00', NULL],
      'junk zero year' => ['0000', NULL],
      'prose circa' => ['circa 1918', NULL],
      'prose decade' => ['early 1960s', NULL],
      'invalid calendar date' => ['2001-02-30', NULL],
      'invalid month' => ['2001-13-01', NULL],
      'unknown month name' => ['19-Septembre-1925', NULL],
      'empty' => ['', NULL],
      'whitespace' => ['   ', NULL],
      'far future year' => ['2150', NULL],
    ];
  }

}
