<?php

declare(strict_types=1);

namespace Drupal\Tests\term_registers\Kernel;

use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\KernelTests\KernelTestBase;
use Drupal\term_registers\EventSubscriber\LegacyListRedirectSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Retired legacy list paths 301 onto their landing register.
 *
 * @group term_registers
 */
final class LegacyListRedirectSubscriberTest extends KernelTestBase {

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
    $this->installConfig(['term_registers']);
  }

  /**
   * Dispatches the subscriber for a raw request path.
   */
  private function dispatch(string $path, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent {
    $subscriber = new LegacyListRedirectSubscriber($this->container->get('config.factory'));
    $event = new RequestEvent($this->container->get('http_kernel'), Request::create($path), $type);
    $subscriber->onRequest($event);
    return $event;
  }

  /**
   * A mapped register path redirects with the register applied.
   */
  public function testRegisterRedirect(): void {
    $response = $this->dispatch('/women-biographies')->getResponse();
    $this->assertInstanceOf(LocalRedirectResponse::class, $response);
    $this->assertSame(301, $response->getStatusCode());
    $this->assertSame('/biographies?tid_1%5B13%5D=13', $response->getTargetUrl());
  }

  /**
   * Case and trailing slash do not matter; label-mode registers encode.
   */
  public function testNormalisedPathAndLabelRegister(): void {
    $response = $this->dispatch('/Archives-Books/')->getResponse();
    $this->assertInstanceOf(LocalRedirectResponse::class, $response);
    $this->assertSame('/archives?type%5BOnline+book%5D=Online+book', $response->getTargetUrl());
  }

  /**
   * Paths without a register land on the landing root.
   */
  public function testRootFallback(): void {
    $response = $this->dispatch('/places-limpopo')->getResponse();
    $this->assertInstanceOf(LocalRedirectResponse::class, $response);
    $this->assertSame('/places', $response->getTargetUrl());
  }

  /**
   * Unmapped paths, sub-requests and the kill-switch all pass through.
   */
  public function testPassThrough(): void {
    $this->assertNull($this->dispatch('/biographies')->getResponse());
    $this->assertNull($this->dispatch('/women-biographies', HttpKernelInterface::SUB_REQUEST)->getResponse());

    $this->config('term_registers.settings')->set('enabled', FALSE)->save();
    $this->assertNull($this->dispatch('/women-biographies')->getResponse());
  }

  /**
   * Every mapped path is normalised (lower-case, no slashes) so lookups hit.
   */
  public function testMapKeysAreNormalised(): void {
    foreach (array_keys(LegacyListRedirectSubscriber::MAP) as $key) {
      $this->assertSame(mb_strtolower(trim($key, '/')), $key);
    }
  }

}
