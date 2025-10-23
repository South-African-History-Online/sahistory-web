<?php

namespace Drupal\tdih\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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

    // Create day, month, and year dropdowns for full date selection.
    $form['date_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row', 'g-2', 'align-items-end'],
      ],
    ];

    $form['date_container']['birthday_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => ['' => $this->t('- Select Day -')] + array_combine(
        range(1, 31),
        array_map(
          function ($day) {
            return sprintf('%02d', $day);
          },
          range(1, 31)
        )
      ),
      '#attributes' => [
        'class' => ['form-select', 'tdih-day-picker'],
      ],
      '#wrapper_attributes' => [
        'class' => ['col-md-4'],
      ],
    ];

    $form['date_container']['birthday_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => [
        '' => $this->t('- Select Month -'),
        '01' => $this->t('January'),
        '02' => $this->t('February'),
        '03' => $this->t('March'),
        '04' => $this->t('April'),
        '05' => $this->t('May'),
        '06' => $this->t('June'),
        '07' => $this->t('July'),
        '08' => $this->t('August'),
        '09' => $this->t('September'),
        '10' => $this->t('October'),
        '11' => $this->t('November'),
        '12' => $this->t('December'),
      ],
      '#attributes' => [
        'class' => ['form-select', 'tdih-month-picker'],
      ],
      '#wrapper_attributes' => [
        'class' => ['col-md-4'],
      ],
      '#states' => [
        'enabled' => [
          ':input[name="birthday_day"]' => ['!value' => ''],
        ],
      ],
    ];

    // Add year selection - reasonable range for birthdays.
    $current_year = date('Y');
    $year_options = ['' => $this->t('- Select Year -')];
    for ($year = $current_year; $year >= 1900; $year--) {
      $year_options[$year] = $year;
    }

    $form['date_container']['birthday_year'] = [
      '#type' => 'select',
      '#title' => $this->t('Year'),
      '#options' => $year_options,
      '#attributes' => [
        'class' => ['form-select', 'tdih-year-picker'],
      ],
      '#wrapper_attributes' => [
        'class' => ['col-md-4'],
      ],
      '#states' => [
        'enabled' => [
          ':input[name="birthday_month"]' => ['!value' => ''],
        ],
      ],
    ];

    // Hidden field to combine month and day for processing.
    $form['birthday_date'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['tdih-combined-date'],
      ],
    ];

    // Add a submit button.
    $form['date_container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show Events'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'tdih-birthday-submit'],
      ],
      '#wrapper_attributes' => [
        'class' => ['col-12', 'mt-3'],
      ],
      '#ajax' => [
        'callback' => 'Drupal\tdih\Plugin\Block\TdihInteractiveBlock::updateEvents',
        'wrapper' => 'tdih-events-wrapper',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading events...'),
        ],
      ],
      '#states' => [
        'enabled' => [
          ':input[name="birthday_day"]' => ['!value' => ''],
          ':input[name="birthday_month"]' => ['!value' => ''],
          ':input[name="birthday_year"]' => ['!value' => ''],
        ],
      ],
    ];

    // Add JavaScript to handle form interactions.
    $form['#attached']['library'][] = 'tdih/tdih-interactive';
    $form['#attached']['drupalSettings']['tdihBirthdayForm'] = [
      'formId' => 'tdih-birthday-date-form',
    ];

    // Add a container for the events display.
    $form['events_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'tdih-events-wrapper',
        'class' => ['tdih-events-container'],
      ],
    ];

    // If day, month, and year are selected, load and display events.
    $selected_day = $form_state->getValue('birthday_day');
    $selected_month = $form_state->getValue('birthday_month');
    $selected_year = $form_state->getValue('birthday_year');

    // Ensure proper formatting for date comparison.
    if (!empty($selected_day)) {
      $selected_day = sprintf('%02d', (int) $selected_day);
    }
    if (!empty($selected_month)) {
      $selected_month = sprintf('%02d', (int) $selected_month);
    }

    if (!empty($selected_month) && !empty($selected_day) && !empty($selected_year)) {
      // Get the NodeFetcher service.
      $node_fetcher = \Drupal::service('tdih.node_fetcher');

      // Create the full birth date and month-day pattern.
      $birth_date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $selected_day);
      $month_day_pattern = sprintf('%02d-%02d', $selected_month, $selected_day);

      // Load all events for this month-day combination.
      $nodes = $node_fetcher->loadPotentialEvents($month_day_pattern);
      $exact_match_items = [];
      $same_day_items = [];

      // Separate exact date matches from same month-day matches.
      foreach ($nodes as $node) {
        $item = TdihInteractiveBlock::buildNodeItems([$node])[0] ?? NULL;
        if ($item && !empty($item['raw_date'])) {
          // Check if this is an exact date match (same year, month, day).
          if ($item['raw_date'] === $birth_date) {
            $exact_match_items[] = $item;
          }
          // Check if this is same month-day but different year.
          elseif (preg_match('/\d{4}-(\d{2})-(\d{2})/', $item['raw_date'], $matches)) {
            $item_month_day = $matches[1] . '-' . $matches[2];

            if ($item_month_day === $month_day_pattern) {
              $same_day_items[] = $item;
            }
          }
        }
      }

      // Sort both arrays chronologically (oldest first).
      usort($exact_match_items, function ($a, $b) {
        return $a['event_date'] <=> $b['event_date'];
      });
      usort($same_day_items, function ($a, $b) {
        return $a['event_date'] <=> $b['event_date'];
      });

      // Combine results with exact matches first, then same day events.
      $all_birthday_events = array_merge($exact_match_items, $same_day_items);

      // If we have events, display them with appropriate messaging.
      if (!empty($all_birthday_events)) {
        $form['events_container']['events'] = [
          '#theme' => 'tdih_birthday_events',
          '#exact_match_events' => $exact_match_items,
          '#same_day_events' => $same_day_items,
          '#birth_date' => $birth_date,
          '#month_day_pattern' => $month_day_pattern,
          '#selected_year' => $selected_year,
        ];
      }
      else {
        // Display a message if no events were found.
        $form['events_container']['no_events'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => '<i class="fas fa-info-circle me-2"></i>' . $this->t('No historical events found for @date or @month_day in other years.', [
            '@date' => date('F j, Y', strtotime($birth_date)),
            '@month_day' => date('F j', strtotime($birth_date)),
          ]),
          '#attributes' => [
            'class' => ['alert', 'alert-info', 'text-center'],
          ],
        ];
      }
    }
    else {
      // Display a prompt to select a date.
      $form['events_container']['prompt'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '<i class="fas fa-birthday-cake me-2"></i>' . $this->t('Select your birth day, month, and year to discover historical events that happened on your birthday!'),
        '#attributes' => [
          'class' => ['alert', 'alert-light', 'text-center', 'text-muted'],
        ],
      ];
    }

    return $form;
  }

  /**
   * AJAX callback to update events display.
   */
  public function updateEventsCallback(array &$form, FormStateInterface $form_state) {
    return $form['events_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form is primarily handled via AJAX, so we don't need to do anything.
    // The form state is rebuilt to show the selected events.
    $form_state->setRebuild(TRUE);
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
