<?php

namespace Drupal\tdih\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Provides a form for selecting day and month to see historical events.
 */
class DayMonthDateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tdih_day_month_date_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create a container for the form elements.
    $form['#prefix'] = '<div id="tdih-day-month-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Add a title for the form.
    $form['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('What happened on this day?'),
      '#attributes' => [
        'class' => ['tdih-day-month-title'],
      ],
    ];

    // Add a description.
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Select a day and month to see historical events.'),
      '#attributes' => [
        'class' => ['tdih-day-month-description'],
      ],
    ];

    // Create day and month dropdowns.
    $form['date_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row', 'g-2', 'align-items-end'],
      ],
    ];

    $form['date_container']['day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => ['' => $this->t('- Day -')] + array_combine(
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
        'class' => ['col-md-5'],
      ],
    ];

    $form['date_container']['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => [
        '' => $this->t('- Month -'),
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
        'class' => ['col-md-5'],
      ],
    ];

    // Add a submit button.
    $form['date_container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show Events'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'tdih-day-month-submit'],
      ],
      '#wrapper_attributes' => [
        'class' => ['col-md-2'],
      ],
      '#ajax' => [
        'callback' => '::updateDayMonthEvents',
        'wrapper' => 'tdih-day-month-events-wrapper',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading events...'),
        ],
      ],
      '#states' => [
        'enabled' => [
          ':input[name="day"]' => ['!value' => ''],
          ':input[name="month"]' => ['!value' => ''],
        ],
      ],
    ];

    // Add JavaScript to handle form interactions.
    $form['#attached']['library'][] = 'tdih/tdih-interactive';

    // Add a container for the events display.
    $form['events_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'tdih-day-month-events-wrapper',
        'class' => ['tdih-events-container', 'mt-4'],
      ],
    ];

    // If day and month are selected, load and display events.
    $selected_day = $form_state->getValue('day');
    $selected_month = $form_state->getValue('month');

    if (!empty($selected_day) && !empty($selected_month)) {
      // Format the date components.
      $day = sprintf('%02d', (int) $selected_day);
      $month = sprintf('%02d', (int) $selected_month);
      $month_day_pattern = sprintf('%02d-%02d', $month, $day);

      // Get the NodeFetcher service.
      $node_fetcher = \Drupal::service('tdih.node_fetcher');

      // Load all events for this month-day combination.
      $nodes = $node_fetcher->loadAllBirthdayEvents($month_day_pattern);
      $events = [];

      foreach ($nodes as $node) {
        // Build node item.
        if ($node->hasField('field_this_day_in_history_3') && !$node->get('field_this_day_in_history_3')->isEmpty()) {
          $raw_date = $node->get('field_this_day_in_history_3')->value;
          if (!empty($raw_date) && preg_match('/\d{4}-(\d{2})-(\d{2})/', $raw_date, $matches)) {
            $item_month_day = $matches[1] . '-' . $matches[2];
            if ($item_month_day === $month_day_pattern) {
              // Get the image URL if available.
              $image_url = '';
              if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
                $file = $node->get('field_event_image')->entity;
                if ($file) {
                  $file_url_generator = \Drupal::service('file_url_generator');
                  $image_url = $file_url_generator->generateAbsoluteString($file->getFileUri());
                }
              }

              $events[] = [
                'id' => $node->id(),
                'title' => $node->label(),
                'url' => $node->toUrl()->toString(),
                'raw_date' => $raw_date,
                'image' => $image_url,
                'body' => html_entity_decode(strip_tags($node->get('body')->processed ?? '')),
              ];
            }
          }
        }
      }

      if (!empty($events)) {
        $form['events_container']['events'] = [
          '#theme' => 'tdih_events',
          '#tdih_nodes' => $events,
          '#attributes' => [
            'class' => ['tdih-events-list'],
          ],
        ];
      }
      else {
        $form['events_container']['no_events'] = [
          '#markup' => '<div class="alert alert-info text-center">' .
          '<i class="fas fa-info-circle me-2"></i>' .
          $this->t('No historical events found for @date.', [
            '@date' => date('F j', mktime(0, 0, 0, $month, $day)),
          ]) . '</div>',
        ];
      }
    }

    return $form;
  }

  /**
   * AJAX callback to update events display.
   */
  public function updateDayMonthEvents(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get the selected date components.
    $selected_day = $form_state->getValue('day');
    $selected_month = $form_state->getValue('month');

    if (!empty($selected_day) && !empty($selected_month)) {
      // Format the date components.
      $day = sprintf('%02d', (int) $selected_day);
      $month = sprintf('%02d', (int) $selected_month);
      $month_day_pattern = sprintf('%02d-%02d', $month, $day);

      // Get the NodeFetcher service.
      $node_fetcher = \Drupal::service('tdih.node_fetcher');

      // Load all events for this month-day combination.
      $nodes = $node_fetcher->loadAllBirthdayEvents($month_day_pattern);
      $events = [];

      foreach ($nodes as $node) {
        if ($node->hasField('field_this_day_in_history_3') && !$node->get('field_this_day_in_history_3')->isEmpty()) {
          $raw_date = $node->get('field_this_day_in_history_3')->value;
          if (!empty($raw_date) && preg_match('/\d{4}-(\d{2})-(\d{2})/', $raw_date, $matches)) {
            $item_month_day = $matches[1] . '-' . $matches[2];
            if ($item_month_day === $month_day_pattern) {
              // Get the image URL if available.
              $image_url = '';
              if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
                $file = $node->get('field_event_image')->entity;
                if ($file) {
                  $file_url_generator = \Drupal::service('file_url_generator');
                  $image_url = $file_url_generator->generateAbsoluteString($file->getFileUri());
                }
              }

              $events[] = [
                'id' => $node->id(),
                'title' => $node->label(),
                'url' => $node->toUrl()->toString(),
                'raw_date' => $raw_date,
                'image' => $image_url,
                'body' => html_entity_decode(strip_tags($node->get('body')->processed ?? '')),
              ];
            }
          }
        }
      }

      // Build the render array.
      if (!empty($events)) {
        $events_html = [
          '#theme' => 'tdih_events',
          '#tdih_nodes' => $events,
          '#attributes' => [
            'class' => ['tdih-events-list'],
          ],
        ];
      }
      else {
        $events_html = [
          '#markup' => '<div class="alert alert-info text-center">' .
          '<i class="fas fa-info-circle me-2"></i>' .
          $this->t('No historical events found for @date.', [
            '@date' => date('F j', mktime(0, 0, 0, $month, $day)),
          ]) . '</div>',
        ];
      }

      // Replace the events container.
      $response->addCommand(new ReplaceCommand('.tdih-events-container',
        '<div class="tdih-events-container mt-4">' .
        \Drupal::service('renderer')->render($events_html) .
        '</div>'));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form is handled via AJAX.
    $form_state->setRebuild(TRUE);
  }

}
