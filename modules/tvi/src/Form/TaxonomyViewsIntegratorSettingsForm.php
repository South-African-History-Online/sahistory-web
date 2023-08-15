<?php

namespace Drupal\tvi\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * TVI global settings form.
 */
class TaxonomyViewsIntegratorSettingsForm extends ConfigFormBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * TaxonomyViewsIntegratorSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tvi_global_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tvi.global_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('tvi.global_settings');
    $views = Views::getEnabledViews();

    $view_options = ['' => ' - Select a View -'];
    $display_options = ['' => ' - Select a View Display -'];
    $default_display = '';

    foreach ($views as $view) {
      $view_options[$view->id()] = $view->label();
    }

    if (isset($values['view'])) {
      $display_options += $this->getViewDisplayOptions($values['view']);
    }
    elseif ($config !== NULL) {
      $view = $config->get('view');
      $view_display = $config->get('view_display');

      if (isset($view)) {
        $display_options += $this->getViewDisplayOptions($view);
      }

      if (isset($view_display)) {
        $default_display = $view_display;
      }
    }

    $form['#prefix'] = '<div id="tvi-settings-wrapper">';
    $form['#suffix'] = '</div>';

    $form['tvi'] = [
      '#type' => 'details',
      '#title' => $this->t('Global settings'),
      '#open' => TRUE,
      '#description' => $this->t('By enabling taxonomy views integration here, it will apply to all vocabularies and their terms by default.'),
    ];

    $form['tvi']['disable_default_view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Don't display a view by default."),
      '#description' => $this->t('Checking this field will enable the use of the selected view when displaying this taxonomy page.'),
      '#default_value' => $config->get('disable_default_view'),
    ];

    $form['tvi']['enable_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use global view override.'),
      '#description' => $this->t('Checking this field will enable the use of the selected view when displaying this taxonomy page.'),
      '#default_value' => $config->get('enable_override'),
      '#states' => [
        'visible' => [
          ':input[name="disable_default_view"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['tvi']['view'] = [
      '#type' => 'select',
      '#title' => 'Using the view',
      '#description' => $this->t('The default view that you want to use for all vocabularies and terms.'),
      '#default_value' => $config->get('view'),
      '#options' => $view_options,
      '#states' => [
        'visible' => [
          ':input[name="enable_override"]' => ['checked' => TRUE],
          ':input[name="disable_default_view"]' => ['checked' => FALSE],
        ],
      ],
      '#ajax' => [
        'callback' => '::loadDisplayOptions',
        'event' => 'change',
        'wrapper' => 'tvi-settings-wrapper',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['tvi']['view_display'] = [
      '#type' => 'select',
      '#title' => 'With this view display',
      '#description' => $this->t('The view display that you want to use from the selected view.'),
      '#default_value' => $default_display,
      '#options' => $display_options,
      '#states' => [
        'visible' => [
          ':input[name="enable_override"]' => ['checked' => TRUE],
          ':input[name="disable_default_view"]' => ['checked' => FALSE],
        ],
      ],
      '#prefix' => '<div id="tvi-view-display">',
      '#suffix' => '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['enable_override']) {
      if (!mb_strlen($values['view'])) {
        $form_state->setError($form['tvi']['view'], $this->t('To override the term presentation, you must specify a view.'));
      }

      if (!mb_strlen($values['view_display'])) {
        $form_state->setError($form['tvi']['view_display'], $this->t('To override the term presentation, you must specify a view display.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tvi.global_settings')
      ->set('disable_default_view', $form_state->getValue('disable_default_view'))
      ->set('enable_override', $form_state->getValue('enable_override'))
      ->set('view', $form_state->getValue('view'))
      ->set('view_display', $form_state->getValue('view_display'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return an array of displays for a given view id.
   *
   * @param string $view_id
   *   View id to populate options from.
   *
   * @return array
   *   Drupal render array options values.
   */
  protected function getViewDisplayOptions(string $view_id) {
    $display_options = [];
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);

    if ($view) {
      foreach ($view->get('display') as $display) {
        $display_options[$display['id']] = $display['display_title'] . ' (' . $display['display_plugin'] . ')';
      }
    }

    return $display_options;
  }

  /**
   * Ajax callback - null the value and return the piece of the form.
   *
   * The value gets nulled because we cannot overwrite #default_value in an ajax
   * callback.
   *
   * @param array $form
   *   Ajax form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   *
   * @return array
   *   Form render array response.
   *
   * @see https://www.drupal.org/node/1446510
   * @see https://www.drupal.org/node/752056
   */
  public function loadDisplayOptions(array &$form, FormStateInterface $form_state) {
    $form['tvi']['view_display']['#value'] = '';
    $form_state->setRebuild();
    return $form;
  }

}
