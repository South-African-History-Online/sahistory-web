<?php

namespace Drupal\glazed_builder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of glazed builder profiles.
 */
class GlazedBuilderProfileListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'glazed_builder_profiles';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['roles'] = $this->t('Roles');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\glazed_builder\GlazedBuilderProfileInterface $entity */
    $row['label'] = $entity->label();
    $row['id']['data']['#markup'] = implode(',', $entity->get('roles')) ;
    $row['status']['data']['#markup'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = \Drupal::config('glazed_builder.settings');

    $form['ui_customization'] = array(
      '#type' => 'details',
      '#title' => $this->t('CKEditor Overrides'),
      '#description' => t('Extend Glazed Builder CKEditor style options and CKEditor Font options.'),
    );

    $form['ui_customization']['cke_stylesset'] = array(
      '#type' => 'textarea',
      '#title' => t('CKEditor Formatting Styles'),
      '#description' => t('Enter one class on each line in the format: @format. Example: @example.', array(
        '@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-stylesSet',
        '@format' => '[label]=[element].[class]',
        '@example' => 'Sassy Title=h1.sassy-title',
      )) . ' ' . t('Uses the <a href="@url">@setting</a> setting internally.', array('@setting' => 'stylesSet', '@url' => 'http://docs.ckeditor.com/#!/api/CKEDITOR.config-cfg-stylesSet')),
      '#default_value' => $config->get('cke_stylesset'),
      // '#element_validate' => array('form_validate_stylesset'),
    );

    $form['ui_customization']['cke_fonts'] = array(
      '#type' => 'textarea',
      '#title' => t('CKEditor Fonts'),
      '#title' => t('CKEditor Font Options'),
      '#default_value' => $config->get('cke_fonts'),
      '#description' => t('Enter one class on each line in the format: @format. Example: @example<br />The font selector is only available if you add it in a Glazed Builder profile.', array(
        '@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-font_names',
        '@format' => 'Font Label/Font Name, Alternative System Font',
        '@example' => 'Times New Roman/Times New Roman, Times, serif',
      )) . ' ' . t('Uses the <a href="@url">@setting</a> setting internally.', array('@setting' => 'font_names', '@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-font_names')),
    );

    $form['bootstrap_details'] = array(
      '#type' => 'details',
      '#title' => $this->t('Bootstrap Assets'),
    );

    $form['bootstrap_details']['bootstrap'] = [
      '#type' => 'radios',
      '#title' => $this->t('Include Bootstrap Files'),
      '#description' => $this->t('Bootstrap 3 is required. Bootstrap 3 Light is recommended if your theme has conflicts with Bootstrap 3 CSS. Bootstrap Light includes all grid and helper classes but doesn\'t contain normalize.css and some typography styles.'),
      '#options' => [
        0 => $this->t('No'),
        2 => $this->t('Load Bootstrap 3 Light'),
        1 => $this->t('Load Bootstrap 3 Full'),
      ],
      '#default_value' => $config->get('bootstrap'),
    ];


    $form['media_details'] = array(
      '#type' => 'details',
      '#title' => $this->t('Media Browser'),
    );
    $default = ['' => $this->t('None (Use basic file upload widget)')];
    if (\Drupal::moduleHandler()->moduleExists('entity_browser')) {
      $media_browsers = \Drupal::entityQuery('entity_browser')->execute();
      $media_browsers = $default + $media_browsers;
    }
    else {
      $media_browsers = $default;
    }
    $form['media_details']['media_browser'] = [
      '#type' => 'radios',
      '#title' => $this->t('Media Browser'),
      '#description' => $this->t('Glazed Builder supports media image reusability via the Entity Browser module. The Entity Browser selected here will be used by the editor. The Entity Browser has to be using the iFrame display plugin.'),
      '#options' => $media_browsers,
      '#default_value' => $config->get('media_browser'),
    ];

    $form['experimental'] = array(
      '#type' => 'details',
      '#title' => $this->t('Text Format Filters'),
    );

    $form['experimental']['format_filters'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Process Text Format Filters on Frontend Builder content'),
      '#description' => $this->t('Use with caution. If a field uses Glazed Builder as field formatter any filters that are set on the field\'s text format will be ignored. This is because when editing on the frontend, you are editing the raw field contents. With this setting enabled the Glazed editor still loads raw fields content, but users that don\'t have Glazed Builder editing permission will get a filtered field. Some filters will not work at all with Glazed Builder while others should work just fine.'),
      '#default_value' => $config->get('format_filters'),
    );

    $form['uninstall'] = array(
      '#type' => 'details',
      '#title' => $this->t('Uninstall batch process'),
      '#description' => $this->t('Our builder content contains tokens like -base-url- that make sure your content safely migrates between environments. Before uninstalling this module you have to run this batch process on your production environment to replace the tokens. This will ensure your image, css and javascript files will keep working.'),
    );

    if (file_exists(__DIR__ . '/../glazed_builder/glazed_builder.js')) {
      $form['development'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Development mode'),
        '#description' => $this->t('In Development mode Glazed Builder will use non-minified files to make debugging easier.'),
        '#default_value' => $config->get('development'),
      );
    }

    $form['uninstall']['actions'] = array(
      '#type' => 'actions',
    );
    $form['uninstall']['actions']['start_batch'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Remove url tokens from Glazed Builder content'),
      '#submit' => [[self::class, 'startBatch']],
    );


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    \Drupal::configFactory()->getEditable('glazed_builder.settings')
      ->set('bootstrap', $form_state->getValue('bootstrap'))
      ->set('cke_fonts', $form_state->getValue('cke_fonts'))
      ->set('cke_stylesset', $form_state->getValue('cke_stylesset'))
      ->set('development', $form_state->getValue('development'))
      ->set('format_filters', $form_state->getValue('format_filters'))
      ->set('media_browser', $form_state->getValue('media_browser'))
      ->save();
    $this->messenger()->addStatus($this->t('The configuration has been updated'));
    parent::submitForm($form, $form_state);
  }

  /**
   * Start batch submit callback.
   */
  public static function startBatch(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('glazed_builder.admin_uninstall');
  }

}
