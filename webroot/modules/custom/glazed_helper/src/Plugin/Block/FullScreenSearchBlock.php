<?php

namespace Drupal\glazed_helper\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides Full Screen search block
 *
 * @Block(
 *   id = "full_screen_search",
 *   admin_label = @Translation("Glazed Full Screen Search"),
 *   category = @Translation("Forms")
 * )
 */
class FullScreenSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('search')) {
      $search_form = \Drupal::formBuilder()->getForm('Drupal\search\Form\SearchBlockForm');
      $search_form['keys']['#prefix'] = '<div class="full-screen-search-form-input">';
      $search_form['keys']['#suffix'] = '</div>';
      $search_form['keys']['#title_display'] = 'before';
      $search_form['keys']['#title'] = $this->t('Type and Press “enter” to Search');
      $search_form['keys']['#attributes']['placeholder'] = FALSE;
      $search_form['keys']['#attributes']['autocomplete'] = 'off';
      $search_form['keys']['#attributes']['class'][] = 'search-query';
      unset($search_form['keys']['#field_suffix']); // Unset submit button, we search when pressing return
      $search_form['keys']['#input_group_button'] = FALSE; // remove .input-group wrapper
      $search_form['#attributes']['class'][] = 'invisible';
      $search_form['#attributes']['class'][] = 'full-screen-search-form';
      $search_form['#attributes']['class'][] = 'invisible';
      // Search screen toggle button
      $content['full_screen_search_button'] = [
        '#type' => 'button',
        '#button_type' => 'button',
        '#value' => '',
        '#attributes' => ['class' => ['btn-link', 'full-screen-search-button', 'icon', 'glyphicon', 'glyphicon-search'], 'value' => $this->t('Search')],
      ];
      $content['search_form'] = $search_form;
      return $content;
    }
    else {
      drupal_set_message(t('Search module in not installed. Please install Search module to use the Glazed Full Screen Search Block'), 'error');
      return [];
    }

  }

}
