<?php

namespace Drupal\saho_api_guard\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rate-limits JSON:API write requests per authenticated account.
 *
 * Runs as a request subscriber rather than http_middleware because the
 * per-account flood key needs the authenticated user, which only exists
 * after AuthenticationSubscriber (kernel.request priority 300). Priority 35
 * keeps us ahead of routing (32) so flooded requests are rejected before
 * any routing/controller cost.
 *
 * GET is deliberately unlimited: reads are access-checked and cheap, the
 * harvester's dedupe lookups depend on them, and edge protection belongs to
 * Cloudflare. Anonymous writers are skipped - entity access rejects them
 * anyway, so there is no point burning flood rows on them.
 */
final class JsonApiWriteRateLimiter implements EventSubscriberInterface {

  /**
   * Flood event name for the per-minute window.
   */
  protected const EVENT_MINUTE = 'saho_api_guard.jsonapi_write_minute';

  /**
   * Flood event name for the per-hour window.
   */
  protected const EVENT_HOUR = 'saho_api_guard.jsonapi_write_hour';

  /**
   * HTTP methods that count as writes.
   */
  protected const WRITE_METHODS = ['POST', 'PATCH', 'DELETE'];

  public function __construct(
    protected FloodInterface $flood,
    protected AccountProxyInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [KernelEvents::REQUEST => [['onRequest', 35]]];
  }

  /**
   * Rejects JSON:API writes that exceed the configured flood windows.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    $request = $event->getRequest();
    if (!in_array($request->getMethod(), self::WRITE_METHODS, TRUE)) {
      return;
    }
    if (!str_starts_with($request->getPathInfo(), '/jsonapi/')) {
      return;
    }

    $config = $this->configFactory->get('saho_api_guard.settings');
    if (!$config->get('rate_limit_enabled')) {
      return;
    }
    if ($this->currentUser->isAnonymous()) {
      return;
    }
    if ($this->currentUser->hasPermission('bypass api guard rate limits')) {
      return;
    }

    $identifier = 'uid:' . $this->currentUser->id();
    $per_minute = max(1, (int) $config->get('writes_per_minute'));
    $per_hour = max(1, (int) $config->get('writes_per_hour'));

    if (!$this->flood->isAllowed(self::EVENT_MINUTE, $per_minute, 60, $identifier)) {
      $event->setResponse($this->tooManyRequests(60));
      return;
    }
    if (!$this->flood->isAllowed(self::EVENT_HOUR, $per_hour, 3600, $identifier)) {
      $event->setResponse($this->tooManyRequests(3600));
      return;
    }
    $this->flood->register(self::EVENT_MINUTE, 60, $identifier);
    $this->flood->register(self::EVENT_HOUR, 3600, $identifier);
  }

  /**
   * Builds a JSON:API-shaped 429 response.
   */
  protected function tooManyRequests(int $retry_after): JsonResponse {
    return new JsonResponse(
      [
        'errors' => [
          [
            'status' => '429',
            'title' => 'Too Many Requests',
            'detail' => 'JSON:API write rate limit exceeded. Slow down and retry after the indicated interval.',
          ],
        ],
      ],
      429,
      [
        'Retry-After' => (string) $retry_after,
        'Content-Type' => 'application/vnd.api+json',
      ]
    );
  }

}
