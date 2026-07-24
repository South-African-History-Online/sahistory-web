<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_performance\Unit;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\saho_performance\EventSubscriber\FacetFloodSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the facet-flood cap on the classroom browse route.
 *
 * The negative cases guard against rejecting real people: a handful of
 * selections, the bare page, and other routes must always pass through.
 *
 * @coversDefaultClass \Drupal\saho_performance\EventSubscriber\FacetFloodSubscriber
 * @group saho_performance
 */
class FacetFloodSubscriberTest extends UnitTestCase {

  /**
   * Runs the subscriber for a route + URI and returns the set response.
   *
   * @param string $route_name
   *   The current route name.
   * @param string $uri
   *   The request URI including any query string.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   The short-circuit response, or NULL when the request passes through.
   */
  private function runFor(string $route_name, string $uri) {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn($route_name);

    $event = new RequestEvent(
      $this->createMock(HttpKernelInterface::class),
      Request::create($uri),
      HttpKernelInterface::MAIN_REQUEST
    );

    (new FacetFloodSubscriber($route_match))->onRequest($event);
    return $event->getResponse();
  }

  /**
   * Builds a classroom URI selecting the given number of facet values.
   */
  private function classroomUri(int $topics, int $grades): string {
    $params = [];
    for ($i = 0; $i < $topics; $i++) {
      $tid = 35804 + $i;
      $params[] = "caps_topic%5B$tid%5D=$tid";
    }
    for ($i = 0; $i < $grades; $i++) {
      $tid = 35779 + $i;
      $params[] = "grade%5B$tid%5D=$tid";
    }
    return '/classroom/presentations?' . implode('&', $params);
  }

  /**
   * @covers ::onRequest
   */
  public function testEnumerationSweepIsRejected(): void {
    // Shape taken from the 2026-07-24 scraper sweep: 22 topics + 4 grades.
    $response = $this->runFor(
      'view.classroom_presentations.page_1',
      $this->classroomUri(22, 4)
    );
    $this->assertNotNull($response);
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::onRequest
   */
  public function testJustOverTheCapIsRejected(): void {
    $response = $this->runFor(
      'view.classroom_presentations.page_1',
      $this->classroomUri(6, 3)
    );
    $this->assertNotNull($response);
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::onRequest
   */
  public function testRealisticSelectionPassesThrough(): void {
    // A teacher picking a few topics and a couple of grades.
    $this->assertNull($this->runFor(
      'view.classroom_presentations.page_1',
      $this->classroomUri(3, 2)
    ));
  }

  /**
   * @covers ::onRequest
   */
  public function testBareBrowsePagePassesThrough(): void {
    $this->assertNull($this->runFor(
      'view.classroom_presentations.page_1',
      '/classroom/presentations'
    ));
  }

  /**
   * @covers ::onRequest
   */
  public function testOtherRoutesAreNeverCapped(): void {
    // Same flood-shaped query string on another route must pass through.
    $this->assertNull($this->runFor(
      'view.saho_global_search.page_1',
      '/search?' . substr($this->classroomUri(22, 4), strlen('/classroom/presentations?'))
    ));
  }

}
