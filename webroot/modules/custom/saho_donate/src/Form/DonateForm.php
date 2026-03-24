<?php

namespace Drupal\saho_donate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Donation form - builds a PayFast POST payload and redirects.
 */
class DonateForm extends FormBase {

  /**
   * {@inheritdoc}
   *
   * FormBase declares $requestStack; inject via setRequestStack().
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->setRequestStack($container->get('request_stack'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'saho_donate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'saho-donate-form';

    // ── Amount picker ──────────────────────────────────────────────────────
    $form['amount_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose an amount (ZAR)'),
      '#attributes' => ['class' => ['saho-donate-amounts']],
    ];

    $form['amount_group']['preset'] = [
      '#type' => 'radios',
      '#title' => $this->t('Preset amount'),
      '#title_display' => 'invisible',
      '#options' => [
        50 => $this->t('R&nbsp;50'),
        100 => $this->t('R&nbsp;100'),
        200 => $this->t('R&nbsp;200'),
        500 => $this->t('R&nbsp;500'),
        0 => $this->t('Other amount'),
      ],
      '#default_value' => 100,
      '#attributes' => ['class' => ['saho-donate-presets']],
    ];

    $form['amount_group']['custom_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Custom amount (R)'),
      '#min' => 10,
      '#step' => 1,
      '#placeholder' => $this->t('Enter amount'),
      '#attributes' => [
        'class' => ['saho-donate-custom-amount'],
        'aria-label' => $this->t('Custom donation amount in Rands'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="preset"]' => ['value' => '0'],
        ],
        'required' => [
          ':input[name="preset"]' => ['value' => '0'],
        ],
      ],
    ];

    // ── Donor details ──────────────────────────────────────────────────────
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email address'),
      '#required' => TRUE,
      '#placeholder' => $this->t('you@example.com'),
      '#attributes' => ['class' => ['saho-donate-email']],
      '#description' => $this->t('We will send a confirmation to this address.'),
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name (optional)'),
      '#placeholder' => $this->t('First name'),
      '#maxlength' => 100,
      '#attributes' => ['class' => ['saho-donate-name']],
    ];

    // ── Submit ─────────────────────────────────────────────────────────────
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Donate Now'),
      '#attributes' => ['class' => ['btn', 'btn-primary', 'saho-donate-submit']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $preset = (int) $form_state->getValue('preset');
    $custom = $form_state->getValue('custom_amount');

    if ($preset === 0 && (empty($custom) || (float) $custom < 10)) {
      $form_state->setErrorByName(
        'custom_amount',
        $this->t('Please enter a minimum donation of R 10.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $preset = (int) $form_state->getValue('preset');
    $custom = (float) $form_state->getValue('custom_amount');
    $amount = $preset > 0 ? (float) $preset : $custom;
    $email  = $form_state->getValue('email');
    $name   = trim((string) ($form_state->getValue('name') ?? ''));

    $merchant_id  = Settings::get('payfast_merchant_id', '');
    $merchant_key = Settings::get('payfast_merchant_key', '');
    $passphrase   = Settings::get('payfast_passphrase', '');

    $request  = $this->requestStack->getCurrentRequest();
    $base_url = $request->getSchemeAndHttpHost();

    // Build the data array in the order PayFast expects.
    $data = array_filter([
      'merchant_id'      => $merchant_id,
      'merchant_key'     => $merchant_key,
      'return_url'       => $base_url . '/donate/thank-you',
      'cancel_url'       => $base_url . '/donate/cancelled',
      'notify_url'       => $base_url . '/donate/notify',
      'name_first'       => $name,
      'email_address'    => $email,
      'amount'           => number_format($amount, 2, '.', ''),
      'item_name'        => 'Donation to South African History Online',
    ], static fn($v): bool => $v !== '' && $v !== NULL);

    $data['signature'] = $this->generateSignature($data, $passphrase);

    $request->getSession()->set('saho_payfast_payload', [
      'url'  => 'https://www.payfast.co.za/eng/process',
      'data' => $data,
    ]);

    $form_state->setRedirect('saho_donate.payfast_redirect');
  }

  /**
   * Generates the PayFast MD5 signature string.
   *
   * @param array $data
   *   Ordered POST fields (must NOT include 'signature').
   * @param string $passphrase
   *   Optional passphrase from PayFast account settings.
   *
   * @return string
   *   MD5 signature.
   */
  protected function generateSignature(array $data, string $passphrase = ''): string {
    $param_string = http_build_query($data);
    if ($passphrase !== '') {
      $param_string .= '&passphrase=' . urlencode(trim($passphrase));
    }
    return md5($param_string);
  }

}
