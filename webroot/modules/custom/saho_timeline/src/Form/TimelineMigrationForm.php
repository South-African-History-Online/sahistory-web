<?php

namespace Drupal\saho_timeline\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\saho_timeline\Service\TimelineMigrationService;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Batch\BatchBuilder;

/**
 * Form for migrating timeline articles to events.
 */
class TimelineMigrationForm extends FormBase {

  /**
   * The timeline migration service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineMigrationService
   */
  protected $migrationService;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new TimelineMigrationForm object.
   *
   * @param \Drupal\saho_timeline\Service\TimelineMigrationService $migration_service
   *   The timeline migration service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(TimelineMigrationService $migration_service, MessengerInterface $messenger) {
    $this->migrationService = $migration_service;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_timeline.migration_service'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saho_timeline_migration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--status">' .
      $this->t('This tool helps migrate timeline articles to the event content type. It will identify articles that appear to be timeline-related based on their titles, tags, and content.') .
      '</div>',
    ];

    $form['identification'] = [
      '#type' => 'details',
      '#title' => $this->t('Timeline Article Identification'),
      '#open' => TRUE,
    ];

    $form['identification']['identify'] = [
      '#type' => 'submit',
      '#value' => $this->t('Identify Timeline Articles'),
      '#submit' => ['::identifyArticles'],
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    // Show identified articles if they exist in form state.
    $identified_articles = $form_state->get('identified_articles');
    if (!empty($identified_articles)) {
      $form['identification']['results'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Found @count potential timeline articles.', [
          '@count' => count($identified_articles),
        ]) . '</p>',
      ];

      $form['articles'] = [
        '#type' => 'details',
        '#title' => $this->t('Select Articles to Migrate'),
        '#open' => TRUE,
      ];

      $options = [];
      foreach ($identified_articles as $article) {
        $options[$article->id()] = $article->getTitle() . ' (ID: ' . $article->id() . ')';
      }

      $form['articles']['article_ids'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Articles to Migrate'),
        '#options' => $options,
        '#default_value' => array_keys($options),
        '#description' => $this->t('Select which articles to migrate to events.'),
      ];

      $form['articles']['migrate_selected'] = [
        '#type' => 'submit',
        '#value' => $this->t('Migrate Selected Articles'),
        '#submit' => ['::migrateSelectedArticles'],
        '#attributes' => [
          'class' => ['button--primary'],
        ],
      ];

      $form['articles']['migrate_all'] = [
        '#type' => 'submit',
        '#value' => $this->t('Migrate All Articles'),
        '#submit' => ['::migrateAllArticles'],
        '#attributes' => [
          'class' => ['button--danger'],
        ],
      ];
    }

    $form['migration_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Migration Options'),
      '#open' => FALSE,
    ];

    $form['migration_options']['preserve_originals'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preserve Original Articles'),
      '#default_value' => TRUE,
      '#description' => $this->t('Keep the original articles after migration (recommended for safety).'),
    ];

    $form['migration_options']['unpublish_originals'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unpublish Original Articles'),
      '#default_value' => FALSE,
      '#description' => $this->t('Unpublish the original articles after successful migration.'),
      '#states' => [
        'visible' => [
          ':input[name="preserve_originals"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['migration_options']['add_redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add Redirects'),
      '#default_value' => TRUE,
      '#description' => $this->t('Create redirects from old article URLs to new event URLs.'),
    ];

    $form['migration_options']['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch Size'),
      '#default_value' => 50,
      '#min' => 10,
      '#max' => 200,
      '#description' => $this->t('Number of articles to process per batch.'),
    ];

    $form['history'] = [
      '#type' => 'details',
      '#title' => $this->t('Migration History'),
      '#open' => FALSE,
    ];

    // Get migration statistics.
    $stats = $this->getMigrationStatistics();

    $form['history']['stats'] = [
      '#type' => 'markup',
      '#markup' => '<ul>' .
      '<li>' . $this->t('Total articles migrated: @count', ['@count' => $stats['total']]) . '</li>' .
      '<li>' . $this->t('Last migration: @date', ['@date' => $stats['last_migration']]) . '</li>' .
      '<li>' . $this->t('Failed migrations: @count', ['@count' => $stats['failed']]) . '</li>' .
      '</ul>',
    ];

    $form['history']['clear_history'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Migration History'),
      '#submit' => ['::clearHistory'],
      '#attributes' => [
        'class' => ['button--danger'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Main submit is handled by specific submit handlers.
  }

  /**
   * Submit handler for identifying timeline articles.
   */
  public function identifyArticles(array &$form, FormStateInterface $form_state) {
    $articles = $this->migrationService->identifyTimelineArticles();

    if (empty($articles)) {
      $this->messenger->addWarning($this->t('No timeline articles found.'));
    }
    else {
      $this->messenger->addStatus($this->t('Found @count potential timeline articles.', [
        '@count' => count($articles),
      ]));
      $form_state->set('identified_articles', $articles);
      $form_state->setRebuild();
    }
  }

  /**
   * Submit handler for migrating selected articles.
   */
  public function migrateSelectedArticles(array &$form, FormStateInterface $form_state) {
    $selected_ids = array_filter($form_state->getValue('article_ids'));

    if (empty($selected_ids)) {
      $this->messenger->addWarning($this->t('No articles selected for migration.'));
      return;
    }

    $batch_size = $form_state->getValue('batch_size') ?? 50;
    $preserve = $form_state->getValue('preserve_originals');
    $unpublish = $form_state->getValue('unpublish_originals');
    $redirect = $form_state->getValue('add_redirect');

    // Create batch operation.
    $batch = new BatchBuilder();
    $batch->setTitle($this->t('Migrating Timeline Articles'))
      ->setInitMessage($this->t('Starting migration...'))
      ->setProgressMessage($this->t('Processing @current of @total articles.'))
      ->setErrorMessage($this->t('An error occurred during migration.'));

    // Add operations in chunks.
    $chunks = array_chunk($selected_ids, $batch_size);
    foreach ($chunks as $chunk) {
      $batch->addOperation([
        '\Drupal\saho_timeline\Form\TimelineMigrationForm',
        'processBatch',
      ], [$chunk, $preserve, $unpublish, $redirect]);
    }

    $batch->setFinishCallback([
      '\Drupal\saho_timeline\Form\TimelineMigrationForm',
      'finishBatch',
    ]);

    batch_set($batch->toArray());
  }

  /**
   * Submit handler for migrating all articles.
   */
  public function migrateAllArticles(array &$form, FormStateInterface $form_state) {
    $identified_articles = $form_state->get('identified_articles');

    if (empty($identified_articles)) {
      $this->messenger->addWarning($this->t('No articles to migrate.'));
      return;
    }

    $all_ids = array_keys($identified_articles);
    $form_state->setValue('article_ids', array_combine($all_ids, $all_ids));
    $this->migrateSelectedArticles($form, $form_state);
  }

  /**
   * Submit handler for clearing migration history.
   */
  public function clearHistory(array &$form, FormStateInterface $form_state) {
    // Clear the migration tracking table.
    try {
      \Drupal::database()->truncate('saho_timeline_migration')->execute();
      $this->messenger->addStatus($this->t('Migration history cleared.'));
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('Failed to clear migration history.'));
    }
  }

  /**
   * Batch operation callback.
   */
  public static function processBatch(array $article_ids, $preserve, $unpublish, $redirect, &$context) {
    $migration_service = \Drupal::service('saho_timeline.migration_service');

    if (!isset($context['results']['success'])) {
      $context['results']['success'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['skipped'] = 0;
    }

    $results = $migration_service->batchMigrateArticles($article_ids);

    $context['results']['success'] += $results['success'];
    $context['results']['failed'] += $results['failed'];
    $context['results']['skipped'] += $results['skipped'];

    $context['message'] = t('Migrated @count articles...', ['@count' => $context['results']['success']]);
  }

  /**
   * Batch finish callback.
   */
  public static function finishBatch($success, $results, $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addStatus(t('Migration completed: @success migrated, @failed failed, @skipped skipped.', [
        '@success' => $results['success'] ?? 0,
        '@failed' => $results['failed'] ?? 0,
        '@skipped' => $results['skipped'] ?? 0,
      ]));
    }
    else {
      $messenger->addError(t('Migration failed to complete.'));
    }
  }

  /**
   * Get migration statistics.
   */
  protected function getMigrationStatistics() {
    $database = \Drupal::database();
    $stats = [
      'total' => 0,
      'failed' => 0,
      'last_migration' => $this->t('Never'),
    ];

    try {
      // Check if migration table exists.
      if ($database->schema()->tableExists('saho_timeline_migration')) {
        $stats['total'] = $database->select('saho_timeline_migration', 'm')
          ->countQuery()
          ->execute()
          ->fetchField();

        $last = $database->select('saho_timeline_migration', 'm')
          ->fields('m', ['migrated'])
          ->orderBy('migrated', 'DESC')
          ->range(0, 1)
          ->execute()
          ->fetchField();

        if ($last) {
          $stats['last_migration'] = \Drupal::service('date.formatter')
            ->format($last, 'medium');
        }
      }
    }
    catch (\Exception $e) {
      // Table doesn't exist yet.
    }

    return $stats;
  }

}
