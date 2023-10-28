<?php

namespace Drupal\glazed_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Glazed Builder Profile form.
 *
 * @property \Drupal\glazed_builder\GlazedBuilderProfileInterface $entity
 */
class GlazedBuilderProfileForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the glazed builder profile.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\glazed_builder\Entity\GlazedBuilderProfile::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    // $form['weight'] = [
    //   '#type' => 'weight',
    //   '#title' => $this->t('Weight'),
    //   '#delta' => 10,
    //   '#default_value' => $this->entity->get('weight'),
    // ];

    $form['roles_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('If a user has one of the selected roles his Glazed Builder interface will be limited to the elements and buttons selected in this profile. New blocks and views are not included automatically.'),
    ];
    $options = [];
    foreach (user_roles(TRUE) as $role_id => $role) {
      $options[$role_id] = $role->label();
    }

    $form['roles_wrapper']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $options,
      '#default_value' => $this->entity->isNew() ? [] : $this->entity->get('roles'),
    ];

    $form['elements_wrapper'] = [
      '#type' => 'details',
      '#title' => t('Elements'),
    ];
    $options = self::getElements();
    $form['elements_wrapper']['elements'] = [
      '#type' => 'checkboxes',
      '#title' => t('Elements'),
      '#options' => $options,
      '#default_value' => $this->entity->isNew() ? array_keys($options) : $this->entity->get('elements'),
    ];

    $form['blocks_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Blocks'),
    ];
    $blacklist = [
      // These two blocks can only be configured in display variant plugin.
      // @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
      'page_title_block',
      'system_main_block',
      // Fallback plugin makes no sense here.
      'broken',
    ];
    $definitions = \Drupal::service('plugin.manager.block')->getDefinitions();
    $options = [];
    foreach ($definitions as $block_id => $definition) {
      $hidden = !empty($definition['_block_ui_hidden']);
      $blacklisted = in_array($block_id, $blacklist);
      $is_view = ($definition['provider'] == 'views');
      $is_ctools = ($definition['provider'] == 'ctools');
      if ($hidden || $blacklisted OR $is_view OR $is_ctools) {
        continue;
      }
      $options['az_block-' . $block_id] = ucfirst($definition['category']) . ': ' . $definition['admin_label'];
    }
    $form['blocks_wrapper']['blocks'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Blocks'),
      '#options' => $options,
      '#default_value' => $this->entity->isNew() ? array_keys($options) : $this->entity->get('blocks'),
    ];

    $form['views_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Views'),
    ];
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
    $form['views_wrapper']['views'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Views'),
      '#options' => $views_elements,
      '#default_value' => $this->entity->isNew() ? array_keys($views_elements) : $this->entity->get('views'),
    ];

    $form['inline_buttons'] = [
      '#type' => 'details',
      '#title' => $this->t('CKEditor buttons (inline editing)'),
      '#tree' => TRUE,
      '#attributes' => ['class' => ['cke_ltr']],
    ];
    $buttons = $this->entity->isNew() ?
      self::getInlineButtons() : $this->entity->get('inline_buttons');
    foreach (self::getAllButtons() as $button => $title) {
      $form['inline_buttons'][$button] = [
        '#type' => 'checkbox',
        '#title' => $title,
        '#default_value' => in_array($button, $buttons),
        // Add a button icon near to the checkbox.
        '#field_suffix' => sprintf('<span class="cke_button_icon cke_button__%s_icon"></span>', strtolower($button)),
      ];
    }

    $form['modal_buttons'] = [
      '#type' => 'details',
      '#title' => $this->t('CKEditor buttons (modal editing)'),
      '#tree' => TRUE,
      '#attributes' => ['class' => ['cke_ltr']],
    ];
    $buttons = $this->entity->isNew() ?
      self::getModalButtons() : $this->entity->get('modal_buttons');
    foreach (self::getAllButtons() as $button => $title) {
      $form['modal_buttons'][$button] = [
        '#type' => 'checkbox',
        '#title' => $title,
        '#default_value' => in_array($button, $buttons),
        // Add a button icon near to the checkbox.
        '#field_suffix' => sprintf('<span class="cke_button_icon cke_button__%s_icon"></span>', strtolower($button)),
      ];
    }

    $form['#attached']['library'][]  = 'glazed_builder/configuration.profileform';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Make the roles export more readable.
    $values['roles'] = array_values(array_filter($values['roles']));
    $values['elements'] = array_values(array_filter($values['elements']));
    $values['blocks'] = array_values(array_filter($values['blocks']));
    $values['views'] = array_values(array_filter($values['views']));
    $values['inline_buttons'] = array_keys(array_filter($values['inline_buttons']));
    $values['modal_buttons'] = array_keys(array_filter($values['modal_buttons']));
    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new glazed builder profile %label.', $message_args)
      : $this->t('Updated glazed builder profile %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * Returns element options.
   */
  function getElements() {
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
   * Returns all available CKEditor buttons.
   */
  protected static function  getAllButtons() {
    return  array(
      'Bold' => 'Bold',
      'Italic' => 'Italic',
      'Underline' => 'Underline',
      'Strike' => 'Strike through',
      'JustifyLeft' => 'Align left',
      'JustifyCenter' => 'Center',
      'JustifyRight' => 'Align right',
      'JustifyBlock' => 'Justify',
      'BulletedList' => 'Insert/Remove Bullet list',
      'NumberedList' => 'Insert/Remove Numbered list',
      'BidiLtr' => 'Left-to-right',
      'BidiRtl' => 'Right-to-left',
      'Outdent' => 'Outdent',
      'Indent' => 'Indent',
      'Undo' => 'Undo',
      'Redo' => 'Redo',
      'Link' => 'Link',
      'Unlink' => 'Unlink',
      'Anchor' => 'Anchor',
      'Image' => 'Image',
      'TextColor' => 'Text color',
      'BGColor' => 'Background color',
      'Superscript' => 'Superscript',
      'Subscript' => 'Subscript',
      'Blockquote' => 'Block quote',
      'Source' => 'Source code',
      'HorizontalRule' => 'Horizontal rule',
      'Cut' => 'Cut',
      'Copy' => 'Copy',
      'Paste' => 'Paste',
      'PasteText' => 'Paste Text',
      'PasteFromWord' => 'Paste from Word',
      'ShowBlocks' => 'Show blocks',
      'RemoveFormat' => 'Remove format',
      'SpecialChar' => 'Character map',
      'Format' => 'HTML block format',
      'Font' => 'Font',
      'FontSize' => 'Font size',
      'Styles' => 'Font style',
      'Table' => 'Table',
      'SelectAll' => 'Select all',
      'Find' => 'Search',
      'Replace' => 'Replace',
      'Flash' => 'Flash',
      'Smiley' => 'Smiley',
      'CreateDiv' => 'Div container',
      'Iframe' => 'IFrame',
      'Maximize' => 'Maximize',
      'SpellChecker' => 'Check spelling',
      'Scayt' => 'Spell check as you type',
      'About' => 'About',
      'Templates' => 'Templates',
      'CopyFormatting' => 'Copy Formatting',
      'NewPage' => 'New page',
      'Preview' => 'Preview',
      'PageBreak' => 'Page break',
    );
  }

  /**
   * Returns default buttons for inline mode.
   */
  protected static function getInlineButtons() {
    return [
      'Bold',
      'Italic',
      'RemoveFormat',
      'TextColor',
      'Format',
      'Styles',
      'FontSize',
      'JustifyLeft',
      'JustifyCenter',
      'JustifyRight',
      'JustifyBlock',
      'BulletedList',
      'Link',
      'Unlink',
      'Image',
      'Table',
      'Undo',
      'Redo',
    ];
  }

  /**
   * Returns default buttons form modal mode.
   */
  protected static function getModalButtons() {
    return [
      'Bold',
      'Italic',
      'Underline',
      'Strike',
      'Superscript',
      'Subscript',
      'RemoveFormat',
      'JustifyLeft',
      'JustifyCenter',
      'JustifyRight',
      'JustifyBlock',
      'BulletedList',
      'NumberedList',
      'Outdent',
      'Indent',
      'Blockquote',
      'CreateDiv',
      'Undo',
      'Redo',
      'PasteText',
      'PasteFromWord',
      'Link',
      'Unlink',
      'Image',
      'HorizontalRule',
      'SpecialChar',
      'Table',
      'Templates',
      'TextColor',
      'Source',
      'ShowBlocks',
      'Maximize',
      'Format',
      'Styles',
      'FontSize',
      'Scayt',
    ];
  }

}
