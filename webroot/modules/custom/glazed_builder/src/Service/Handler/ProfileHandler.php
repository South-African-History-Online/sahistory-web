<?php

namespace Drupal\glazed_builder\Service\Handler;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\glazed_builder\GlazedBuilderProfileInterface;
use Drupal\views\Views;

/**
 * ProfileHandler service.
 */
class ProfileHandler {

  /**
   * The context.repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The plugin.manager.block service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs the profile handler.
   *
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context.repository service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block plugin manager.
   */
  public function __construct(ContextRepositoryInterface $context_repository, BlockManagerInterface $block_manager) {
    $this->contextRepository = $context_repository;
    $this->blockManager = $block_manager;
  }

  /**
   * Method description.
   */
  public function buildSettings(GlazedBuilderProfileInterface $profile) {
    $hide_main_elements = array_diff_key(self::getMainElements(), array_combine($profile->get('elements'), $profile->get('elements')));
    $hide_block_elements = array_diff_key($this->getBlockElements(), array_combine($profile->get('blocks'), $profile->get('blocks')));
    $hide_view_elements = array_diff_key($this->getViewElements(), array_combine($profile->get('views'), $profile->get('views')));
    $hide_elements = array_merge($hide_main_elements, $hide_block_elements, $hide_view_elements);

    $cke_config = self::getCkeConfig($profile);
    return [
      'hide_els' => $hide_elements,
      'ck_config' => $cke_config,
    ];
  }

  /**
   * Returns main elements
   */
  protected static function getMainElements() {
    return [
      'az_accordion' => t('Accordion'),
      'az_alert' => t('Alert'),
      'az_blockquote' => t('Blockquote'),
      'az_button' => t('Button'),
      'az_circle_counter' => t('Circle Counter'),
      'az_countdown' => t('Countdown'),
      'az_counter' => t('Counter'),
      'az_html' => t('HTML'),
      'az_icon' => t('Icon'),
      'az_image' => t('Image'),
      'az_images_carousel' => t('Image Carousel'),
      'az_jumbotron' => t('Jumbotron'),
      'az_link' => t('Link'),
      'az_map' => t('Map'),
      'az_panel' => t('Panel'),
      'az_progress_bar' => t('Progress Bar'),
      'az_separator' => t('Separator'),
      'az_text' => t('Text'),
      'az_video' => t('Video'),
      'az_well' => t('Well'),
      'az_carousel' => t('Carousel'),
      'az_container' => t('Container'),
      'az_layers' => t('Layers'),
      'az_row' => t('Row'),
      'az_section' => t('Section'),
      'st_social' => t('Social Links'),
      'az_tabs' => t('Tabs'),
      'az_toggle' => t('Toggle'),
    ];
  }

  /**
   * Return block elements.
   */
  protected function getBlockElements() {

    $blacklist = [
      // These two blocks can only be configured in display variant plugin.
      // @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
      'page_title_block',
      'system_main_block',
      // Fallback plugin makes no sense here.
      'broken',
    ];
    $definitions = $this->blockManager->getDefinitions();
    $block_elements = [];
    foreach ($definitions as $block_id => $definition) {
      $hidden = !empty($definition['_block_ui_hidden']);
      $blacklisted = in_array($block_id, $blacklist);
      $is_view = ($definition['provider'] == 'views');
      $is_ctools = ($definition['provider'] == 'ctools');
      if ($hidden || $blacklisted OR $is_view OR $is_ctools) {
        continue;
      }
      $block_elements['az_block-' . $block_id] = ucfirst($definition['category']) . ': ' . $definition['admin_label'];
    }

    return $block_elements;
  }

  /**
   * Returns view elements.
   */
  protected function getViewElements() {
    $views_elements = [];
    $views = Views::getAllViews();
    foreach ($views as $view) {
      if (!$view->status()) {
        continue;
      }
      $executable_view = Views::getView($view->id());
      $executable_view->initDisplay();
      foreach ($executable_view->displayHandlers as $id => $display) {
        $key = 'az_view-' . $executable_view->id() . '-' . $id;
        $views_elements[$key] = $view->label() . ': ' . $display->display['display_title'];
      }
    }
    return $views_elements;
  }

  /**
   * Return CKEditor config.
   */
  protected static function getCkeConfig(GlazedBuilderProfileInterface $profile) {

    // Create CKEditor buttons configuration.
    $toolbar_config['inline'] = [
      ['name' => 'basicstyles', 'items' => ['Bold', 'Italic', 'Underline']],
      ['name' => 'colors', 'items' => ['TextColor']],
      ['name' => 'styles', 'items' => ['Format', 'Styles', 'FontSize']],
      ['name' => 'paragraph', 'items' => ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'BulletedList', 'NumberedList']],
      ['name' => 'links', 'items' => ['Link', 'Unlink']],
      ['name' => 'insert', 'items' => ['Image', 'Table']],
      ['name' => 'clipboard', 'items' => ['Undo', 'Redo']],
    ];

    $toolbar_config['modal'] = [
      ['name' => 'basicstyles', 'items' => ['Bold', 'Italic', 'Underline', 'Strike', 'Superscript', 'Subscript', 'RemoveFormat']],
      ['name' => 'paragraph', 'items' => ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'BulletedList', 'NumberedList', 'Outdent', 'Indent', 'Blockquote', 'CreateDiv']],
      ['name' => 'clipboard', 'items' => ['Undo', 'Redo', 'PasteText', 'PasteFromWord']],
      ['name' => 'links', 'items' => ['Link', 'Unlink']],
      ['name' => 'insert', 'items' => ['Image', 'HorizontalRule', 'SpecialChar', 'Table', 'Templates']],
      ['name' => 'colors', 'items' => ['TextColor']],
      ['name' => 'document', 'items' => ['Source']],
      ['name' => 'tools', 'items' => ['ShowBlocks', 'Maximize']],
      ['name' => 'styles', 'items' => ['Format', 'Styles', 'FontSize']],
      ['name' => 'editing', 'items' => ['Scayt']],
    ];

    $registered_buttons = [];
    $cke_config = [];
    foreach ($toolbar_config as $mode => $config) {
      $key = $mode == 'inline' ? 'inline_buttons' : 'modal_buttons';
      $cke_config[$mode] = $config;
      foreach ($config as $panel_index => $panel) {
        foreach ($panel['items'] as $button_index => $button) {
          if (!in_array($button, $profile->get($key))) {
            unset($cke_config[$mode][$panel_index]['items'][$button_index]);
          }
          $registered_buttons[] = $button;
        }
        // Rebase array keys after unsetting, otherwise JSON conversion will turn this into an object and CKEditor expects an array
        $cke_config[$mode][$panel_index]['items'] = array_values($cke_config[$mode][$panel_index]['items']);
      }

      // Create a separate pane for buttons that are not present in the pane
      // config.
      $cke_config[$mode][] = [
        'name' => 'misc',
        'items' => array_values(array_diff($profile->get($key), $registered_buttons)),
      ];
    }

    return $cke_config;
  }

}
