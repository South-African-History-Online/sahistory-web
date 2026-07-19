<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_api_guard\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the flood-backed JSON:API write rate limiter.
 *
 * @group saho_api_guard
 */
final class JsonApiWriteRateLimiterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'saho_api_guard',
  ];

  /**
   * The authenticated account issuing requests.
   */
  protected User $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['saho_api_guard']);

    // Tight limits so tests exercise both windows cheaply.
    $this->config('saho_api_guard.settings')
      ->set('writes_per_minute', 3)
      ->set('writes_per_hour', 5)
      ->save();

    User::create(['name' => 'root'])->save();
    $this->account = User::create(['name' => 'svc_harvester']);
    $this->account->save();
    $this->container->get('current_user')->setAccount($this->account);
  }

  /**
   * Dispatches a request through the subscriber, returns the set response.
   */
  protected function fire(string $method, string $path): ?int {
    $subscriber = $this->container->get('saho_api_guard.jsonapi_write_rate_limiter');
    $request = Request::create($path, $method);
    $event = new RequestEvent(
      $this->container->get('http_kernel'),
      $request,
      HttpKernelInterface::MAIN_REQUEST
    );
    $subscriber->onRequest($event);
    return $event->getResponse()?->getStatusCode();
  }

  /**
   * Writes within the window pass; the write over the limit 429s.
   */
  public function testMinuteWindow(): void {
    foreach (range(1, 3) as $i) {
      $this->assertNull($this->fire('POST', '/jsonapi/node/archive'), "Write $i within the window passes.");
    }
    $this->assertSame(429, $this->fire('POST', '/jsonapi/node/archive'), 'The 4th write in a minute is rejected.');
  }

  /**
   * GET requests and non-jsonapi paths are never limited.
   */
  public function testOnlyJsonApiWritesAreLimited(): void {
    foreach (range(1, 10) as $i) {
      $this->assertNull($this->fire('GET', '/jsonapi/node/archive'), 'GET is unlimited.');
      $this->assertNull($this->fire('POST', '/node/add/archive'), 'Non-jsonapi paths are unlimited.');
    }
  }

  /**
   * Anonymous requests are skipped (entity access rejects them anyway).
   */
  public function testAnonymousSkipped(): void {
    $this->container->get('current_user')->setAccount(User::getAnonymousUser());
    foreach (range(1, 10) as $i) {
      $this->assertNull($this->fire('POST', '/jsonapi/node/archive'), 'Anonymous writes are not flood-tracked.');
    }
  }

  /**
   * The bypass permission disables limiting for trusted accounts.
   */
  public function testBypassPermission(): void {
    $role = Role::create(['id' => 'bypass', 'label' => 'bypass']);
    $role->grantPermission('bypass api guard rate limits');
    $role->save();
    $trusted = User::create(['name' => 'trusted', 'roles' => ['bypass']]);
    $trusted->save();
    $this->container->get('current_user')->setAccount($trusted);

    foreach (range(1, 10) as $i) {
      $this->assertNull($this->fire('POST', '/jsonapi/node/archive'), 'Bypass permission skips the limiter.');
    }
  }

  /**
   * The config kill switch disables the limiter entirely.
   */
  public function testKillSwitch(): void {
    $this->config('saho_api_guard.settings')->set('rate_limit_enabled', FALSE)->save();
    foreach (range(1, 10) as $i) {
      $this->assertNull($this->fire('POST', '/jsonapi/node/archive'), 'Disabled limiter never responds.');
    }
  }

  /**
   * A 429 carries Retry-After and a JSON:API error body.
   */
  public function testResponseShape(): void {
    foreach (range(1, 3) as $i) {
      $this->fire('POST', '/jsonapi/node/archive');
    }
    $subscriber = $this->container->get('saho_api_guard.jsonapi_write_rate_limiter');
    $request = Request::create('/jsonapi/node/archive', 'POST');
    $event = new RequestEvent(
      $this->container->get('http_kernel'),
      $request,
      HttpKernelInterface::MAIN_REQUEST
    );
    $subscriber->onRequest($event);
    $response = $event->getResponse();
    $this->assertNotNull($response);
    $this->assertSame('60', $response->headers->get('Retry-After'));
    $body = json_decode($response->getContent(), TRUE);
    $this->assertSame('429', $body['errors'][0]['status']);
  }

}
