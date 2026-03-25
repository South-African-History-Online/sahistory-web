<?php

namespace Drupal\saho_donate\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\saho_donate\Form\DonateForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the SAHO donation flow.
 */
class DonateController extends ControllerBase {

  /**
   * Constructs a DonateController.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block plugin manager.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected BlockManagerInterface $blockManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('request_stack'),
      $container->get('plugin.manager.block'),
    );
  }

  /**
   * Renders the /donate page: PayFast form + alternative pathways below.
   *
   * @return array
   *   A render array.
   */
  public function page(): array {
    $pathways_block = [];
    if ($this->blockManager->hasDefinition('saho_donate_pathways')) {
      $block = $this->blockManager->createInstance('saho_donate_pathways', []);
      $pathways_block = $block->build();
    }

    return [
      '#theme' => 'saho_donate_page',
      '#form' => $this->formBuilder()->getForm(DonateForm::class),
      '#pathways_block' => $pathways_block,
      '#attached' => [
        'library' => ['saho_donate/donate', 'saho_donate/donate-page'],
      ],
    ];
  }

  /**
   * Renders an auto-submitting hidden form that POSTs to PayFast.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   Render array or redirect if no payload is found.
   */
  public function payfastRedirect(): array|Response {
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $payload = $session->get('saho_payfast_payload');

    if (empty($payload)) {
      return $this->redirect('saho_donate.form');
    }

    $session->remove('saho_payfast_payload');

    return [
      '#theme' => 'saho_donate_redirect',
      '#payfast_url' => $payload['url'],
      '#fields' => $payload['data'],
      '#attached' => [
        'library' => ['saho_donate/donate'],
      ],
    ];
  }

  /**
   * Thank-you page shown after successful PayFast payment.
   *
   * @return array
   *   A render array.
   */
  public function thankYou(): array {
    return [
      '#theme' => 'saho_donate_thankyou',
      '#attached' => [
        'library' => ['saho_donate/donate'],
      ],
    ];
  }

  /**
   * Cancelled page shown when donor cancels at PayFast.
   *
   * @return array
   *   A render array.
   */
  public function cancelled(): array {
    return [
      '#theme' => 'saho_donate_cancelled',
      '#attached' => [
        'library' => ['saho_donate/donate'],
      ],
    ];
  }

  /**
   * Handles PayFast ITN (Instant Transaction Notification) POST.
   *
   * Validates the source IP and MD5 signature before processing.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   200 OK on valid signature, 400/403 on failure.
   */
  public function notify(): Response {
    $request = $this->requestStack->getCurrentRequest();

    // Validate source IP against PayFast allowlist.
    $client_ip = $request->getClientIp() ?? '';
    if (!$this->isValidPayfastIp($client_ip)) {
      $this->getLogger('saho_donate')->warning(
        'PayFast ITN: request from disallowed IP @ip rejected.',
        ['@ip' => $client_ip]
      );
      return new Response('Forbidden', 403);
    }

    $data = $request->request->all();

    // Require passphrase to be configured — reject all ITNs otherwise.
    $passphrase = Settings::get('payfast_passphrase', '');
    if ($passphrase === '') {
      $this->getLogger('saho_donate')->error(
        'PayFast ITN: payfast_passphrase is not set in settings.php — all ITN notifications rejected.'
      );
      return new Response('Configuration error', 500);
    }

    if (!$this->validateItnSignature($data, $passphrase)) {
      $this->getLogger('saho_donate')->warning(
        'PayFast ITN: signature validation failed for payment_id=@id',
        ['@id' => $data['pf_payment_id'] ?? 'unknown']
      );
      return new Response('Invalid signature', 400);
    }

    $this->getLogger('saho_donate')->notice(
      'PayFast ITN: status=@status amount=@amount email=@email payment_id=@id',
      [
        '@status' => $data['payment_status'] ?? 'unknown',
        '@amount' => $data['amount_gross'] ?? '0',
        '@email' => $data['email_address'] ?? '',
        '@id' => $data['pf_payment_id'] ?? '',
      ]
    );

    return new Response('OK', 200);
  }

  /**
   * Validates a PayFast ITN signature.
   *
   * Rebuilds the MD5 signature from the posted fields (excluding 'signature')
   * and compares it using a timing-safe comparison.
   *
   * @param array $data
   *   All POST fields received from PayFast.
   * @param string $passphrase
   *   The PayFast passphrase configured in settings (must not be empty).
   *
   * @return bool
   *   TRUE if the signature is valid.
   */
  private function validateItnSignature(array $data, string $passphrase): bool {
    $received = $data['signature'] ?? '';
    if ($received === '') {
      return FALSE;
    }

    // Remove signature from the data before rebuilding.
    unset($data['signature']);

    $param_string = http_build_query($data);
    $param_string .= '&passphrase=' . urlencode(trim($passphrase));

    return hash_equals(md5($param_string), $received);
  }

  /**
   * Checks whether an IP address is in the PayFast allowlist.
   *
   * The allowlist is configurable via settings.php:
   *   $settings['payfast_valid_ips'] = ['1.2.3.4', '10.0.0.0/8'];
   * Defaults to PayFast's published production and sandbox CIDR ranges.
   *
   * @param string $ip
   *   The client IP address to validate.
   *
   * @return bool
   *   TRUE if the IP is in the allowlist.
   */
  private function isValidPayfastIp(string $ip): bool {
    $allowed = Settings::get('payfast_valid_ips', [
      // PayFast production range.
      '197.97.145.144/28',
      // PayFast sandbox range.
      '196.33.227.224/27',
      // Legacy PayFast IP.
      '41.74.179.194',
    ]);

    foreach ($allowed as $range) {
      if ($this->ipInRange($ip, $range)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks whether an IP falls within a CIDR range or equals a single IP.
   *
   * @param string $ip
   *   The IP address to check.
   * @param string $range
   *   A CIDR range (e.g. '197.97.145.144/28') or single IP.
   *
   * @return bool
   *   TRUE if the IP is within the range.
   */
  private function ipInRange(string $ip, string $range): bool {
    if (strpos($range, '/') === FALSE) {
      return $ip === $range;
    }
    [$subnet, $bits] = explode('/', $range, 2);
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet);
    if ($ip_long === FALSE || $subnet_long === FALSE) {
      return FALSE;
    }
    $mask = ~((1 << (32 - (int) $bits)) - 1);
    return ($ip_long & $mask) === ($subnet_long & $mask);
  }

}
