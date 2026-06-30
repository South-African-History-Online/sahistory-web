<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_linkfix\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * End-to-end HTTP routing for legacy .htm links, through the real kernel.
 *
 * This handles genuine Request -> Response cycles via the http_kernel service,
 * so it exercises the full pipeline the .htaccess change exposes: routing, the
 * redirect module's REQUEST-phase subscriber (precise legacy redirect to the
 * exact node), and saho_linkfix's EXCEPTION-phase fallback subscriber (typed
 * search guess for unmapped .htm). It needs no browser, so it runs in the same
 * kernel suite that CI executes - unlike a BrowserTestBase test, which this
 * project has no local or CI browser to run.
 *
 * @group saho_linkfix
 */
final class LegacyLinkRoutingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'link',
    'path_alias',
    'redirect',
    'saho_linkfix',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('redirect');
    $this->installEntitySchema('path_alias');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'field', 'filter', 'node']);
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
  }

  /**
   * Create a published article and return its id.
   */
  protected function makeArticle(string $title): int {
    $node = Node::create(['type' => 'article', 'title' => $title, 'status' => 1]);
    $node->save();
    return (int) $node->id();
  }

  /**
   * Handle a GET request through the real HTTP kernel.
   */
  protected function handle(string $path): Response {
    // The redirect module only acts when the request runs through index.php
    // (RedirectChecker::canRedirect), so emulate that for a realistic pipeline.
    $request = Request::create($path, 'GET', [], [], [], [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => 'index.php',
    ]);
    // A fresh kernel handle each time keeps the container request stack clean.
    return $this->container->get('http_kernel')->handle($request);
  }

  /**
   * A mapped legacy .htm URL 301s to the exact node.
   */
  public function testPreciseRedirectResolvesToExactNode(): void {
    $nid = $this->makeArticle('South African Indian Congress SAIC');
    $this->container->get('saho_linkfix.redirect_writer')->apply([
      [
        'source_path' => 'pages/governence-projects/organisations/saic/saic.htm',
        'uri' => 'internal:/node/' . $nid,
      ],
    ], ['dry_run' => FALSE]);

    $response = $this->handle('/pages/governence-projects/organisations/saic/saic.htm');
    $this->assertTrue($response->isRedirect(), 'A redirect was returned.');
    $this->assertStringContainsString('/node/' . $nid, (string) $response->headers->get('Location'));
    // It did NOT fall through to the search guess.
    $this->assertStringNotContainsString('/search', (string) $response->headers->get('Location'));
  }

  /**
   * An unmapped people/bios .htm falls back to a typed biography search.
   */
  public function testUnmappedBioFallsBackToBiographySearch(): void {
    $response = $this->handle('/pages/people/bios/no-such-person.htm');
    $location = (string) $response->headers->get('Location');
    $this->assertTrue($response->isRedirect());
    $this->assertStringContainsString('/search', $location);
    $this->assertStringContainsString('search_api_fulltext=no-such-person', $location);
    $this->assertStringContainsString('type=biography', $location);
  }

  /**
   * An unmapped archive .htm falls back to a typed archive search.
   */
  public function testUnmappedArchiveFallsBackToArchiveSearch(): void {
    $response = $this->handle('/pages/archive/some-missing-document.htm');
    $this->assertStringContainsString('type=archive', (string) $response->headers->get('Location'));
  }

  /**
   * A precise redirect wins over the fallback.
   *
   * Even when the path also matches the people/bios pattern, the redirect fires
   * in the REQUEST phase, so the request never reaches the 404 fallback
   * subscriber.
   */
  public function testPreciseRedirectBeatsFallback(): void {
    $nid = $this->makeArticle('Oliver Tambo Biography');
    $this->container->get('saho_linkfix.redirect_writer')->apply([
      ['source_path' => 'pages/people/bios/tambo,o.htm', 'uri' => 'internal:/node/' . $nid],
    ], ['dry_run' => FALSE]);

    $location = (string) $this->handle('/pages/people/bios/tambo,o.htm')->headers->get('Location');
    $this->assertStringContainsString('/node/' . $nid, $location);
    $this->assertStringNotContainsString('/search', $location);
  }

}
