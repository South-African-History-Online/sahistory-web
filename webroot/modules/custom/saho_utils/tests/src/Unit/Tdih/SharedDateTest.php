<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_utils\Unit\Tdih;

use Drupal\tdih\Plugin\Block\TdihInteractiveBlock;
use Drupal\Tests\UnitTestCase;

/**
 * Validates the shareable ?date=MM-DD parameter parser.
 *
 * @group tdih
 * @coversDefaultClass \Drupal\tdih\Plugin\Block\TdihInteractiveBlock
 */
final class SharedDateTest extends UnitTestCase {

  /**
   * @covers ::parseSharedDate
   * @dataProvider dateProvider
   */
  public function testParseSharedDate(?string $raw, ?string $expected): void {
    $this->assertSame($expected, TdihInteractiveBlock::parseSharedDate($raw));
  }

  /**
   * Data provider for testParseSharedDate().
   */
  public static function dateProvider(): array {
    return [
      'valid july 18' => ['07-18', '07-18'],
      'valid with whitespace' => [' 12-01 ', '12-01'],
      'leap day allowed' => ['02-29', '02-29'],
      'first of january' => ['01-01', '01-01'],
      'last of december' => ['12-31', '12-31'],
      'invalid 31 february' => ['02-31', NULL],
      'invalid month 13' => ['13-01', NULL],
      'invalid day 00' => ['05-00', NULL],
      'single digits rejected' => ['7-18', NULL],
      'with year rejected' => ['1976-06-16', NULL],
      'junk' => ['biko', NULL],
      'empty' => ['', NULL],
      'null' => [NULL, NULL],
    ];
  }

}
