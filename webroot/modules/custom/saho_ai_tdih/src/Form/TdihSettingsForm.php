<?php

namespace Drupal\saho_ai_tdih\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure SAHO AI TDIH settings.
 */
class TdihSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saho_ai_tdih_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['saho_ai_tdih.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saho_ai_tdih.settings');

    $form['ai_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AI Provider Settings'),
    ];

    $form['ai_settings']['ai_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('AI Provider'),
      '#options' => [
        'anthropic' => $this->t('Anthropic (Claude)'),
      ],
      '#default_value' => $config->get('ai_provider') ?? 'anthropic',
      '#description' => $this->t('Select the AI provider to use. Configure API keys at /admin/config/ai/providers/anthropic'),
    ];

    $form['ai_settings']['ai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Claude Model'),
      '#options' => [
        'claude-sonnet-4-20250514' => $this->t('Claude Sonnet 4 (Recommended)'),
        'claude-3-5-sonnet-20241022' => $this->t('Claude 3.5 Sonnet'),
        'claude-3-haiku-20240307' => $this->t('Claude 3 Haiku (Faster, cheaper)'),
      ],
      '#default_value' => $config->get('ai_model') ?? 'claude-sonnet-4-20250514',
      '#description' => $this->t('Select the Claude model to use for research.'),
    ];

    $form['processing_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Processing Settings'),
    ];

    $form['processing_settings']['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch Size'),
      '#min' => 1,
      '#max' => 100,
      '#default_value' => $config->get('batch_size') ?? 10,
      '#description' => $this->t('Number of events to process in each batch.'),
    ];

    $form['processing_settings']['delay_between_requests'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay Between Requests (seconds)'),
      '#min' => 0,
      '#max' => 60,
      '#default_value' => $config->get('delay_between_requests') ?? 2,
      '#description' => $this->t('Delay between API requests to avoid rate limiting.'),
    ];

    $form['processing_settings']['auto_apply_verified'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-apply verified dates'),
      '#default_value' => $config->get('auto_apply_verified') ?? FALSE,
      '#description' => $this->t('Automatically apply dates that are marked as verified without manual review.'),
    ];

    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Setup Instructions'),
      '#open' => FALSE,
    ];

    $form['help']['content'] = [
      '#markup' => '<ol>
        <li>Go to <a href="/admin/config/system/keys/add">Admin > Config > System > Keys</a> to add your Anthropic API key</li>
        <li>Go to <a href="/admin/config/ai/providers/anthropic">Admin > Config > AI > Providers > Anthropic</a> to configure the provider</li>
        <li>Return here to configure processing settings</li>
        <li>Visit the <a href="/admin/content/tdih-ai">TDIH AI Dashboard</a> to start processing</li>
      </ol>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('saho_ai_tdih.settings')
      ->set('ai_provider', $form_state->getValue('ai_provider'))
      ->set('ai_model', $form_state->getValue('ai_model'))
      ->set('batch_size', $form_state->getValue('batch_size'))
      ->set('delay_between_requests', $form_state->getValue('delay_between_requests'))
      ->set('auto_apply_verified', $form_state->getValue('auto_apply_verified'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
