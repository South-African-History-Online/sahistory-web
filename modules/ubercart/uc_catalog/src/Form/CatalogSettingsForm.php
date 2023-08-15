<?php

namespace Drupal\uc_catalog\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Views;

/**
 * Configure catalog settings for this site.
 */
class CatalogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_catalog_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_catalog.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uc_catalog.settings');

    $view = Views::getView('uc_catalog');
    $view->initDisplay();
    $displays = array();
    foreach ($view->displayHandlers as $display) {
      if ($display->getPluginId() == 'page') {
        $displays[$display->display['id']] = $display->display['display_title'];
      }
    }

    $form['uc_catalog_display'] = array(
      '#type' => 'select',
      '#title' => $this->t('Catalog display'),
      '#default_value' => $config->get('display'),
      '#options' => $displays,
    );

    $vid = $config->get('vocabulary');
    if ($vid) {
      $catalog = Vocabulary::load($vid);

      $form['catalog_vid'] = array(
        '#markup' => '<p>' . $this->t('The taxonomy vocabulary <a href=":edit-url">%name</a> is set as the product catalog.', [':edit-url' => Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $catalog->id()])->toString(), '%name' => $catalog->label()]) . '</p>',
      );
    }

    $vocabs = array();
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $vid => $vocabulary) {
      $vocabs[$vid] = $vocabulary->label();
    }

    $form['uc_catalog_vid'] = array(
      '#type' => 'select',
      '#title' => $this->t('Catalog vocabulary'),
      '#default_value' => $config->get('vocabulary'),
      '#options' => $vocabs,
    );

    $form['uc_catalog_breadcrumb'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display the catalog breadcrumb'),
      '#default_value' => $config->get('breadcrumb'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('uc_catalog.settings')
      ->set('display', $form_state->getValue('uc_catalog_display'))
      ->set('vocabulary', $form_state->getValue('uc_catalog_vid'))
      ->set('breadcrumb', $form_state->getValue('uc_catalog_breadcrumb'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
