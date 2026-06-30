<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_linkfix\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * The unmapped-.htm fallback reproduces the old smart-search behaviour.
 *
 * @group saho_linkfix
 */
final class LegacyHtmFallbackSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'saho_linkfix'];

  /**
   * The subscriber under test.
   *
   * @var \Drupal\saho_linkfix\EventSubscriber\LegacyHtmFallbackSubscriber
   */
  protected $subscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->subscriber = $this->container->get('saho_linkfix.legacy_htm_fallback_subscriber');
  }

  /**
   * Dispatch a 404 for a path and return the redirect target, or NULL.
   */
  protected function redirectFor(string $path): ?string {
    $request = Request::create($path);
    $event = new ExceptionEvent(
      $this->container->get('http_kernel'),
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      new NotFoundHttpException(),
    );
    $this->subscriber->onException($event);
    $response = $event->getResponse();
    return $response ? $response->headers->get('Location') : NULL;
  }

  /**
   * A bios path maps to a title-scoped biography search.
   */
  public function testBiosMapsToBiographySearch(): void {
    $target = $this->redirectFor('/pages/people/bios/lembede-am.htm');
    $this->assertNotNull($target);
    $this->assertStringContainsString('search_api_fulltext=lembede-am', $target);
    $this->assertStringContainsString('type=biography', $target);
    $this->assertStringContainsString('title', $target);
  }

  /**
   * A places path maps to a place search.
   */
  public function testPlacesMapsToPlaceSearch(): void {
    $target = $this->redirectFor('/pages/places/robben-island.htm');
    $this->assertStringContainsString('type=place', (string) $target);
  }

  /**
   * An archive path maps to an archive search.
   */
  public function testArchiveMapsToArchiveSearch(): void {
    $target = $this->redirectFor('/pages/archive/some-document.htm');
    $this->assertStringContainsString('type=archive', (string) $target);
  }

  /**
   * An article path strips the legacy articleNNN- prefix.
   */
  public function testArticlePrefixStripped(): void {
    $target = $this->redirectFor('/pages/article123-freedom-charter.htm');
    $this->assertStringContainsString('search_api_fulltext=freedom-charter', (string) $target);
    $this->assertStringContainsString('type=article', (string) $target);
  }

  /**
   * An index.htm redirects to the homepage.
   */
  public function testIndexRedirectsHome(): void {
    $this->assertSame('/', $this->redirectFor('/pages/section/index.htm'));
  }

  /**
   * A non-legacy 404 path is ignored (no response set).
   */
  public function testNonLegacyPathIgnored(): void {
    $this->assertNull($this->redirectFor('/some/clean/path'));
  }

}
