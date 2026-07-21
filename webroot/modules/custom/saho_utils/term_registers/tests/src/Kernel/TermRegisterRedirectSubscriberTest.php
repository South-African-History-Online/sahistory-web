<?php

declare(strict_types=1);

namespace Drupal\Tests\term_registers\Kernel;

use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\term_registers\EventSubscriber\TermRegisterRedirectSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;

/**
 * The term-register redirect maps term pages onto filtered landings.
 *
 * @group term_registers
 */
final class TermRegisterRedirectSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'taxonomy',
    'term_registers',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installConfig(['term_registers']);
    foreach (['member_of_organisation', 'saldru_archive_topic', 'african_country', 'field_arts_culture_categories'] as $vid) {
      Vocabulary::create(['vid' => $vid, 'name' => $vid])->save();
    }
  }

  /**
   * Dispatches the subscriber for a term on a given route name.
   */
  private function dispatch(Term $term, string $route_name = 'entity.taxonomy_term.canonical'): RequestEvent {
    $route = new Route('/taxonomy/term/{taxonomy_term}');
    $route_match = new RouteMatch($route_name, $route, ['taxonomy_term' => $term], ['taxonomy_term' => $term->id()]);
    $subscriber = new TermRegisterRedirectSubscriber($route_match, $this->container->get('config.factory'));
    $event = new RequestEvent(
      $this->container->get('http_kernel'),
      Request::create('/taxonomy/term/' . $term->id()),
      HttpKernelInterface::MAIN_REQUEST
    );
    $subscriber->onRequest($event);
    return $event;
  }

  /**
   * A tid-mode vocabulary redirects to its landing with the tid applied.
   */
  public function testTidModeRedirect(): void {
    $term = Term::create(['vid' => 'member_of_organisation', 'name' => '(ANC) African National Congress']);
    $term->save();
    $event = $this->dispatch($term);
    $response = $event->getResponse();
    $this->assertInstanceOf(LocalRedirectResponse::class, $response);
    $this->assertSame(301, $response->getStatusCode());
    $tid = $term->id();
    $this->assertSame("/biographies?org%5B$tid%5D=$tid", $response->getTargetUrl());
  }

  /**
   * A label-mode vocabulary redirects with the term label as facet value.
   */
  public function testLabelModeRedirect(): void {
    $term = Term::create(['vid' => 'saldru_archive_topic', 'name' => 'Labour']);
    $term->save();
    $event = $this->dispatch($term);
    $response = $event->getResponse();
    $this->assertInstanceOf(LocalRedirectResponse::class, $response);
    $this->assertSame('/archives?saldru%5BLabour%5D=Labour', $response->getTargetUrl());
  }

  /**
   * Topic-section categories land on their topic shell with the chip set.
   */
  public function testTopicCategoryRedirect(): void {
    $term = Term::create(['vid' => 'field_arts_culture_categories', 'name' => 'Sport']);
    $term->save();
    $event = $this->dispatch($term);
    $response = $event->getResponse();
    $this->assertInstanceOf(LocalRedirectResponse::class, $response);
    $tid = $term->id();
    $this->assertSame("/art-culture?tid_1%5B$tid%5D=$tid", $response->getTargetUrl());
  }

  /**
   * Unmapped vocabularies pass through - the Africa architecture survives.
   */
  public function testUnmappedVocabularyPassesThrough(): void {
    $term = Term::create(['vid' => 'african_country', 'name' => 'Ghana']);
    $term->save();
    $event = $this->dispatch($term);
    $this->assertNull($event->getResponse());
  }

  /**
   * An empty (untagged) term still redirects to the register empty state.
   */
  public function testEmptyTermStillRedirects(): void {
    $term = Term::create(['vid' => 'member_of_organisation', 'name' => 'AOL African Orthodox Church']);
    $term->save();
    $event = $this->dispatch($term);
    $this->assertNotNull($event->getResponse());
  }

  /**
   * The kill-switch disables every redirect without a code change.
   */
  public function testKillSwitch(): void {
    $this->config('term_registers.settings')->set('enabled', FALSE)->save();
    $term = Term::create(['vid' => 'member_of_organisation', 'name' => 'ANC']);
    $term->save();
    $event = $this->dispatch($term);
    $this->assertNull($event->getResponse());
  }

  /**
   * Non-canonical term routes (edit forms etc.) are never redirected.
   */
  public function testNonCanonicalRoutePassesThrough(): void {
    $term = Term::create(['vid' => 'member_of_organisation', 'name' => 'ANC']);
    $term->save();
    $event = $this->dispatch($term, 'entity.taxonomy_term.edit_form');
    $this->assertNull($event->getResponse());
  }

}
