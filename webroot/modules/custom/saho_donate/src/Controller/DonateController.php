<?php

namespace Drupal\saho_donate\Controller;

use Drupal\Core\Controller\ControllerBase;
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
   */
  public function __construct(
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('request_stack'),
    );
  }

  /**
   * Renders the donation page with the form.
   *
   * @return array
   *   A render array.
   */
  public function page(): array {
    return [
      '#theme' => 'saho_donate_page',
      '#form' => $this->formBuilder()->getForm(DonateForm::class),
      '#attached' => [
        'library' => ['saho_donate/donate'],
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
      '#markup' => $this->t(
        '<div class="saho-donate-message container py-5 text-center"><h2>Donation Cancelled</h2><p>No payment was taken. You can <a href="/donate">try again</a> or <a href="/">return to SAHO</a>.</p></div>'
      ),
    ];
  }

  /**
   * Handles PayFast ITN (Instant Transaction Notification) POST.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   200 OK response.
   */
  public function notify(): Response {
    $request = $this->requestStack->getCurrentRequest();
    $data = $request->request->all();

    $this->getLogger('saho_donate')->notice(
      'PayFast ITN: status=@status amount=@amount email=@email',
      [
        '@status' => $data['payment_status'] ?? 'unknown',
        '@amount' => $data['amount_gross'] ?? '0',
        '@email' => $data['email_address'] ?? '',
      ]
    );

    return new Response('OK', 200);
  }

}
