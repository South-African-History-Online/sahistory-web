<?php

namespace Drupal\saho_performance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for SAHO Performance settings.
 */
class PerformanceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['saho_performance.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saho_performance_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saho_performance.settings');

    $form['css_optimization'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CSS Optimization'),
    ];

    $form['css_optimization']['enable_css_removal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable unused CSS removal'),
      '#description' => $this->t('Remove CSS files that are not needed on specific pages.'),
      '#default_value' => $config->get('enable_css_removal') ?? TRUE,
    ];

    $form['css_optimization']['aggressive_css_minify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Aggressive CSS minification'),
      '#description' => $this->t('Apply more aggressive CSS minification techniques.'),
      '#default_value' => $config->get('aggressive_css_minify') ?? TRUE,
    ];

    $form['css_optimization']['critical_css_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Critical CSS size limit (bytes)'),
      '#description' => $this->t('Maximum size for critical CSS to be inlined.'),
      '#default_value' => $config->get('critical_css_size') ?? 14000,
      '#min' => 1000,
      '#max' => 50000,
    ];

    $form['performance_monitoring'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Performance Monitoring'),
    ];

    $form['performance_monitoring']['enable_monitoring'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable performance monitoring'),
      '#description' => $this->t('Monitor page load times and Core Web Vitals.'),
      '#default_value' => $config->get('enable_monitoring') ?? TRUE,
    ];

    $form['cache_optimization'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cache Optimization'),
    ];

    $form['cache_optimization']['enable_preload_hints'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable preload hints'),
      '#description' => $this->t('Add preload hints for critical resources.'),
      '#default_value' => $config->get('enable_preload_hints') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('saho_performance.settings')
      ->set('enable_css_removal', $form_state->getValue('enable_css_removal'))
      ->set('aggressive_css_minify', $form_state->getValue('aggressive_css_minify'))
      ->set('critical_css_size', $form_state->getValue('critical_css_size'))
      ->set('enable_monitoring', $form_state->getValue('enable_monitoring'))
      ->set('enable_preload_hints', $form_state->getValue('enable_preload_hints'))
      ->save();

    // Clear CSS cache to apply changes.
    \Drupal::service('asset.css.collection_optimizer')->deleteAll();
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
