<?php

namespace Drupal\glazed_builder\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\glazed_builder\Entity\GlazedBuilderProfile;
use Drupal\glazed_builder\GlazedBuilderProfileInterface;
use Drupal\glazed_builder\Service\GlazedBuilderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'glazed_builder_text' formatter.
 *
 * @FieldFormatter(
 *    id = "glazed_builder_text",
 *    label = @Translation("Glazed Builder"),
 *    field_types = {
 *       "text",
 *       "text_long",
 *       "text_with_summary"
 *    }
 * )
 */
class GlazedBuilderFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The configuration factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current path stack
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The request stack
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager service
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The CSRF token generator service
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The module handler service
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The glazed builder service
   *
   * @var \Drupal\glazed_builder\Service\GlazedBuilderServiceInterface
   */
  protected $glazedBuilderService;

  /**
   * Construct a GlazedBuilderFormatter object
   *
   * @param string $plugin_id
   *   The ID of the formatter
   * @param array $plugin_definition
   *   The formatter definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field
   * @param array $settings
   *   The settings of the formatter
   * @param string $label
   *   The position of the lable when the field is rendered
   * @param string $view_mode
   *   The current view mode
   * @param array $third_party_settings
   *   Any third-party settings
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   The current path stack
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service
   * @param \Drupal\glazed_builder\Service\GlazedBuilderServiceInterface $glazedBuilderService
   *   The glazed builder service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountProxyInterface $currentUser, ConfigFactoryInterface $configFactory, CurrentPathStack $currentPathStack, RequestStack $requestStack, LanguageManagerInterface $languageManager, CsrfTokenGenerator $csrfToken, ModuleHandlerInterface $moduleHandler, RendererInterface $renderer, GlazedBuilderServiceInterface $glazedBuilderService) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
    $this->currentPathStack = $currentPathStack;
    $this->requestStack = $requestStack;
    $this->languageManager = $languageManager;
    $this->csrfToken = $csrfToken;
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
    $this->glazedBuilderService = $glazedBuilderService;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('path.current'),
      $container->get('request_stack'),
      $container->get('language_manager'),
      $container->get('csrf_token'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('glazed_builder.service')
    );
  }

  public function settingsSummary() {
    $summary = [];

    $summary[] = t('The Glazed Builder drag and drop interface');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $config = $this->configFactory->get('glazed_builder.settings');

    $entity_type = $this->fieldDefinition->get('entity_type');
    $bundle = $this->fieldDefinition->get('bundle');
    $entity = $items->getEntity();
    $id = $entity->id();
    $field_name = $this->fieldDefinition->get('field_name');
    $entity_label = $entity->label();

    foreach ($items as $delta => $item) {
      $value = $item->value;
      $element[$delta] = [];
      if ($item->getLangcode()) {
        $langcode = $item->getLangcode();
      }
      else {
        $langcode = $this->languageManager->getCurrentLanguage()->getId();
      }
      $human_readable = base64_encode(Html::escape($field_name . ' on ' . str_replace('node', 'page', $entity_type) . ' \'' . $entity_label . '\''));
      $attrs = 'class="az-element az-container glazed" data-az-type="' . $entity_type . '|' . $bundle . '" data-az-name="' . $id . '|' . $field_name . '" data-az-human-readable="' . $human_readable . '" data-az-langcode="' . $langcode . '"';
      preg_match('/^\s*\<[\s\S]*\>\s*$/', $value, $html_format);

      if (empty($html_format)) {
        $output = '<div ' . $attrs . ' style="display:none"></div>';
        $mode = 'static';
      }
      else {
        $response = $this->glazedBuilderService->updateHtml($value);
        $output = $response['output'];
        $mode = $response['mode'];
        $libraries = $response['library'];
        $settings = $response['settings'];

        foreach ($libraries as $library) {
          $element[$delta]['#attached']['library'][] = $library;
        }

        foreach ($settings as $key => $setting) {
          $element[$delta]['#attached']['drupalSettings'][$key] = $setting;
        }
        $output = '<div ' . $attrs . ' data-az-mode="' . $mode . '">' . $output . '</div>';

        // Glazed Builder 1.1.0 Experimental feature: Process Text Format Filters for non-editors ~Jur 15/06/16
        // Don't run text format filters when editor is loaded because the editor would save all filter output into the db
        if (!$this->currentUser->hasPermission('edit via glazed builder') && $config->get('format_filters')) {
          $build = [
            '#type' => 'processed_text',
            '#text' => $output,
            '#format' => $item->__get('format'),
            '#filter_types_to_skip' => [],
            '#langcode' => $langcode,
          ];

          $output = $this->renderer->renderPlain($build);
        }
      }

      $element[$delta]['#markup'] = Markup::create($output);
      $element[$delta]['#id'] = $id. '|' . $field_name;
      $enable_editor = $this->currentUser->hasPermission('edit via glazed builder') && $entity->access('update', $this->currentUser);
      // Attach Glazed Builder assets
      $this->attachAssets($element[$delta], $value, $html_format, $enable_editor, $mode, $this->languageManager->getCurrentLanguage()->getId());
    }

    $element['#cache']['contexts'] = ['url'];
    $element['#cache']['tags'] = $config->getCacheTags();

    $profile = GlazedBuilderProfile::loadByRoles($this->currentUser->getRoles());
    if ($profile) {
      $profile_settings = \Drupal::service('glazed_builder.profile_handler')->buildSettings($profile);
      $element['#attached']['drupalSettings']['glazedBuilder']['profile'] = $profile_settings;
      $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'], $profile->getCacheTags());
    }

    return $element;
  }

  /**
   * Attaches CSS and JS assets to field render array.
   *
   * @param array $element
   *   A renderable array for the $items, as an array of child elements keyed by numeric indexes starting from 0.
   *   @see https://api.drupal.org/api/drupal/modules!field!field.api.php/function/hook_field_formatter_view/7.x
   * @param string $content
   *   Raw field value
   * @param string $html_format
   *   Valid HTML field value
   * @param bool $enable_editor
   *   When FALSE only frontend rendering assets will be attached. When TRUE the full
   *   drag and drop editor will be attached.
   * @param string $mode
   *   The mode.
   * @param string $glazed_lang
   *   2 letter language code
   */
  function attachAssets(&$element, $content, $html_format, $enable_editor, $mode, $glazed_lang) {
    $base_url = $this->getBaseUrl();
    $config = $this->configFactory->get('glazed_builder.settings');

    $settings = [];
    $settings['currentPath'] = $this->currentPathStack->getPath();

    if ($enable_editor) {
      $settings['glazedEditor'] = TRUE;
    }

    $url = Url::fromRoute('glazed_builder.ajax_callback');
    $token = $this->csrfToken->get($url->getInternalPath());
    $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);
    $settings['glazedAjaxUrl'] = $url->toSTring();
    $settings['glazedLanguage'] = $glazed_lang;
    $settings['glazedBaseUrl'] = $base_url . base_path() . '/' . $this->getPath('module', 'glazed_builder') . '/glazed_builder/';

    $element['#attached']['library'][] = 'glazed_builder/core';
    if ($mode == 'dynamic') {
      if ($config->get('development')) {
        $element['#attached']['library'][] = 'glazed_builder/editor.frontend_dev';
      }
      else {
        $element['#attached']['library'][] = 'glazed_builder/editor.frontend';
      }
    }

    if ($config->get('development')) {
      $settings['glazedDevelopment'] = TRUE;
    }

    if ($config->get('bootstrap') == 1) {
      $element['#attached']['library'][] = 'glazed_builder/bootstrap_full';
    }
    elseif ($config->get('bootstrap') == 2) {
      $element['#attached']['library'][] = 'glazed_builder/bootstrap_light';
    }

    if ($enable_editor) {
      $this->glazedBuilderService->editorAttach($element, $settings);
    }
    else {
      if (!empty($element['#id'])) {
        $settings['disallowContainers'] = [$element['#id']];
      }
    }

    $settings['mediaBrowser'] = $config->get('media_browser') ;
    if ($settings['mediaBrowser'] != '') {
      // Attach Entity Browser Configurations and libraries
      $element['#attached']['drupalSettings']['entity_browser'] = [
        'glazedBuilderSingle' => [
          'cardinality' => 1,
          'selection_mode' => 'selection_append',
          'selector' => false,
        ],
        'glazedBuilderMulti' => [
          'cardinality' => -1,
          'selection_mode' => 'selection_append',
          'selector' => false,
        ],
      ];
      $element['#attached']['library'][] = 'entity_browser/common';
    }

    if ($this->moduleHandler->moduleExists('color')) {
      if ($palette = $this->colorGetPalette()) {
        $settings['palette'] = array_slice($palette, 0, 10);
      }
    }

    $element['#attached']['drupalSettings']['glazedBuilder'] = $settings;
  }

  /**
   * Wrapper for drupal_get_path()
   *
   * @param string $type
   *   The type of path to return, module or theme
   * @param string $name
   *   The name of the theme/module to look up
   *
   * @return string
   *   The path to the given module/theme
   *
   * @see drupal_get_path()
   */
  private function getPath($type, $name) {
    $paths = &drupal_static(__CLASS__ . '::' . __FUNCTION__, []);
    $key = $type . '::' . $name;
    if (!isset($paths[$key])) {
      $paths[$key] = drupal_get_path($type, $name);
    }

    return $paths[$key];
  }

  /**
   * Get the base URL of the current request
   */
  private function getBaseUrl() {
    return $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
  }

  /**
   * Get the theme color pallette for the current theme
   */
  private function colorGetPalette() {
    $default_theme = $this->configFactory->get('system.theme')->get('default');

    return color_get_palette($default_theme);
  }

}
