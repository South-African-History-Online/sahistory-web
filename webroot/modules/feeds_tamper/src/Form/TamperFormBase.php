<?php

namespace Drupal\feeds_tamper\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class for add/edit tamper forms.
 *
 * @package Drupal\feeds_tamper\Form
 */
abstract class TamperFormBase extends FormBase {

  use TamperFormTrait;

  // Form fields.
  const VAR_TAMPER_ID = 'tamper_id';
  const VAR_TAMPER_LABEL = 'label';
  const VAR_PLUGIN_CONFIGURATION = 'plugin_configuration';
  const VAR_WEIGHT = 'weight';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var self $form */
    $form = parent::create($container);
    $form->setTamperManager($container->get('plugin.manager.tamper'));
    $form->setTamperMetaManager($container->get('feeds_tamper.feed_type_tamper_manager'));
    return $form;
  }

  /**
   * Page title callback.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   Translated string to use as the title.
   */
  public function tamperTitle(RouteMatchInterface $route_match) {
    /** @var \Drupal\feeds\Entity\FeedType $feed_type */
    $feed_type = $route_match->getParameter('feeds_feed_type');
    $source_field = $route_match->getParameter('source_field');
    $tamper_uuid = $route_match->getParameter('tamper_uuid');

    if ($source_field) {
      return $this->t('Add a tamper plugin to @label : @source', [
        '@label' => $feed_type->label(),
        '@source' => $source_field,
      ]);
    }
    elseif ($tamper_uuid) {
      $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($feed_type);
      $tamper = $tamper_meta->getTamper($tamper_uuid);
      return $this->t('Edit @label', [
        '@label' => $tamper->getPluginDefinition()['label'],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form[self::VAR_TAMPER_ID] = [
      '#type' => 'select',
      '#title' => $this->t('The plugin to add'),
      '#options' => $this->getPluginOptions(),
      '#required' => TRUE,
      '#default_value' => $this->plugin ? $this->plugin->getPluginDefinition()['id'] : NULL,
      '#ajax' => [
        'callback' => '::getPluginForm',
        'wrapper' => 'plugin-config',
      ],
    ];
    $form[self::VAR_PLUGIN_CONFIGURATION] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => ['id' => ['plugin-config']],
    ];

    if ($this->plugin) {
      $form[self::VAR_PLUGIN_CONFIGURATION][self::VAR_TAMPER_LABEL] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#maxlength' => '255',
        '#description' => $this->t('A useful description of what this plugin is doing.'),
        '#required' => TRUE,
        '#default_value' => $this->plugin->getSetting(self::VAR_TAMPER_LABEL) ? $this->plugin->getSetting(self::VAR_TAMPER_LABEL) : $this->plugin->getPluginDefinition()['label'],
      ];
      $form[self::VAR_PLUGIN_CONFIGURATION]['description'] = [
        '#markup' => $this->plugin->getPluginDefinition()['description'],
      ];

      $subform_state = SubformState::createForSubform($form[self::VAR_PLUGIN_CONFIGURATION], $form, $form_state);
      $form[self::VAR_PLUGIN_CONFIGURATION] = $this->plugin->buildConfigurationForm($form[self::VAR_PLUGIN_CONFIGURATION], $subform_state);
    }

    $form[self::VAR_WEIGHT] = [
      '#type' => 'hidden',
      '#value' => $this->getWeight(),
    ];

    $cancel_url = Url::fromRoute('entity.feeds_feed_type.tamper', [
      'feeds_feed_type' => $this->feedsFeedType->id(),
    ]);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $cancel_url,
    ];
    return $form;
  }

  /**
   * Ajax callback.
   *
   * Returns the plugin configuration form from an ajax request.
   *
   * @param array $form
   *   Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   *
   * @return array
   *   Plugin form.
   */
  public function getPluginForm(array $form, FormStateInterface $form_state) {
    // Update label when selecting an other plugin.
    if (!$this->plugin || !$this->plugin->getSetting(self::VAR_TAMPER_LABEL)) {
      $form[self::VAR_PLUGIN_CONFIGURATION][self::VAR_TAMPER_LABEL]['#value'] = $form[self::VAR_PLUGIN_CONFIGURATION][self::VAR_TAMPER_LABEL]['#default_value'];
    }

    return $form[self::VAR_PLUGIN_CONFIGURATION];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($this->plugin)) {
      $subform_state = SubformState::createForSubform($form[self::VAR_PLUGIN_CONFIGURATION], $form, $form_state);
      $this->plugin->validateConfigurationForm($form[self::VAR_PLUGIN_CONFIGURATION], $subform_state);
    }
  }

  /**
   * Get the tamper plugin options.
   *
   * @return array
   *   List of tamper plugin groups, keyed by group, where the value is another
   *   array of plugin labels keyed by plugin id.
   */
  protected function getPluginOptions() {
    // @todo Move this logic to the tamper manager interface?
    $plugin_options = array_map(function ($grouped_plugins) {
      $group_options = [];
      foreach ($grouped_plugins as $id => $plugin_definition) {
        $group_options[$id] = $plugin_definition['label'];
      }
      return $group_options;
    }, $this->tamperManager->getGroupedDefinitions());

    return $plugin_options;
  }

  /**
   * Prepares a configuration array.
   *
   * @param string $source
   *   The source.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The configuration array.
   */
  protected function prepareConfig($source, FormStateInterface $form_state) {
    $config = [
      'plugin' => $this->plugin->getPluginId(),
      'source' => $source,
      'weight' => $form_state->getValue(self::VAR_WEIGHT),
      'label' => $form_state->getValue([self::VAR_PLUGIN_CONFIGURATION, self::VAR_TAMPER_LABEL]),
    ];

    $plugin_config = $form_state->getValue(self::VAR_PLUGIN_CONFIGURATION);
    if ($plugin_config) {
      $config += $plugin_config;
    }

    return $config;
  }

  /**
   * Gets the weight to use for the plugin.
   *
   * @return int
   *   The plugin's weight.
   */
  protected function getWeight() {
    $request = $this->getRequest();
    if ($request->query->has(self::VAR_WEIGHT)) {
      return (int) $request->query->get(self::VAR_WEIGHT);
    }
    if ($this->plugin) {
      return (int) $this->plugin->getSetting(self::VAR_WEIGHT);
    }
    return 0;
  }

}
