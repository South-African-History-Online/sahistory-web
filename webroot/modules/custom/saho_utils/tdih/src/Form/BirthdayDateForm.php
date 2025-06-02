<?php

namespace Drupal\tdih\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\tdih\Plugin\Block\TdihInteractiveBlock;

/**
 * Provides a form for selecting a birthday date to see historical events.
 */
class BirthdayDateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tdih_birthday_date_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create a container for the form elements.
    $form['#prefix'] = '<div id="tdih-birthday-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Hide the Spam Master text.
    $form['#after_build'][] = [$this, 'hideSpamMasterText'];

    // Add a title for the form.
    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('What happened on your birthday?'),
      '#attributes' => [
        'class' => ['tdih-birthday-title'],
      ],
    ];

    // Add a description.
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Select a date to see historical events that occurred on that day.'),
      '#attributes' => [
        'class' => ['tdih-birthday-description'],
      ],
    ];

    // Add a date field for selecting a birthday.
    $form['birthday_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Select a date'),
      '#title_display' => 'invisible',
      '#date_format' => 'Y-m-d',
      '#attributes' => [
        'class' => ['tdih-birthday-date-picker'],
      ],
      '#ajax' => [
        'callback' => [TdihInteractiveBlock::class, 'updateEvents'],
        'wrapper' => 'tdih-events-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading events...'),
        ],
      ],
    ];

    // Add a submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show Events'),
      '#attributes' => [
        'class' => ['tdih-birthday-submit'],
      ],
      '#ajax' => [
        'callback' => [TdihInteractiveBlock::class, 'updateEvents'],
        'wrapper' => 'tdih-events-wrapper',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading events...'),
        ],
      ],
    ];

    // Add a container for the events display.
    $form['events_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'tdih-events-wrapper',
        'class' => ['tdih-events-container'],
      ],
    ];

    // If a date is already selected, load and display events.
    $selected_date = $form_state->getValue('birthday_date');
    if (!empty($selected_date)) {
      $date = new DrupalDateTime($selected_date);
      $month = $date->format('m');
      $day = $date->format('d');

      // Get the NodeFetcher service.
      $node_fetcher = \Drupal::service('tdih.node_fetcher');

      // Load nodes for the selected date.
      $nodes = $node_fetcher->loadDateNodes($month, $day, 10);

      // If nodes were found, display them.
      if (!empty($nodes)) {
        $form['events_container']['events'] = [
          '#theme' => 'tdih_events',
          '#tdih_nodes' => TdihInteractiveBlock::buildNodeItems($nodes),
        ];
      }
      else {
        // Display a message if no events were found.
        $form['events_container']['no_events'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('No historical events found for this date.'),
          '#attributes' => [
            'class' => ['tdih-no-events'],
          ],
        ];
      }
    }
    else {
      // Display a prompt to select a date.
      $form['events_container']['prompt'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Select a date to see historical events.'),
        '#attributes' => [
          'class' => ['tdih-prompt'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form is primarily handled via AJAX, so we don't need to do anything.
  }

  /**
   * After build callback to hide the Spam Master text.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form.
   */
  public function hideSpamMasterText(array $form, FormStateInterface $form_state) {
    // Add CSS to hide the Spam Master text.
    $form['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => '.tdih-birthday-form .spam-master-message { ' .
        'display: none !important; ' .
        '}',
      ],
      'tdih_hide_spam_master',
    ];

    return $form;
  }

}
