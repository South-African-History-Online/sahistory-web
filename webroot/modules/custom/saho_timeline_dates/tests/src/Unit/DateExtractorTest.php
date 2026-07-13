<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_timeline_dates\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\saho_timeline_dates\Service\DateExtractor;

/**
 * Pattern-table tests for the date extractor.
 *
 * @group saho_timeline_dates
 */
final class DateExtractorTest extends UnitTestCase {

  protected DateExtractor $extractor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->extractor = new DateExtractor();
  }

  /**
   * @dataProvider providerExtract
   */
  public function testExtract(string $title, string $body, ?array $expected): void {
    $result = $this->extractor->extract($title, $body);
    if ($expected === NULL) {
      $this->assertNull($result);
      return;
    }
    $this->assertNotNull($result);
    $this->assertSame($expected['date'], $result['date']);
    $this->assertSame($expected['precision'], $result['precision']);
    if (isset($expected['date_end'])) {
      $this->assertSame($expected['date_end'], $result['date_end']);
    }
    if (isset($expected['min_confidence'])) {
      $this->assertGreaterThanOrEqual($expected['min_confidence'], $result['confidence']);
    }
    if (isset($expected['max_confidence'])) {
      $this->assertLessThanOrEqual($expected['max_confidence'], $result['confidence']);
    }
    $this->assertNotSame('', $result['snippet']);
  }

  /**
   * Pattern table: title, body, expected best candidate.
   */
  public static function providerExtract(): array {
    return [
      'house style body opening' => [
        'Van Riebeeck sets sail',
        'On 24 December 1651, accompanied by his wife and son, Jan van Riebeeck set off from Texel.',
        ['date' => '1651-12-24', 'precision' => 'day', 'min_confidence' => 0.95],
      ],
      'house style with the/of' => [
        'A treaty is signed',
        'On the 3rd of October 1685 the Dutch East India Company decided to send refugees.',
        ['date' => '1685-10-03', 'precision' => 'day', 'min_confidence' => 0.95],
      ],
      'date in title' => [
        'Sharpeville massacre, 21 March 1960',
        'Police opened fire on a crowd.',
        ['date' => '1960-03-21', 'precision' => 'day', 'min_confidence' => 0.9],
      ],
      'US order in body' => [
        'A birth',
        'He was born on March 21, 1960 in the Eastern Cape.',
        ['date' => '1960-03-21', 'precision' => 'day', 'min_confidence' => 0.8],
      ],
      'month precision' => [
        'Strike wave',
        'The strikes spread across the Rand during February 1922 and grew violent.',
        ['date' => '1922-02-01', 'precision' => 'month', 'min_confidence' => 0.7],
      ],
      'in-year precision' => [
        'Diamonds discovered',
        'Diamonds were first found near Hopetown in 1867 by a farm boy.',
        ['date' => '1867-01-01', 'precision' => 'year', 'min_confidence' => 0.6],
      ],
      'circa' => [
        'Founding of a settlement',
        'The settlement was established circa 1750 on the banks of the river.',
        ['date' => '1750-01-01', 'precision' => 'circa', 'min_confidence' => 0.6],
      ],
      'year range' => [
        'Anglo-Boer War 1899-1902',
        'The war between Britain and the Boer republics.',
        ['date' => '1899-01-01', 'date_end' => '1902-12-31', 'precision' => 'range', 'min_confidence' => 0.7],
      ],
      'lone year in title' => [
        'The 1913 Land Act',
        'A cornerstone of dispossession.',
        ['date' => '1913-01-01', 'precision' => 'year', 'min_confidence' => 0.5],
      ],
      'unbounded number is NOT a year' => [
        'A massacre',
        'Reports say 5000 people died in the violence.',
        NULL,
      ],
      'invalid day degrades to month precision' => [
        'A story',
        'On 32 January 1960 nothing happened because that date does not exist.',
        ['date' => '1960-01-01', 'precision' => 'month', 'max_confidence' => 0.75],
      ],
      'conflicting years demote confidence' => [
        'Two dates, one event 1990',
        'On 5 June 1652 something happened. Later in 1994 it was commemorated.',
        ['date' => '1652-06-05', 'precision' => 'day', 'max_confidence' => 0.5],
      ],
      'nothing at all' => [
        'Chief Kausobson Kausob is killed',
        'He was killed in a raid near the river.',
        NULL,
      ],
    ];
  }

}
