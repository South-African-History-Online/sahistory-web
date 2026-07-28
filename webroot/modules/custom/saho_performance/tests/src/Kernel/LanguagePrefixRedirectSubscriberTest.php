<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_performance\Kernel;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;

/**
 * Real-entity coverage for the language-prefix redirect.
 *
 * The decision matrix lives in the unit test; this covers the two cases a
 * mock cannot honestly fake - a real upcast node with a real translation
 * passing through, and a real untranslated node redirecting.
 *
 * @group saho_performance
 */
final class LanguagePrefixRedirectSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'language',
    'content_translation',
    'saho_performance',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['language', 'saho_performance']);

    ConfigurableLanguage::createFromLangcode('zu')->save();
    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => '', 'zu' => 'zu'])
      ->save();

    NodeType::create(['type' => 'presentation', 'name' => 'Presentation'])->save();
    $this->container->get('content_translation.manager')
      ->setEnabled('node', 'presentation', TRUE);
  }

  /**
   * Runs the subscriber for a path + upcast node, returns the Location.
   */
  private function redirectFor(string $path, NodeInterface $node): ?string {
    $request = Request::create($path);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'entity.node.canonical');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/{node}'));
    $request->attributes->set('node', $node);

    $request_stack = $this->container->get('request_stack');
    $request_stack->push($request);
    try {
      $event = new RequestEvent(
        $this->container->get('http_kernel'),
        $request,
        HttpKernelInterface::MAIN_REQUEST
      );
      $this->container->get('saho_performance.language_prefix_redirect_subscriber')
        ->onRequest($event);
      $response = $event->getResponse();
      return $response ? $response->headers->get('Location') : NULL;
    }
    finally {
      $request_stack->pop();
    }
  }

  /**
   * A real published translation on its canonical route passes through.
   */
  public function testRealTranslationPassesThrough(): void {
    $node = Node::create(['type' => 'presentation', 'title' => 'English deck']);
    $node->addTranslation('zu', ['title' => 'Zulu deck']);
    $node->save();

    $this->assertNull($this->redirectFor('/zu/classroom/grade-4/zulu-deck', $node));
  }

  /**
   * A real untranslated node under a prefix 301s to the unprefixed URL.
   */
  public function testUntranslatedNodeRedirects(): void {
    $node = Node::create(['type' => 'presentation', 'title' => 'English only']);
    $node->save();

    $this->assertSame(
      '/classroom/grade-4/english-only',
      $this->redirectFor('/zu/classroom/grade-4/english-only', $node)
    );
  }

}
