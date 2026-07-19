<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_tools\Unit\Schema;

use Drupal\Tests\UnitTestCase;
use Drupal\saho_tools\Schema\ProvenanceIds;

/**
 * Persistent-identifier detection for provenance source URLs.
 *
 * @group saho_tools
 * @coversDefaultClass \Drupal\saho_tools\Schema\ProvenanceIds
 */
final class ProvenanceIdsTest extends UnitTestCase {

  /**
   * @covers ::isPersistent
   * @covers ::propertyId
   * @dataProvider provideUrls
   */
  public function testDetection(string $uri, string $title, bool $persistent, ?string $scheme): void {
    $this->assertSame($persistent, ProvenanceIds::isPersistent($uri, $title));
    $this->assertSame($scheme, ProvenanceIds::propertyId($uri));
  }

  /**
   * URL shapes from the Catalyst provenance imports.
   */
  public static function provideUrls(): array {
    return [
      'n2t ark' => ['https://n2t.net/ark:/13030/hb0f59n7bx', '', TRUE, 'ark'],
      'cdlib ark' => ['https://ark.cdlib.org/ark:/13030/tf0z09n5jk', '', TRUE, 'ark'],
      'doi' => ['https://doi.org/10.2307/2637233', '', TRUE, 'doi'],
      'dx doi' => ['https://dx.doi.org/10.2307/2637233', '', TRUE, 'doi'],
      'handle' => ['https://hdl.handle.net/10855/12345', '', TRUE, 'handle'],
      'purl' => ['https://purl.org/dc/terms/', '', TRUE, 'purl'],
      'www prefix stripped' => ['https://www.doi.org/10.1000/x', '', TRUE, 'doi'],
      'uppercase host' => ['https://N2T.net/ark:/99999/x', '', TRUE, 'ark'],
      'ark in provider path stays provider' => [
        'https://calisphere.org/item/ark:/13030/hb0f59n7bx/',
        '',
        FALSE,
        NULL,
      ],
      'ordinary provider page' => ['https://archive.org/details/something', '', FALSE, NULL],
      'title hint forces persistent' => [
        'https://tessa.lapl.org/some/item',
        'Persistent link',
        TRUE,
        NULL,
      ],
      'title hint case-insensitive' => ['https://example.org/x', 'PERSISTENT URL', TRUE, NULL],
      'no host' => ['not-a-url', '', FALSE, NULL],
    ];
  }

}
