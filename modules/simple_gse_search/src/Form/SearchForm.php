<?php

namespace Drupal\simple_gse_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form for performing a simple redirect to display search results.
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_gse_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['s'] = [
      '#type' => 'textfield',
      '#title' => t('Search'),
      '#default_value' => $form_state->getValue('s', $this->getRequest()->query->get('s', '')),
      '#attributes' => [
        'placeholder' => $this->t('Search site...'),
        'class' => ['SearchForm-input'],
      ],
      '#theme_wrappers' => [],
      '#size' => NULL,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('go'),
      '#attributes' => ['class' => ['SearchForm-submit']],
    ];

    $form['#attributes'] = ['class' => ['SearchForm']];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(Url::fromRoute('simple_gse_search.search_page', [], [
      'query' => [
        's' => $form_state->getValue('s'),
      ],
    ]));
  }

}
