<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_performance\Unit;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\saho_performance\EventSubscriber\SeoRobotsSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests that only the faceted search routes get a noindex header.
 *
 * The negative cases (a node route, the homepage, a non-HTML response) are
 * the guard against accidentally de-indexing real content.
 *
 * @coversDefaultClass \Drupal\saho_performance\EventSubscriber\SeoRobotsSubscriber
 * @group saho_performance
 */
class SeoRobotsSubscriberTest extends UnitTestCase {

  /**
   * Runs the subscriber for a route + response and returns the robots header.
   *
   * @param string $route_name
   *   The current route name.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to pass through the subscriber.
   *
   * @return string|null
   *   The X-Robots-Tag header value, or NULL if unset.
   */
  private function runFor(string $route_name, Response $response): ?string {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn($route_name);

    $event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      new Request(),
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );

    (new SeoRobotsSubscriber($route_match))->onResponse($event);
    return $event->getResponse()->headers->get('X-Robots-Tag');
  }

  /**
   * Builds an HTML response.
   */
  private function htmlResponse(): Response {
    return new Response('<html></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
  }

  /**
   * @covers ::onResponse
   */
  public function testSearchRoutesGetNoindex(): void {
    foreach ([
      'view.saho_global_search.page_1',
      'view.saho_archive_search.page_1',
      'view.archive.page_1',
    ] as $route) {
      $this->assertSame(
        'noindex, follow',
        $this->runFor($route, $this->htmlResponse()),
        "Route $route should be de-indexed."
      );
    }
  }

  /**
   * @covers ::onResponse
   */
  public function testRealContentRoutesAreUntouched(): void {
    // A node page and the homepage must never be de-indexed.
    $this->assertNull($this->runFor('entity.node.canonical', $this->htmlResponse()));
    $this->assertNull($this->runFor('view.frontpage.page_1', $this->htmlResponse()));
    $this->assertNull($this->runFor('<front>', $this->htmlResponse()));
  }

  /**
   * @covers ::onResponse
   */
  public function testNonHtmlResponseOnSearchRouteIsUntouched(): void {
    $json = new Response('{}', 200, ['Content-Type' => 'application/json']);
    $this->assertNull($this->runFor('view.saho_global_search.page_1', $json));
  }

}
