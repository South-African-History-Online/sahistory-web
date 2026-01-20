<?php

namespace Drupal\saho_ai_tdih\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\saho_ai_tdih\Service\TdihEventProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for processing a batch of dateless events.
 */
class ProcessBatchForm extends FormBase {

  /**
   * The event processor service.
   *
   * @var \Drupal\saho_ai_tdih\Service\TdihEventProcessor
   */
  protected $processor;

  /**
   * Constructs a ProcessBatchForm.
   *
   * @param \Drupal\saho_ai_tdih\Service\TdihEventProcessor $processor
   *   The event processor service.
   */
  public function __construct(TdihEventProcessor $processor) {
    $this->processor = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_ai_tdih.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saho_ai_tdih_process_batch';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saho_ai_tdih.settings');

    // Statistics.
    $stats = $this->processor->getStatistics();

    $form['stats'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Current Statistics'),
    ];

    $form['stats']['info'] = [
      '#markup' => '<p>' . $this->t('Total dateless events: @total<br>Births: @births<br>Deaths: @deaths<br>Already processed: @processed', [
        '@total' => $stats['total_dateless'],
        '@births' => $stats['births_dateless'],
        '@deaths' => $stats['deaths_dateless'],
        '@processed' => $stats['processed'],
      ]) . '</p>',
    ];

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Processing Options'),
    ];

    $form['options']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => [
        'births' => $this->t('Births only (@count)', ['@count' => $stats['births_dateless']]),
        'deaths' => $this->t('Deaths only (@count)', ['@count' => $stats['deaths_dateless']]),
        'all' => $this->t('All events (@count)', ['@count' => $stats['total_dateless']]),
      ],
      '#default_value' => 'births',
      '#description' => $this->t('Select which category of events to process.'),
      '#ajax' => [
        'callback' => '::updatePreview',
        'wrapper' => 'preview-wrapper',
      ],
    ];

    $form['options']['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch Size'),
      '#min' => 1,
      '#max' => 50,
      '#default_value' => $config->get('batch_size') ?? 10,
      '#description' => $this->t('Number of events to process in this batch. Keep low to avoid API rate limits.'),
      '#ajax' => [
        'callback' => '::updatePreview',
        'wrapper' => 'preview-wrapper',
        'event' => 'change',
      ],
    ];

    $form['options']['delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay between requests (seconds)'),
      '#min' => 1,
      '#max' => 30,
      '#default_value' => $config->get('delay_between_requests') ?? 2,
      '#description' => $this->t('Wait time between API calls to avoid rate limiting.'),
    ];

    // Preview events that will be processed.
    $form['preview'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview Events'),
      '#open' => FALSE,
      '#prefix' => '<div id="preview-wrapper">',
      '#suffix' => '</div>',
    ];

    $category = $form_state->getValue('category') ?? 'births';
    $batch_size = $form_state->getValue('batch_size') ?? ($config->get('batch_size') ?? 10);
    $events = $this->processor->getDatelessEvents((int) $batch_size, 0, $category);

    if (!empty($events)) {
      $rows = [];
      foreach ($events as $event) {
        $rows[] = [
          $event->nid,
          $event->title,
        ];
      }

      $form['preview']['table'] = [
        '#type' => 'table',
        '#header' => [$this->t('NID'), $this->t('Title')],
        '#rows' => $rows,
        '#caption' => $this->t('Events to be processed (@count)', ['@count' => count($events)]),
      ];
    }
    else {
      $form['preview']['empty'] = [
        '#markup' => '<p>' . $this->t('No events found for this category.') . '</p>',
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Processing'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('saho_ai_tdih.dashboard'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * AJAX callback to update the preview.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The preview render array for the form.
   */
  public function updatePreview(array &$form, FormStateInterface $form_state): array {
    return $form['preview'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $category = $form_state->getValue('category');
    $batch_size = (int) $form_state->getValue('batch_size');
    $delay = (int) $form_state->getValue('delay');

    // Get events to process.
    $events = $this->processor->getDatelessEvents($batch_size, 0, $category);

    if (empty($events)) {
      $this->messenger()->addWarning($this->t('No events found to process.'));
      return;
    }

    // Set up batch.
    $batch = [
      'title' => $this->t('Processing TDIH Events'),
      'operations' => [],
      'finished' => [self::class, 'batchFinished'],
      'init_message' => $this->t('Starting AI processing...'),
      'progress_message' => $this->t('Processed @current of @total events.'),
      'error_message' => $this->t('An error occurred during processing.'),
    ];

    foreach ($events as $event) {
      $batch['operations'][] = [
        [self::class, 'processEventBatch'],
        [$event->nid, $delay],
      ];
    }

    batch_set($batch);
  }

  /**
   * Batch callback to process a single event.
   *
   * Note: Static batch callbacks cannot use dependency injection, so we must
   * use \Drupal::service() here. This is a Drupal batch API limitation.
   *
   * @param int $nid
   *   The node ID of the event to process.
   * @param int $delay
   *   The delay in seconds between processing events (1-30).
   * @param array $context
   *   Reference to the batch context array.
   */
  public static function processEventBatch(int $nid, int $delay, array &$context): void {
    $processor = \Drupal::service('saho_ai_tdih.processor');

    $result = $processor->processEvent($nid);

    if (isset($result['error'])) {
      $context['results']['errors'][] = $result['error'];
    }
    else {
      $context['results']['processed'][] = $nid;
      if (!empty($result['researched_date'])) {
        $context['results']['dates_found'][] = $nid;
      }
    }

    $context['message'] = t('Processing event @nid', ['@nid' => $nid]);

    // Delay between requests.
    if ($delay > 0) {
      sleep($delay);
    }
  }

  /**
   * Batch finished callback.
   *
   * Note: Static batch callbacks cannot use dependency injection, so we must
   * use \Drupal::messenger() here. This is a Drupal batch API limitation.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results collected during batch processing.
   * @param array $operations
   *   Any remaining operations that were not processed.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();

    if ($success) {
      $processed = count($results['processed'] ?? []);
      $dates_found = count($results['dates_found'] ?? []);
      $errors = count($results['errors'] ?? []);

      $messenger->addStatus(t('Processed @processed events. Found dates for @found events.', [
        '@processed' => $processed,
        '@found' => $dates_found,
      ]));

      if ($errors > 0) {
        $messenger->addWarning(t('@count errors occurred during processing.', ['@count' => $errors]));
      }
    }
    else {
      $messenger->addError(t('Batch processing encountered an error.'));
    }
  }

}
