<?php

namespace Drupal\glazed_builder\Service;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\glazed_builder\Service\Handler\BlockHandlerInterface;
use Drupal\glazed_builder\Service\Handler\ViewHandlerInterface;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class GlazedBuilderService implements GlazedBuilderServiceInterface {

  use StringTranslationTrait;

  /**
   * The request stack
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The glazed builder configuration
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Drupal file system
   *
   * @var Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The current user
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The module handler service
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The glazed builder block handler service
   *
   * @var \Drupal\glazed_builder\Service\Handler\BlockHandlerInterface
   */
  protected $glazedBlockHandler;

  /**
   * The glazed builder view handler service
   *
   * @var \Drupal\glazed_builder\Service\Handler\ViewHandlerInterface
   */
  protected $glazedViewHandler;

  /**
   * The cache service
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The entity manager service
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The theme handler service
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The language manager service
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The block manager service
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The CSRF token generator service
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs a GlazedBuilderService object
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The Drupal file system
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user
   * @ param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service
   * @param \Drupal\glazed_builder\Service\Handler\BlockHandlerInterface $glazedBlockHandler
   *   The glazed builder block handler service
   * @param \Drupal\glazed_builder\Service\Handler\ViewHandlerInterface $glazedViewHandler
   *   The glazed builder view handler service
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache service;
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager service
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service
   */
  public function __construct(RequestStack $requestStack, ConfigFactoryInterface $configFactory, FileSystemInterface $fileSystem, AccountProxyInterface $currentUser, ModuleHandlerInterface $moduleHandler, BlockHandlerInterface $glazedBlockHandler, ViewHandlerInterface $glazedViewHandler, CacheBackendInterface $cacheBackend, EntityManagerInterface $entityManager, ThemeHandlerInterface $themeHandler, LanguageManagerInterface $languageManager, BlockManagerInterface $blockManager, CsrfTokenGenerator $csrfToken) {
    $this->requestStack = $requestStack;
    $this->configFactory = $configFactory;
    $this->fileSystem = $fileSystem;
    $this->currentUser = $currentUser;
    $this->moduleHandler = $moduleHandler;
    $this->glazedBlockHandler = $glazedBlockHandler;
    $this->glazedViewHandler = $glazedViewHandler;
    $this->cacheBackend = $cacheBackend;
    $this->entityManager = $entityManager;
    $this->themeHandler = $themeHandler;
    $this->languageManager = $languageManager;
    $this->blockManager = $blockManager;
    $this->csrfToken = $csrfToken;
  }

  /**
   * {@inheritdoc}
   */
  public function insertBaseTokens($content) {
    // Get url-safe path, replace backslashes from windows paths
    $filesDirectoryPath = str_replace('\\', '/', $this->getFilesDirectoryPath());
    $modulePath = str_replace('\\', '/', $this->getModulePath());
    $replacements = [
      $this->getBaseUrl() => '-base-url-',
      $filesDirectoryPath => '-files-directory-',
      $modulePath => '-module-directory-',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $content);
  }

  /**
   * {@inheritdoc}
   */
  public function replaceBaseTokens(&$content) {
    // Get url-safe path, replace backslashes from windows paths
    $filesDirectoryPath = str_replace('\\', '/', $this->getFilesDirectoryPath());
    $modulePath = str_replace('\\', '/', $this->getModulePath());
    $replacements = [
      '-base-url-' => $this->getBaseUrl(),
      '-files-directory-' => $filesDirectoryPath,
      '-module-directory-' => $modulePath,
    ];

    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
  }

  /**
   * {@inheritdoc}
   */
  public function updateHtml($dataString) {
    $response = [
      'output' => $dataString,
      'library' => [],
      'settings' => [],
      'mode' => 'static',
    ];

    $this->replaceBaseTokens($response['output']);
    $this->parseContentForScripts($response);
    $this->parseForContent($response);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function editorAttach(&$element, &$settings) {

    $libraries = [];
    $config = $this->configFactory->get('glazed_builder.settings');
    $settings['glazedTemplateElements'] = $this->getTemplateElements($libraries);
    if (count($libraries)) {
      foreach ($libraries as $library) {
        $element['#attached']['library'][]  = $library;
      }
    }

    $settings['cmsElementNames'] = $this->getCmsElementNames();

    // Creating a list of views with additional settings.
    $settings['cmsElementViewsSettings'] = $this->getCmsElementSettings();

    // Creating a list of views tags.
    $settings['viewsTags'] = $this->getCmsViewsTags();

    // Creating a list of buttons style.
    $settings['buttonStyles'] = $this->getButtonStyles();

    // Set the current language
    $settings['language'] = $this->languageManager->getCurrentLanguage()->getId();

    // Set AJAX file upload callback URL
    $url = Url::fromRoute('glazed_builder.ajax_file_upload_callback');
    $token = $this->csrfToken->get($url->getInternalPath());
    $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);
    $settings['fileUploadUrl'] = $url->toString();

    $default_scheme = \Drupal::config('system.file')->get('default_scheme');
    $settings['publicFilesFolder'] = file_create_url($default_scheme . '://');
    $settings['fileUploadFolder'] = file_create_url($default_scheme . '://glazed_builder_images');

    if ($cke_stylesset = $config->get('cke_stylesset')) {
      $settings['cke_stylesset'] = $this->ckeParseStyles($cke_stylesset);
    }
    if ($cke_fonts = $config->get('cke_fonts')) {
      $settings['cke_fonts'] = str_replace(';;', ';', preg_replace("/[\n\r]/",";",$cke_fonts));
    }


    $element['#attached']['library'][] = 'core/jquery.ui';
    $element['#attached']['library'][] = 'core/jquery.ui.tabs';
    $element['#attached']['library'][] = 'core/jquery.ui.sortable';
    $element['#attached']['library'][] = 'core/jquery.ui.droppable';
    $element['#attached']['library'][] = 'core/jquery.ui.draggable';
    $element['#attached']['library'][] = 'core/jquery.ui.accordian';
    $element['#attached']['library'][] = 'core/jquery.ui.selectable';
    $element['#attached']['library'][] = 'core/jquery.ui.resizable';
    $element['#attached']['library'][] = 'core/jquery.ui.slider';
    $element['#attached']['library'][] = 'core/drupalSettings';

    $themes = $this->themeHandler->listInfo();
    $glazed_classes = [];
    foreach ($themes as $theme => $theme_info) {
      if ($theme_info->status == 1 && isset($theme_info->info['glazed_classes'])) {
        $optgroup = 'optgroup-' . $theme;
        $glazed_classes[$optgroup] = $theme_info->info['name'];
        $glazed_classes = array_merge($glazed_classes, $theme_info->info['glazed_classes']);
      }
    }

    $this->moduleHandler->alter('glazed_builder_classes', $glazed_classes);
    $settings['glazedClasses'] = $glazed_classes;

    $styles = $this->entityManager->getStorage('image_style')->loadMultiple();
    $styles_list = ['original' => t('Original image (No resizing)')];
    foreach ($styles as $style) {
      $styles_list[$style->id()] = $style->label();
    }

    $settings['imageStyles'] = $styles_list;

    // Load assets media module
    if ($this->moduleHandler->moduleExists('media')) {
      $element['#attached']['library'][] = 'media/view';
    }

    if ($config->get('development')) {
      $element['#attached']['library'][] = 'glazed_builder/development';
    }
    else {
      $element['#attached']['library'][] = 'glazed_builder/production';
    }

    $element['#cache']['tags'] = $config->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function ckeParseStyles($css_classes) {
    $set = array();
    $input = trim($css_classes);
    if (empty($input)) {
      return $set;
    }
    // Handle both Unix and Windows line-endings.
    foreach (explode("\n", str_replace("\r", '', $input)) as $line) {
      $line = trim($line);
      // [label]=[element].[class][.[class]][...] pattern expected.
      if (!preg_match('@^.+= *[a-zA-Z0-9]+(\.[a-zA-Z0-9_ -]+)*$@', $line)) {
        return FALSE;
      }
      list($label, $selector) = explode('=', $line, 2);
      $classes = explode('.', $selector);
      $element = array_shift($classes);

      $style = array();
      $style['name'] = trim($label);
      $style['element'] = trim($element);
      if (!empty($classes)) {
        $style['attributes']['class'] = implode(' ', array_map('trim', $classes));
      }
      $set[] = $style;
    }
    return $set;

  }

  /**
   * {@inheritdoc}
   */
  public function getCmsElementNames() {
    $cms_elements = &drupal_static(__CLASS__ . '::' .__FUNCTION__);
    if (!isset($cms_elements)) {
      $block_elements = [];
      if ($this->moduleHandler->moduleExists('block_content')) {
        if ($cache = $this->cacheBackend->get('glazed_builder:cms_elements_blocks')) {
          $block_elements = $cache->data;
        }
        else {
          $blacklist = [
            // These two blocks can only be configured in display variant plugin.
            // @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
            'page_title_block',
            'system_main_block',
            // Fallback plugin makes no sense here.
            'broken',
          ];
          $block_definitions = $this->blockManager->getDefinitions();
          foreach($block_definitions as $block_id => $definition) {
            $hidden = !empty($definition['_block_ui_hidden']);
            $blacklisted = in_array($block_id, $blacklist);
            $is_view = ($definition['provider'] == 'views');
            $is_ctools = ($definition['provider'] == 'ctools');
            if ($hidden || $blacklisted OR $is_view OR $is_ctools) {
              continue;
            }
            $block_elements['block-' . $block_id] = $this->t('Block: @block_name', ['@block_name' => ucfirst($definition['category']) . ': ' . $definition['admin_label']])->render();
          }

          $this->cacheBackend->set('glazed_builder:cms_elements_blocks', $block_elements);
        }
      }

      $views_elements = [];
      if ($this->moduleHandler->moduleExists('views')) {
        if ($cache = $this->cacheBackend->get('glazed_builder:cms_elements_views')) {
          $views_elements = $cache->data;
        }
        else {
          $views = Views::getAllViews();
          foreach ($views as $view) {
            if (!$view->status()) {
              continue;
            }
            $executable_view = Views::getView($view->id());
            $executable_view->initDisplay();
            foreach ($executable_view->displayHandlers as $id => $display) {
              $key = 'view-' . $executable_view->id() . '-' . $id;
              $views_elements[$key] = t('View: @view_name', ['@view_name' => $view->label() . ' (' . $display->display['display_title'] . ')'])->render();
            }
          }
          $this->cacheBackend->set('glazed_builder:cms_elements_views', $views_elements);
        }
      }

      $cms_elements = $block_elements + $views_elements;
    }
    return $cms_elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilesDirectoryPath() {
    $default_scheme = $this->configFactory->get('system.file')->get('default_scheme');
    return trim(str_replace($this->getBaseUrl(), '', file_create_url($default_scheme . '://')), '/');
  }

  /**
   * {@inheritdoc}
   */
  public function loadCmsElement($element_info, $settings, $data = [], AttachedAssets $assets = NULL) {
    if ($element_info['type'] == 'block') {
      $output = $this->glazedBlockHandler->getBlock($element_info, $assets, $data);
    }
    else {
      $output = FALSE;
      if ($element_info['type'] == 'view') {
        $output = $this->glazedViewHandler->getView($element_info['view_id'], $settings, $element_info['display_id'], $data, $assets);
      }
    }

    if (!$output) {
      $output = '<div class="empty-cms-block-placeholder"></div>';
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getGlazedElementsFolders() {
    $base_url = $this->getBaseUrl();
    $glazed_elements_folders = [[
      'folder' => realpath($this->getModulePath()) . DIRECTORY_SEPARATOR . 'glazed_elements',
      'folder_url' => $base_url . '/' . $this->getModulePath() . '/' . 'glazed_elements',
    ]];

    $themes = $this->themeHandler->listInfo();
    foreach ($themes as $theme_key => $theme_info) {
      if ($this->themeHandler->themeExists($theme_key)
        && ($folder = $this->fileSystem->realpath($this->getPath('theme', $theme_key) . DIRECTORY_SEPARATOR . 'elements'))) {
        $glazed_elements_folders[] = [
          'folder' => $folder,
          'folder_url' => $base_url . '/' . $this->getPath('theme', $theme_key) . '/' . 'elements',
        ];
      }
    }
    $this->moduleHandler->alter('glazed_builder_elements_folders', $glazed_elements_folders);

    return $glazed_elements_folders;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUrl() {
    $current_request = $this->requestStack->getCurrentRequest();
    return $current_request->getSchemeAndHttpHost() . $current_request->getBasePath();
  }

  /**
   * {@inheritdoc}
   */
  public function parseStringForCmsElementInfo($string) {
    if(strpos($string, 'block-') === 0) {
      preg_match('/^block-(.+):(.+)$/', $string, $matches);
      if (count($matches)) {
        if($matches[1] == 'block_content') {
          $element_info = [
            'type' => 'block',
            'provider' => $matches[1],
            'uuid' => $matches[2],
          ];
        }
        else {
          array_shift($matches);
          $element_info = [
            'type' => 'block',
            'provider' => 'plugin',
            'id' => implode(':', $matches),
          ];
        }
      }
      else {
        $parts = explode('-', $string);
        array_shift($parts);
        $element_info = [
          'type' => 'block',
          'provider' => 'plugin',
          'id' => implode('-', $parts),
        ];
      }
    }
    elseif (strpos($string, 'view-') === 0) {
      $parts = explode('-', $string);
      $element_info = [
        'type' => array_shift($parts),
        'display_id' => array_pop($parts),
        'view_id' => implode('-', $parts),
      ];
    }

    return $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmptyStringToGlazedFieldsOnEntity(EntityInterface $entity) {
    $entity_type = $entity->getEntityType()->id();
    $bundle = $entity->bundle();

    // Get the display for the current bundle
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load($entity_type . '.' . $bundle . '.' . 'default');

    // Get all fields on the current bundle
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);

    // Loop through each of the fields
    foreach ($fields as $field) {
      // Get the formatter for the field
      $renderer = $display->getRenderer($field->getName());
      if ($renderer) {
        // Check to see if the formatter is glazed_builder_text
        if ($renderer->getBaseId() == 'glazed_builder_text') {
          // If the field is empty, set an empty space to the field to force it to render
          if ($entity->get($field->getName())->isEmpty()) {
            $entity->get($field->getName())->set(0, '&nbsp;');
          }
        }
      }
    }
  }

  /**
   * Retrieves and caches list of views displays and their settings and fields.
   *
   * @return array $cms_view_elements_settings
   *   Array of views displayscontaining all metadata that the Glazed Builder
   *   interface uses for modifying the display using various settings. Keyed by
   *   an identifier with the view and display name.
   */
  protected function getCmsElementSettings() {
    $cms_view_elements_settings = &drupal_static(__FUNCTION__);
    if (!isset($cms_view_elements_settings)) {
      if ($cache = $this->cacheBackend->get('glazed_builder:cms_view_elements_settings')) {
        $cms_view_elements_settings = $cache->data;
      }
      else {
        $cms_view_elements_settings = [];
        $views = Views::getAllViews();
        foreach ($views as $view) {
          if (!$view->status()) {
            continue;
          }
          $executable_view = Views::getView($view->id());
          $executable_view->initDisplay();
          foreach ($executable_view->displayHandlers as $id => $display) {
            $key = 'az_view-' . $executable_view->id() . '-' . $id;
            $executable_view->setDisplay($display->display['id']);
            $title = $executable_view->getTitle();
            $storage = $executable_view->storage;
            $defaultDisplay = &$storage->getDisplay('default');

            $cms_view_elements_settings[$key] = [
              'view_display_type' => $display->getType(),
              'title' => !empty($title) ? 1 : 0,
              'contextual_filter' => isset($defaultDisplay['display_options']['arguments']) && count($defaultDisplay['display_options']['arguments']) ? 1 : 0
            ];

            $fields = isset($display->display['display_options']['fields']) ? $display->display['display_options']['fields'] : [];
            // Copy field list form default display when possible.
            if (count($fields) == 0  && $display->usesFields()) {
              $fields = $defaultDisplay['display_options']['fields'];
            }
            foreach ($fields as $k => $field) {
              $handler = $executable_view->display_handler->getHandler('field', $field['id']);
              if (empty($handler)) {
                $field_name = t('Broken/missing handler: @table > @field', [
                  '@table' => $field['table'],
                  '@field' => $field['field'],
                ]);
              }
              else {
                $field_name = Html::escape($handler->adminLabel(TRUE));
              }

              if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
                $field_name = '(' . $relationships[$field['relationship']] . ') ' . $field_name;
              }
              $fields[$k] = $field_name;
            }
            $cms_view_elements_settings[$key]['use_fields'] = (count($fields) > 1) ? 1 : 0;
            $cms_view_elements_settings[$key]['field_list'] = $fields;

            if (!empty($display->display['display_options']['pager'])) {
              $pager = $display->display['display_options']['pager'];
              $cms_view_elements_settings[$key]['pager'] = [
                'items_per_page' => !empty($pager['options']['items_per_page']) ? $pager['options']['items_per_page'] : NULL,
                'offset' => !empty($pager['options']['offset']) ? $pager['options']['offset'] : NULL,
              ];
            }
            elseif (!empty($cms_view_elements_settings['az_view-' . $view->id() . '-default']['pager'])) {
              $cms_view_elements_settings[$key]['pager'] = $cms_view_elements_settings['az_view-' . $executable_view->id() . '-default']['pager'];
            }
            else {
              $cms_view_elements_settings[$key] = [
                'items_per_page' => NULL,
                'offset' => NULL,
              ];
            }
          }
        }

        $this->cacheBackend->set('glazed_builder:cms_view_elements_settings', $cms_view_elements_settings);
      }
    }

    return $cms_view_elements_settings;
  }

  /**
   * Discovers Sidebar Elements and outputs them as JSON for builder interface.
   *
   * Checks for sidebar elements in glazed_elements folder, in active themes, and
   * in modules implementing hook_glazed_builder_elements_folders.
   *
   * @return array
   *   An array of elements to to be passed to the front end JS
   */
  protected function getTemplateElements(&$libraries) {
    $elements = &drupal_static(__CLASS__ . '::' . __FUNCTION__);
    if (!isset($elements)) {
      if ($cache = $this->cacheBackend->get('glazed_builder:template_elements')) {
        $elements = $cache->data;
      }
      else {
        $elements = [];

        $base_url = $this->getBaseUrl();

        $glazed_elements_folders = $this->getGlazedElementsFolders();

        foreach ($glazed_elements_folders as $glazed_elements_folder) {
          $src = realpath($glazed_elements_folder['folder']);
          $src_url = $glazed_elements_folder['folder_url'];
          if (is_dir($src)) {
            $files = $this->scanDirectory($src, '/\.html/');
            foreach ($files as $path => $file) {
              $path = realpath($path);
              $info = pathinfo($path);
              $p = str_replace(DIRECTORY_SEPARATOR, '|', str_replace('.html', '', substr(str_replace($src, '', $path), 1)));
              $elements[$p]['html'] = file_get_contents($path);
              $elements[$p]['name'] = $info['filename'];
              $folders = explode(DIRECTORY_SEPARATOR, str_replace($src, '', $path));
              array_pop($folders);
              $folders = implode('/', $folders);
              $elements[$p]['baseurl'] = $src_url . $folders . '/';
              if (file_exists(str_replace('.html', '.png', $path))) {
                $elements[$p]['thumbnail'] = $src_url . '/' . str_replace('|', '/', $p) . '.png';
              }
              if (file_exists(str_replace('.html', '.jpg', $path))) {
                $elements[$p]['thumbnail'] = $src_url . '/' . str_replace('|', '/', $p) . '.jpg';
              }

              if (file_exists(str_replace('.html', '.css', $path)) || file_exists(str_replace('.html', '.js', $path))) {
                $libraries[] = 'glazed_builder/elements.' . $info['filename'];
              }
            }
          }
        }
        $this->cacheBackend->set('glazed_builder:template_elements', $elements);
      }
    }

    return $elements;
  }

  /**
   * Retrieves and caches list of Views tags to help organize and filter the interface
   * where you can select views displays in the Glazed Builder modal.
   *
   * @return array
   *   Array of views tags keyed by an identifier with the view and display name
   */
  protected function getCmsViewsTags() {
    $cms_views_tags = &drupal_static(__FUNCTION__);
    if (!isset($cms_views_tags)) {
      if ($cache = $this->cacheBackend->get('glazed_builder:cms_views_tags')) {
        $cms_views_tags = $cache->data;
      }
      else {
        $cms_views_tags = [];

        if ($this->moduleHandler->moduleExists('views')) {
          $views = Views::getAllViews();
          foreach ($views as $view) {
            if (!$view->status()) {
              continue;
            }
            $executable_view = Views::getView($view->id());
            $executable_view->initDisplay();
            foreach ($executable_view->displayHandlers as $id => $display) {
              $cms_views_tags['az_view-' . $executable_view->id() . '-' . $id] = $executable_view->id();
            }
          }
        }

        $this->cacheBackend->set('glazed_builder:cms_views_tags', $cms_views_tags);
      }
    }

    return $cms_views_tags;
  }

  /**
   * Discovers CSS classes used for (bootstrap) buttons
   *
   * Checks for button classes in glazed_elements/Buttons and
   * in modules implementing hook_glazed_builder_element_buttons_folders.
   * These classes are used in the button modal element settings.
   *
   * @return array $button_styles
   *   Array of button style classes, keyed by an identifier for the button style
   */
  protected function getButtonStyles() {
    $button_styles = &drupal_static(__FUNCTION__);
    if (!isset($button_styles)) {
      if ($cache = $this->cacheBackend->get('glazed_builder:button_styles')) {
        $button_styles = $cache->data;
      }
      else {
        $button_styles = [];

        $glazed_element_buttons_folders = [$this->getModulePath() . DIRECTORY_SEPARATOR . 'glazed_elements/Buttons'];
        $this->moduleHandler->alter('glazed_builder_element_buttons_folders', $glazed_element_buttons_folders);

        $elements = [];
        foreach ($glazed_element_buttons_folders as $src) {
          if (is_dir($src)) {
            $files =  $this->scanDirectory($src, '/\.html/');
            foreach ($files as $path => $file) {
              $path = realpath($path);
              $info = pathinfo($path);
              if ($info['extension'] == 'html') {
                $elements[$info['filename']] = file_get_contents($path);
              }
            }
          }
        }
        foreach ($elements as $key => &$element) {
          preg_match('/class="(.*?)"/', $element, $match);
          $classes = preg_replace('/(btn\s)|(btn-\w+\s)|(\saz-\w+$)/', '', $match[1]);
          if (!empty($classes)) {
            $element = $classes;
          }
          else {
            unset($element);
          }
        }
        $button_styles = $elements;
        $this->cacheBackend->set('glazed_builder:button_styles', $button_styles);
      }
    }
    return $button_styles;
  }

  /**
   * Get the path to this module
   */
  private function getModulePath() {
    return drupal_get_path('module', 'glazed_builder');
  }

  /**
   * Get the path to a theme more module.
   *
   * @param string $type
   *   The type of path to get - module or theme
   * @param string $key
   *   The module/theme for which the path should be returned
   *
   * @return string
   *   The path, relative to the webroot, of the module/theme
   */
  private function getPath($type, $key) {
    return drupal_get_path($type, $key);
  }

  /**
   * Provides an OOP wrapper for file_scan_directory()
   *
   * @param $dir
   *   The base directory or URI to scan, without trailing slash.
   * @param $mask
   *   The preg_match() regular expression for files to be included.
   * @param $options
   *   An associative array of additional options, with the following elements:
   *   - 'nomask': The preg_match() regular expression for files to be excluded.
   *     Defaults to the 'file_scan_ignore_directories' setting.
   *   - 'callback': The callback function to call for each match. There is no
   *     default callback.
   *   - 'recurse': When TRUE, the directory scan will recurse the entire tree
   *     starting at the provided directory. Defaults to TRUE.
   *   - 'key': The key to be used for the returned associative array of files.
   *     Possible values are 'uri', for the file's URI; 'filename', for the
   *     basename of the file; and 'name' for the name of the file without the
   *     extension. Defaults to 'uri'.
   *   - 'min_depth': Minimum depth of directories to return files from. Defaults
   *     to 0.
   *
   * @return
   *   An associative array (keyed on the chosen key) of objects with 'uri',
   *   'filename', and 'name' properties corresponding to the matched files.
   *
   * @see file_scan_directory
   */
  private function scanDirectory($dir, $mask, $options = []) {
    return file_scan_directory($dir, $mask, $options);
  }

  /**
   * Parse the content to determine if there are any scripts, as well as to determine
   * the mode (static or dynamic)
   *
   * @param array $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response
  */
  private function parseContentForScripts(&$response) {
    if ((strpos($response['output'], 'glazed_frontend.min.js') !== FALSE) || strpos($response['output'], 'glazed_frontend.js') !== FALSE) {
      // dynamic mode means we add glazed_frontend.js for processing of elements
      // and styles that depend on JS. For example circle counter, parallax backgrounds
      // video backgrounds, etc.
      $response['mode'] = 'dynamic';
    }
  }

  /**
   * Parse the given value for content
   *
   * @param array $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response
   */
  private function parseForContent(&$response) {
    $doc = $this->createDocument($response['output']);
    $this->stripScriptsAndStylesheetsFromContent($doc, $response);
    $this->parseDocumentForTemplateLibrary($doc, $response);
    $this->parseDocumentForCmsElements($doc, $response);
    $this->getValueFromDoc($doc, $response);
  }

  /**
   * Create a DOMDocument from the given data, to be
   * used to parse the data for content
   *
   * @param string $data
   *   The data that is to be parsed into a DOMDocument
   *
   * @return \DOMDocument
   *   An object containing the data, ready to be parsed for content
   */
  private function createDocument($data) {
    // We convert html string to DOM object so that we can process individual elements
    $doc = new \DOMDocument("1.0", "UTF-8");
    $doc->resolveExternals = FALSE;
    $doc->substituteEntities = FALSE;
    $doc->strictErrorChecking = FALSE;
    libxml_use_internal_errors(TRUE);
    $raw = '<?xml encoding="UTF-8"><!DOCTYPE html><html><head></head><body>' . $data . '</body></html>';
    // Makes sure we use UTF-8 encoding, is needed to prevent loss of mul ibyte characters
    $forced_utf8 = mb_convert_encoding($raw, 'HTML-ENTITIES', 'UTF-8');
    @$doc->loadHTML($forced_utf8);
    libxml_clear_errors();

    return $doc;
  }

  /**
   * Strip any scripts and stylesheets from the content, as they are added in libraries
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data
   * @param array $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response
   */
  private function stripScriptsAndStylesheetsFromContent(\DOMDocument $doc, array &$response) {
    // Strip script tags
    $scripts = $doc->getElementsByTagName('script');
    // Note - use a while loop rather than foreach, as the dom document changes when
    // a node is removed, causing unexpected results
    while ($scripts->length) {
      $scripts->item(0)->parentNode->removeChild($scripts->item(0));
      $scripts = $doc->getElementsByTagName('script');
    }

    // Strip stylesheets
    $stylesheets = $doc->getElementsByTagName('link');
    while ($stylesheets->length) {
      $stylesheet = $stylesheets->item(0);
      if ($stylesheet->hasAttribute('rel') && $stylesheet->getAttribute('rel') == 'stylesheet') {
        $stylesheet->parentNode->removeChild($stylesheet);
        $stylesheets = $doc->getElementsByTagName('link');
      }
    }
  }

  /**
   * Parse the given DOMDocument for libraries to be included in
   * the response. Any found libraries should be added to
   * the $response['libraries'] array.
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data
   * @param array $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response
   */
  private function parseDocumentForTemplateLibrary(\DOMDocument $doc, array &$response) {
    $base_url = $this->getBaseUrl();
    $xpath = new \DOMXpath($doc);
    // We aggregate all element css and remove the link tags, but not sidebar
    // elements for editors because those would never be restored and thus lost after resaving.
    $result = $xpath->query('//*[@data-glazed-builder-libraries]');

    $nodes = [];
    foreach ($result as $node) {
      $nodes[] = $node;
    }

    foreach ($nodes as $node) {
      $library_keys = $node->getAttribute('data-glazed-builder-libraries');
      $keys = explode(' ', $library_keys);
      foreach ($keys as $key) {
        $response['library'][] = 'glazed_builder/elements.' . $key;
      }
    }
  }

  /**
   * Parse the given DOMDocument for Drupal elements (blocks, views etc)
   * to be returned as the response.
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data
   * @param array $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response
   */
  private function parseDocumentForCmsElements(\DOMDocument $doc, &$response) {
    // Drupal blocks and views are represented as empty tags, here we replace empty
    // tags with the actual block or view content
    $xpath = new \DOMXpath($doc);
    $result = $xpath->query('//div[contains(@class,"az-cms-element")]');

    $nodes = [];
    foreach ($result as $node) {
      $nodes[] = $node;
    }

    foreach ($nodes as $node) {
      while ($node->hasChildNodes()) {
        $node->removeChild($node->firstChild);
      }
      $base = $node->getAttribute('data-azb');
      $settings = $node->getAttribute('data-azat-settings');

      // Additional settings for cms views.
      $data = [
        'display_title' => $node->getAttribute('data-azat-display_title'),
        'override_pager' => $node->getAttribute('data-azat-override_pager'),
        'items' => $node->getAttribute('data-azat-items'),
        'offset' => $node->getAttribute('data-azat-offset'),
        'contextual_filter' => $node->getAttribute('data-azat-contextual_filter'),
        'toggle_fields' => $node->getAttribute('data-azat-toggle_fields'),
      ];

      $element_info = $this->parseStringForCmsElementInfo(substr($base, 3));
      $assets = new AttachedAssets();

      $html = $this->loadCmsElement($element_info, $settings, $data, $assets);
      if ($html) {
        $this->documentAppendHtml($node, $html);
        $response['library'] = array_merge($response['library'], $assets->getLibraries());
        $response['settings'] = array_merge($response['settings'], $assets->getSettings());
      }
    }
  }

  /**
   * Appends HTML to DOMDocument object. Used to add Drupal Blocks/Views to DOM
   * tree while processing raw Glazed Builder fields.
   *
   * @param \DomNode $parent
   *   The DOM object to which a new node will be added
   * @param string $source
   *   HTML code to be added on to DOM object
   */
  private function documentAppendHtml(\DOMNode $parent, $source) {
    $doc = new \DOMDocument("1.0", "UTF-8");
    $doc->resolveExternals = FALSE;
    $doc->substituteEntities = FALSE;
    $doc->strictErrorChecking = FALSE;
    libxml_use_internal_errors(TRUE);
    $raw = '<?xml encoding="UTF-8"><!DOCTYPE html><html><head></head><body>' . $source . '</body></html>';

    if (function_exists('mb_convert_encoding')) {
      $forced_utf8 = mb_convert_encoding($raw, 'HTML-ENTITIES', 'UTF-8');
    }
    else {
      $forced_utf8 = $raw;
    }

    @$doc->loadHTML($forced_utf8);
    libxml_clear_errors();

    foreach ($doc->getElementsByTagName('head')->item(0)->childNodes as $node) {
      $imported_node = $parent->ownerDocument->importNode($node, TRUE);
      $parent->appendChild($imported_node);
    }

    foreach ($doc->getElementsByTagName('body')->item(0)->childNodes as $node) {
      $imported_node = $parent->ownerDocument->importNode($node, TRUE);
      $parent->appendChild($imported_node);
    }
  }

  /**
   * Retrieve the value from the now fully parsed document and set it to
   * $response['output'].
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data
   * @param array $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response
   */
  private function getValueFromDoc(\DOMDocument $doc, array &$response) {
    $response['output'] = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace([
      '<?xml encoding="UTF-8">',
      '<html>',
      '</html>',
      '<head>',
      '</head>',
      '<body>',
      '</body>',
    ], ['', '', '', '', '', '', ''], $doc->saveHTML()));
  }
}
