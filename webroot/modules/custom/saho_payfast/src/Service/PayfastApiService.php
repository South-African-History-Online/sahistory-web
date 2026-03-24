<?php

namespace Drupal\saho_payfast\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Payfast\PayfastCommon\Aggregator\Request\PaymentRequest;

/**
 * PayFast API service for subscription and payment management.
 *
 * Wraps the payfast-common vendor library to provide an injectable, testable
 * API client shared across saho_donate (one-time payments) and the
 * commerce_payfast gateway (recurring subscriptions).
 *
 * Credentials are read from Drupal Settings (settings.php / settings.local.php)
 * so they are never stored in the database or configuration:
 *
 * @code
 * $settings['payfast_merchant_id']  = 'YOUR_MERCHANT_ID';
 * $settings['payfast_merchant_key'] = 'YOUR_MERCHANT_KEY';
 * $settings['payfast_passphrase']   = 'YOUR_PASSPHRASE';
 * $settings['payfast_test_mode']    = TRUE;
 * @endcode
 *
 * Note on merchant_key: PayFast uses merchant_key only for checkout form
 * signature (handled by commerce_payfast's PaymentOffsiteForm). The
 * Subscription Payments API authenticates via merchant-id header plus an
 * HMAC-MD5 signature computed from the passphrase - merchant_key is not
 * a parameter in those requests. It is stored here for completeness and
 * for future use (e.g. ad-hoc charge, refund endpoints).
 */
class PayfastApiService {

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The PayFast merchant ID.
   *
   * @var string
   */
  protected string $merchantId;

  /**
   * The PayFast merchant key.
   *
   * Used for checkout form signing. Stored here for completeness; not required
   * for Subscription API calls which authenticate via passphrase-based HMAC.
   *
   * @var string
   */
  protected string $merchantKey;

  /**
   * The PayFast passphrase (used for API request signature generation).
   *
   * @var string
   */
  protected string $passphrase;

  /**
   * Whether to use PayFast sandbox (test) mode.
   *
   * @var bool
   */
  protected bool $testMode;

  /**
   * The underlying PayFast API client from the vendor library.
   *
   * @var \Payfast\PayfastCommon\Aggregator\Request\PaymentRequest
   */
  protected PaymentRequest $client;

  /**
   * Constructs a PayfastApiService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('saho_payfast');
    $this->merchantId = Settings::get('payfast_merchant_id', '');
    $this->merchantKey = Settings::get('payfast_merchant_key', '');
    $this->passphrase = Settings::get('payfast_passphrase', '');
    $this->testMode = (bool) Settings::get('payfast_test_mode', FALSE);
    $this->client = new PaymentRequest();
  }

  /**
   * Cancels a PayFast recurring subscription at the gateway.
   *
   * Should be called when a commerce_subscription entity transitions to the
   * 'canceled' state, before role and access changes take effect in Drupal.
   *
   * Silently returns FALSE (with a log warning) when the token is empty -
   * this is expected for test or manually-created subscriptions that were
   * never processed through a real PayFast transaction.
   *
   * @param string $token
   *   The PayFast subscription token received in the original ITN notification.
   *
   * @return bool
   *   TRUE if PayFast accepted the cancellation (HTTP 200), FALSE otherwise.
   */
  public function cancelSubscription(string $token): bool {
    if (empty($token)) {
      $this->logger->warning('PayFast subscription cancel skipped: token is empty.');
      return FALSE;
    }

    $response = $this->client->subscriptionAction(
      $this->merchantId,
      $token,
      'cancel',
      [],
      $this->passphrase,
      $this->testMode
    );

    return $this->parseApiResponse($response, 'cancel', $token);
  }

  /**
   * Fetches the current status of a PayFast subscription from the gateway.
   *
   * Useful for reconciling Drupal subscription state against PayFast's actual
   * billing state during support investigations or dunning recovery.
   *
   * @param string $token
   *   The PayFast subscription token.
   *
   * @return array|null
   *   The decoded response data array on success, or NULL on failure.
   */
  public function fetchSubscription(string $token): ?array {
    if (empty($token)) {
      return NULL;
    }

    $response = $this->client->subscriptionAction(
      $this->merchantId,
      $token,
      'fetch',
      [],
      $this->passphrase,
      $this->testMode
    );

    if (!$this->looksLikeJson($response)) {
      $this->logger->error(
        'PayFast fetch returned a network error for token @token: @error',
        ['@token' => $token, '@error' => $this->truncate($response)]
      );
      return NULL;
    }

    $decoded = json_decode($response, TRUE);
    if ($decoded === NULL) {
      $this->logger->error(
        'PayFast fetch returned malformed JSON for token @token.',
        ['@token' => $token]
      );
      return NULL;
    }

    return $decoded['data'] ?? NULL;
  }

  /**
   * Pauses a PayFast subscription at the gateway.
   *
   * @param string $token
   *   The PayFast subscription token.
   *
   * @return bool
   *   TRUE if PayFast accepted the pause request, FALSE otherwise.
   */
  public function pauseSubscription(string $token): bool {
    if (empty($token)) {
      $this->logger->warning('PayFast subscription pause skipped: token is empty.');
      return FALSE;
    }

    $response = $this->client->subscriptionAction(
      $this->merchantId,
      $token,
      'pause',
      [],
      $this->passphrase,
      $this->testMode
    );

    return $this->parseApiResponse($response, 'pause', $token);
  }

  /**
   * Unpauses a previously paused PayFast subscription.
   *
   * @param string $token
   *   The PayFast subscription token.
   *
   * @return bool
   *   TRUE if PayFast accepted the unpause request, FALSE otherwise.
   */
  public function unpauseSubscription(string $token): bool {
    if (empty($token)) {
      $this->logger->warning('PayFast subscription unpause skipped: token is empty.');
      return FALSE;
    }

    $response = $this->client->subscriptionAction(
      $this->merchantId,
      $token,
      'unpause',
      [],
      $this->passphrase,
      $this->testMode
    );

    return $this->parseApiResponse($response, 'unpause', $token);
  }

  /**
   * Returns whether the service is configured for sandbox (test) mode.
   *
   * @return bool
   *   TRUE if running against the PayFast sandbox.
   */
  public function isTestMode(): bool {
    return $this->testMode;
  }

  /**
   * Decodes and validates a raw PayFast API response string.
   *
   * Distinguishes between cURL network errors (non-JSON plain text returned
   * by the library on failure) and legitimate JSON API error responses, so
   * neither case leaks internal network details or PII into watchdog.
   *
   * @param string $response
   *   Raw cURL response body from the PayFast API.
   * @param string $action
   *   The action performed (used in log messages for context).
   * @param string $token
   *   The subscription token (used in log messages for context).
   *
   * @return bool
   *   TRUE if the response code is 200, FALSE on any error.
   */
  protected function parseApiResponse(string $response, string $action, string $token): bool {
    // The payfast-common library returns a plain-text cURL error string on
    // network failure. Detect this before attempting json_decode so the log
    // message is meaningful rather than "json_decode failed".
    if (!$this->looksLikeJson($response)) {
      $this->logger->error(
        'PayFast @action network error for token @token: @error',
        ['@action' => $action, '@token' => $token, '@error' => $this->truncate($response)]
      );
      return FALSE;
    }

    $decoded = json_decode($response, TRUE);
    if ($decoded === NULL) {
      $this->logger->error(
        'PayFast @action returned malformed JSON for token @token.',
        ['@action' => $action, '@token' => $token]
      );
      return FALSE;
    }

    $code = (int) ($decoded['code'] ?? 0);

    if ($code !== 200) {
      // Log only the status code and status string - not the full response body
      // which may contain billing dates, email addresses, or other PII.
      $status = $decoded['status'] ?? 'unknown';
      $this->logger->error(
        'PayFast @action failed for token @token: HTTP @code (@status).',
        [
          '@action' => $action,
          '@token' => $token,
          '@code' => $code,
          '@status' => $status,
        ]
      );
      return FALSE;
    }

    $this->logger->info(
      'PayFast @action succeeded for token @token.',
      ['@action' => $action, '@token' => $token]
    );

    return TRUE;
  }

  /**
   * Returns TRUE if the string looks like a JSON object.
   *
   * Used to distinguish a cURL plain-text error from a JSON API response
   * before calling json_decode.
   *
   * @param string $response
   *   The raw response string to check.
   *
   * @return bool
   *   TRUE if the string begins with '{', FALSE otherwise.
   */
  protected function looksLikeJson(string $response): bool {
    return str_starts_with(trim($response), '{');
  }

  /**
   * Truncates a string for safe inclusion in log messages.
   *
   * Prevents internal network error messages from leaking excessive detail
   * into watchdog. 150 characters is enough context to diagnose the error.
   *
   * @param string $value
   *   The string to truncate.
   *
   * @return string
   *   The string, truncated to 150 characters with ellipsis if needed.
   */
  protected function truncate(string $value): string {
    $trimmed = trim($value);
    return mb_strlen($trimmed) > 150 ? mb_substr($trimmed, 0, 150) . '…' : $trimmed;
  }

}
