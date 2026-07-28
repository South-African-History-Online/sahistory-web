<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_performance\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_performance\EventSubscriber\LanguagePrefixRedirectSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;

/**
 * Tests the language-prefix duplicate redirect decision matrix.
 *
 * The no-redirect cases are the guard against breaking real content: the
 * unprefixed site, editor admin routes, and published node translations
 * (classroom decks) must always pass through untouched.
 *
 * @coversDefaultClass \Drupal\saho_performance\EventSubscriber\LanguagePrefixRedirectSubscriber
 * @group saho_performance
 */
class LanguagePrefixRedirectSubscriberTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Cache::mergeContexts() validates context tokens through the global
    // container - stub the validator so cacheable 301s can be built.
    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Runs the subscriber for a request/route and returns the Location header.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   * @param string $route_name
   *   The matched route name.
   * @param \Symfony\Component\Routing\Route|null $route
   *   The route object; NULL simulates an unrouted request.
   * @param \Drupal\node\NodeInterface|null $node
   *   The upcast node parameter, if any.
   * @param bool $enabled
   *   The kill-switch state.
   * @param int $request_type
   *   Main or sub request.
   *
   * @return string|null
   *   The redirect target, or NULL when the request passes through.
   */
  private function runFor(
    Request $request,
    string $route_name = 'entity.node.canonical',
    ?Route $route = new Route('/'),
    ?NodeInterface $node = NULL,
    bool $enabled = TRUE,
    int $request_type = HttpKernelInterface::MAIN_REQUEST,
  ): ?string {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn($route_name);
    $route_match->method('getRouteObject')->willReturn($route);
    $route_match->method('getParameter')->with('node')->willReturn($node);

    $event = new RequestEvent(
      $this->createMock(HttpKernelInterface::class),
      $request,
      $request_type
    );

    $subscriber = new LanguagePrefixRedirectSubscriber($route_match, $this->configFactory($enabled));
    $subscriber->onRequest($event);

    $response = $event->getResponse();
    if ($response === NULL) {
      return NULL;
    }
    $this->assertSame(301, $response->getStatusCode());
    return $response->headers->get('Location');
  }

  /**
   * Builds a config factory serving real immutable config objects.
   *
   * Real ImmutableConfig (not stubs) so the subscriber's dot-notation get()
   * and cacheable-dependency merging run for real.
   */
  private function configFactory(bool $enabled): ConfigFactoryInterface {
    $storage = $this->createMock(StorageInterface::class);
    $dispatcher = $this->createMock(EventDispatcherInterface::class);
    $typed = $this->createMock(TypedConfigManagerInterface::class);

    $settings = new ImmutableConfig('saho_performance.settings', $storage, $dispatcher, $typed);
    $settings->initWithData(['language_redirect_enabled' => $enabled]);

    $negotiation = new ImmutableConfig('language.negotiation', $storage, $dispatcher, $typed);
    $negotiation->initWithData([
      'url' => [
        'prefixes' => [
          'en' => '',
          'af' => 'af',
          'zu' => 'zu',
          'xh' => 'xh',
          'nso' => 'nso',
        ],
      ],
    ]);

    $factory = $this->createMock(ConfigFactoryInterface::class);
    $factory->method('get')->willReturnMap([
      ['saho_performance.settings', $settings],
      ['language.negotiation', $negotiation],
    ]);
    return $factory;
  }

  /**
   * Mocks a node whose zu translation exists with the given publish state.
   */
  private function translatedNode(bool $published): NodeInterface {
    $translation = $this->createMock(NodeInterface::class);
    $translation->method('isPublished')->willReturn($published);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasTranslation')->with('zu')->willReturn(TRUE);
    $node->method('getTranslation')->with('zu')->willReturn($translation);
    return $node;
  }

  /**
   * @covers ::onRequest
   */
  public function testPrefixedAliasRedirectsToUnprefixed(): void {
    $this->assertSame(
      '/people/solomon-kalushi-mahlangu',
      $this->runFor(Request::create('/nso/people/solomon-kalushi-mahlangu'))
    );
  }

  /**
   * @covers ::onRequest
   */
  public function testQueryStringIsPreserved(): void {
    $this->assertSame(
      '/search?search_api_fulltext=x',
      $this->runFor(Request::create('/zu/search?search_api_fulltext=x'), 'view.saho_global_search.page_1')
    );
  }

  /**
   * @covers ::onRequest
   */
  public function testBarePrefixRedirectsToFrontPage(): void {
    $this->assertSame('/', $this->runFor(Request::create('/zu')));
    $this->assertSame('/', $this->runFor(Request::create('/zu/')));
  }

  /**
   * @covers ::onRequest
   */
  public function testUntranslatedNodeRedirects(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasTranslation')->willReturn(FALSE);
    $this->assertSame(
      '/people/florence-elizabeth-mnumzana',
      $this->runFor(Request::create('/xh/people/florence-elizabeth-mnumzana'), 'entity.node.canonical', new Route('/'), $node)
    );
  }

  /**
   * @covers ::onRequest
   */
  public function testPublishedTranslationIsSpared(): void {
    $this->assertNull(
      $this->runFor(Request::create('/zu/classroom/grade-4/some-deck'), 'entity.node.canonical', new Route('/'), $this->translatedNode(TRUE))
    );
  }

  /**
   * @covers ::onRequest
   */
  public function testUnpublishedTranslationStillRedirects(): void {
    $this->assertSame(
      '/classroom/grade-4/some-deck',
      $this->runFor(Request::create('/zu/classroom/grade-4/some-deck'), 'entity.node.canonical', new Route('/'), $this->translatedNode(FALSE))
    );
  }

  /**
   * @covers ::onRequest
   */
  public function testNodeSubRouteRedirectsEvenForTranslatedNode(): void {
    // /node/N/connections renders English UI identical to its unprefixed
    // twin - the translation exception applies to the canonical route only.
    $this->assertSame(
      '/node/16528/connections',
      $this->runFor(Request::create('/zu/node/16528/connections'), 'saho_connections.hub', new Route('/'), $this->translatedNode(TRUE))
    );
  }

  /**
   * @covers ::onRequest
   */
  public function testUnprefixedPathPassesThrough(): void {
    $this->assertNull($this->runFor(Request::create('/people/solomon-kalushi-mahlangu')));
    $this->assertNull($this->runFor(Request::create('/')));
  }

  /**
   * @covers ::onRequest
   */
  public function testUnknownFirstSegmentDoesNotMatchPrefixes(): void {
    // 'peoples' must not fuzzy-match any prefix.
    $this->assertNull($this->runFor(Request::create('/peoples/x')));
  }

  /**
   * @covers ::onRequest
   */
  public function testUnsafeMethodPassesThrough(): void {
    $this->assertNull($this->runFor(Request::create('/zu/people/x', 'POST')));
  }

  /**
   * @covers ::onRequest
   */
  public function testSubRequestPassesThrough(): void {
    $this->assertNull(
      $this->runFor(Request::create('/zu/people/x'), 'entity.node.canonical', new Route('/'), NULL, TRUE, HttpKernelInterface::SUB_REQUEST)
    );
  }

  /**
   * @covers ::onRequest
   */
  public function testAdminRoutePassesThrough(): void {
    $route = new Route('/node/{node}/edit');
    $route->setOption('_admin_route', TRUE);
    $this->assertNull(
      $this->runFor(Request::create('/zu/node/1/edit'), 'entity.node.edit_form', $route)
    );
  }

  /**
   * @covers ::onRequest
   */
  public function testUnroutedRequestPassesThrough(): void {
    $this->assertNull($this->runFor(Request::create('/zu/people/x'), '', NULL));
  }

  /**
   * @covers ::onRequest
   */
  public function testKillSwitchDisablesRedirects(): void {
    $this->assertNull(
      $this->runFor(Request::create('/nso/people/x'), 'entity.node.canonical', new Route('/'), NULL, FALSE)
    );
  }

}
