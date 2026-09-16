<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_linkfix\Unit;

use Drupal\saho_linkfix\Service\HotlinkResolver;
use Drupal\Tests\UnitTestCase;

/**
 * Classification and path mapping of hot-linked image sources.
 *
 * @group saho_linkfix
 * @coversDefaultClass \Drupal\saho_linkfix\Service\HotlinkResolver
 */
final class HotlinkResolverTest extends UnitTestCase {

  /**
   * @covers ::classify
   * @dataProvider classifyProvider
   */
  public function testClassify(string $src, string $expected): void {
    $this->assertSame($expected, HotlinkResolver::classify($src));
  }

  /**
   * Data provider for testClassify().
   */
  public static function classifyProvider(): array {
    return [
      'apex' => ['https://sahistory.org.za/sites/default/files/a.jpg', 'self'],
      'www' => ['http://www.sahistory.org.za/sites/default/files/a.jpg', 'self'],
      'v1' => ['http://v1.sahistory.org.za/sites/default/files/a.jpg', 'self'],
      'www v1' => ['http://www.v1.sahistory.org.za/files/a.jpg', 'self'],
      'old dev host with port' => ['http://sahoseventhree.dd:8083/sites/default/files/a.jpg', 'self'],
      'staging' => ['https://staging.sahistory.org.za/sites/default/files/a.jpg', 'self'],
      'lookalike is external' => ['https://sahistory.org.za.evil.com/a.jpg', 'external'],
      'third party' => ['https://businesstech.co.za/x.png', 'external'],
      'youtube' => ['https://www.youtube.com/vi/abc/0.jpg', 'external'],
      'relative' => ['/sites/default/files/a.jpg', 'other'],
      'protocol relative' => ['//sahistory.org.za/a.jpg', 'other'],
      'data uri' => ['data:image/png;base64,AAAA', 'other'],
    ];
  }

  /**
   * @covers ::localPath
   */
  public function testLocalPath(): void {
    $this->assertSame('/sites/default/files/a%20b.jpg', HotlinkResolver::localPath('https://www.sahistory.org.za/sites/default/files/a%20b.jpg'));
    $this->assertSame('/sites/default/files/x.jpg?itok=abc', HotlinkResolver::localPath('http://sahoseventhree.dd:8083/sites/default/files/x.jpg?itok=abc'));
    $this->assertNull(HotlinkResolver::localPath('https://businesstech.co.za/x.png'));
    $this->assertNull(HotlinkResolver::localPath('/already/relative.jpg'));
  }

  /**
   * @covers ::extractImageSources
   */
  public function testExtractImageSources(): void {
    $html = '<p><img alt="x" src="https://sahistory.org.za/a.jpg" /> text <IMG SRC="http://v1.sahistory.org.za/b.jpg"> '
      . '<img src="/relative.jpg"> <a href="https://sahistory.org.za/c.jpg">link</a> <img src="https://sahistory.org.za/a.jpg">';
    $this->assertSame(
      ['https://sahistory.org.za/a.jpg', 'http://v1.sahistory.org.za/b.jpg'],
      HotlinkResolver::extractImageSources($html)
    );
    $this->assertSame([], HotlinkResolver::extractImageSources('<p>no images</p>'));
  }

}
