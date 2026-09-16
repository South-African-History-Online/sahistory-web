<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_linkfix\Unit;

use Drupal\saho_linkfix\Service\LinkRotReport;
use Drupal\Tests\UnitTestCase;

/**
 * Noise filtering and Markdown rendering of the link-rot report.
 *
 * @group saho_linkfix
 * @coversDefaultClass \Drupal\saho_linkfix\Service\LinkRotReport
 */
final class LinkRotReportTest extends UnitTestCase {

  /**
   * @covers ::isNoise
   * @dataProvider noiseProvider
   */
  public function testIsNoise(string $path, bool $expected): void {
    $this->assertSame($expected, LinkRotReport::isNoise($path));
  }

  /**
   * Data provider for testIsNoise().
   */
  public static function noiseProvider(): array {
    return [
      'wordpress probe' => ['/wp-admin/admin-ajax.php', TRUE],
      'php probe' => ['/vendor/phpunit/src/eval-stdin.php', TRUE],
      'jsf probe' => ['/javax.faces.resource.../WEB-INF/web.xml.jsf', TRUE],
      'webdav' => ['/webdav', TRUE],
      'sling' => ['/sling', TRUE],
      'graphql' => ['/graphql', TRUE],
      'dotfile' => ['/.env', TRUE],
      'git' => ['/.git/config', TRUE],
      'real article' => ['/article/khoikhoi', FALSE],
      'retired list view' => ['/archives-by-type', FALSE],
      'null bug path' => ['/people/null', FALSE],
      'trailing quote' => ['/article/south-african-student-organisation-leaders"', FALSE],
    ];
  }

  /**
   * @covers ::isNullBug
   */
  public function testIsNullBug(): void {
    $this->assertTrue(LinkRotReport::isNullBug('/people/null'));
    $this->assertTrue(LinkRotReport::isNullBug('/null'));
    $this->assertTrue(LinkRotReport::isNullBug('/node/undefined'));
    $this->assertTrue(LinkRotReport::isNullBug('/dated-event/null?x=1'));
    $this->assertFalse(LinkRotReport::isNullBug('/people/nullah-mbeki'));
    $this->assertFalse(LinkRotReport::isNullBug('/article/khoikhoi'));
  }

  /**
   * @covers ::render
   */
  public function testRender(): void {
    $not_found = [
      ['path' => '/people/null', 'count' => 2448, 'last' => '2026-09-10', 'null_bug' => TRUE],
      ['path' => '/article/khoikhoi', 'count' => 936, 'last' => '2026-09-10', 'null_bug' => FALSE],
    ];
    $broken = [
      [
        'url' => 'http://example.org/gone',
        'code' => 404,
        'fail_count' => 12,
        'last_check' => '2026-09-14',
        'pages' => 2,
        'sample' => ['node/1', 'node/2'],
      ],
    ];
    $md = LinkRotReport::render($not_found, $broken, ['unresolved_404' => 9728, 'broken_links' => 320], 'https://sahistory.org.za', '2026-09-15');

    $this->assertStringContainsString('## Link-rot report - 2026-09-15', $md);
    $this->assertStringContainsString('9,728 unresolved 404 paths', $md);
    $this->assertStringContainsString('320 distinct dead links', $md);
    $this->assertStringContainsString('### Site-script bugs', $md);
    $this->assertStringContainsString('| 2,448 | `/people/null` | 2026-09-10 |', $md);
    $this->assertStringContainsString('[`/article/khoikhoi`](https://sahistory.org.za/article/khoikhoi)', $md);
    $this->assertStringContainsString('| 404 | 2 | `http://example.org/gone` | [node/1](https://sahistory.org.za/node/1), [node/2](https://sahistory.org.za/node/2) |', $md);
    // The null-bug row must not also appear under missing pages.
    $this->assertSame(1, substr_count($md, '/people/null'));
  }

  /**
   * @covers ::render
   */
  public function testRenderEmpty(): void {
    $md = LinkRotReport::render([], [], ['unresolved_404' => 0, 'broken_links' => 0]);
    $this->assertStringContainsString('None above the threshold.', $md);
    $this->assertStringContainsString('None reported by linkchecker.', $md);
    $this->assertStringNotContainsString('Site-script bugs', $md);
  }

}
