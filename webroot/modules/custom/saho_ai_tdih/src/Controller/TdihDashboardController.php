<?php

namespace Drupal\saho_ai_tdih\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\saho_ai_tdih\Service\TdihEventProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for TDIH AI processing dashboard.
 */
class TdihDashboardController extends ControllerBase {

  /**
   * The event processor service.
   *
   * @var \Drupal\saho_ai_tdih\Service\TdihEventProcessor
   */
  protected $processor;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a TdihDashboardController.
   *
   * @param \Drupal\saho_ai_tdih\Service\TdihEventProcessor $processor
   *   The event processor service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    TdihEventProcessor $processor,
    Connection $database,
    DateFormatterInterface $date_formatter,
  ) {
    $this->processor = $processor;
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_ai_tdih.processor'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * Displays the main dashboard.
   *
   * @return array
   *   A render array representing the dashboard page.
   */
  public function dashboard(): array {
    $stats = $this->processor->getStatistics();

    $build = [];

    $build['stats'] = [
      '#type' => 'details',
      '#title' => $this->t('Statistics'),
      '#open' => TRUE,
    ];

    $build['stats']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Metric'), $this->t('Count')],
      '#rows' => [
        [$this->t('Total Dateless Events'), $stats['total_dateless']],
        [$this->t('Births without dates'), $stats['births_dateless']],
        [$this->t('Deaths without dates'), $stats['deaths_dateless']],
        [$this->t('Processed by AI'), $stats['processed']],
        [$this->t('Applied to nodes'), $stats['applied']],
        [$this->t('Needs Manual Review'), $stats['manual_review']],
      ],
    ];

    $build['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['dashboard-actions']],
    ];

    $build['actions']['process'] = [
      '#type' => 'link',
      '#title' => $this->t('Process Batch'),
      '#url' => Url::fromRoute('saho_ai_tdih.process_batch'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    $build['actions']['review'] = [
      '#type' => 'link',
      '#title' => $this->t('Review Results'),
      '#url' => Url::fromRoute('saho_ai_tdih.review'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    $build['actions']['settings'] = [
      '#type' => 'link',
      '#title' => $this->t('Settings'),
      '#url' => Url::fromRoute('saho_ai_tdih.settings'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    // Recent results preview.
    $build['recent'] = [
      '#type' => 'details',
      '#title' => $this->t('Recent Processing Results'),
      '#open' => TRUE,
    ];

    $results = $this->getRecentResults(10);
    if (!empty($results)) {
      $rows = [];
      foreach ($results as $result) {
        $node_link = Link::createFromRoute(
          $result->original_title,
          'entity.node.canonical',
          ['node' => $result->nid]
        );

        $status_class = match($result->status) {
          'applied' => 'color-success',
          'processed' => 'color-warning',
          default => '',
        };

        $rows[] = [
          $node_link->toString(),
          $result->researched_date ?? $this->t('Not found'),
          [
            'data' => $result->status,
            'class' => [$status_class],
          ],
          $result->manual_review ? $this->t('Yes') : $this->t('No'),
          $this->formatTimestamp($result->processed),
        ];
      }

      $build['recent']['table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Event'),
          $this->t('Date Found'),
          $this->t('Status'),
          $this->t('Review'),
          $this->t('Processed'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No results yet.'),
      ];
    }
    else {
      $build['recent']['empty'] = [
        '#markup' => '<p>' . $this->t('No processing results yet. Start by processing a batch.') . '</p>',
      ];
    }

    return $build;
  }

  /**
   * Review page for AI results.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array representing the review page.
   */
  public function review(Request $request): array {
    $filter = $request->query->get('filter', 'all');
    $page = $request->query->get('page', 0);
    $per_page = 25;

    $build = [];

    // Filter options.
    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['review-filters']],
    ];

    $filters = [
      'all' => $this->t('All'),
      'pending_review' => $this->t('Needs Review'),
      'verified' => $this->t('Verified'),
      'processed' => $this->t('Processed'),
      'applied' => $this->t('Applied'),
    ];

    foreach ($filters as $key => $label) {
      $build['filters'][$key] = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => Url::fromRoute('saho_ai_tdih.review', [], ['query' => ['filter' => $key]]),
        '#attributes' => [
          'class' => ['button', $filter === $key ? 'button--primary' : ''],
        ],
      ];
    }

    // Bulk apply buttons.
    $build['bulk_actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['bulk-actions'], 'style' => 'margin: 1em 0;'],
    ];

    $build['bulk_actions']['apply_verified'] = [
      '#type' => 'link',
      '#title' => $this->t('Apply All Verified'),
      '#url' => Url::fromRoute('saho_ai_tdih.apply_all', ['type' => 'verified']),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'onclick' => 'return confirm("Apply all verified results to nodes?");',
      ],
    ];

    $build['bulk_actions']['apply_all'] = [
      '#type' => 'link',
      '#title' => $this->t('Apply All (incl. Partial)'),
      '#url' => Url::fromRoute('saho_ai_tdih.apply_all', ['type' => 'all']),
      '#attributes' => [
        'class' => ['button'],
        'onclick' => 'return confirm("Apply ALL results including partial verifications?");',
      ],
    ];

    // Get results based on filter.
    $results = $this->getFilteredResults($filter, $per_page, $page * $per_page);

    if (!empty($results)) {
      $rows = [];
      foreach ($results as $result) {
        $node_link = Link::createFromRoute(
          mb_substr($result->original_title, 0, 50) . (mb_strlen($result->original_title) > 50 ? '...' : ''),
          'entity.node.canonical',
          ['node' => $result->nid],
          ['attributes' => ['target' => '_blank']]
        );

        $verified_label = match((int) $result->date_verified) {
          1 => $this->t('Yes'),
          2 => $this->t('Partial'),
          default => $this->t('No'),
        };

        $action_links = [];
        if ($result->status === 'processed' && !empty($result->researched_date)) {
          $action_links['apply'] = [
            '#type' => 'link',
            '#title' => $this->t('Apply'),
            '#url' => Url::fromRoute('saho_ai_tdih.apply', ['nid' => $result->nid]),
            '#attributes' => ['class' => ['button', 'button--small', 'button--primary']],
          ];
        }

        // Format date as "j F Y" (e.g., "1 September 2025").
        $formatted_date = '-';
        if (!empty($result->researched_date)) {
          $date_obj = \DateTime::createFromFormat('Y-m-d', $result->researched_date);
          if ($date_obj) {
            $formatted_date = $date_obj->format('j F Y');
          }
          else {
            $formatted_date = $result->researched_date;
          }
        }

        $rows[] = [
          $result->id,
          ['data' => $node_link->toRenderable()],
          $formatted_date,
          $verified_label,
          $result->status,
          $result->review_reason ?? '-',
          ['data' => $action_links],
        ];
      }

      $build['results'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('ID'),
          $this->t('Event'),
          $this->t('Date'),
          $this->t('Verified'),
          $this->t('Status'),
          $this->t('Review Reason'),
          $this->t('Actions'),
        ],
        '#rows' => $rows,
      ];

      // Simple pagination.
      $build['pager'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['pager']],
      ];

      if ($page > 0) {
        $build['pager']['prev'] = [
          '#type' => 'link',
          '#title' => $this->t('Previous'),
          '#url' => Url::fromRoute('saho_ai_tdih.review', [], [
            'query' => ['filter' => $filter, 'page' => $page - 1],
          ]),
          '#attributes' => ['class' => ['button']],
        ];
      }

      if (count($results) === $per_page) {
        $build['pager']['next'] = [
          '#type' => 'link',
          '#title' => $this->t('Next'),
          '#url' => Url::fromRoute('saho_ai_tdih.review', [], [
            'query' => ['filter' => $filter, 'page' => $page + 1],
          ]),
          '#attributes' => ['class' => ['button']],
        ];
      }
    }
    else {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('No results found for this filter.') . '</p>',
      ];
    }

    return $build;
  }

  /**
   * Apply a result to the node.
   *
   * @param int $nid
   *   The node ID to which the result should be applied.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the review page.
   */
  public function apply(int $nid): RedirectResponse {
    // Get the result ID for this nid.
    $result = $this->database->select('saho_ai_tdih_results', 'r')
      ->fields('r', ['id'])
      ->condition('nid', $nid)
      ->condition('status', 'processed')
      ->execute()
      ->fetchField();

    if ($result && $this->processor->applyResult($result)) {
      $this->messenger()->addStatus($this->t('Result applied successfully to node @nid.', ['@nid' => $nid]));
    }
    else {
      $this->messenger()->addError($this->t('Failed to apply result for node @nid.', ['@nid' => $nid]));
    }

    return new RedirectResponse(Url::fromRoute('saho_ai_tdih.review')->toString());
  }

  /**
   * Apply all results of a given type.
   *
   * @param string $type
   *   The type of results to apply. Defaults to 'verified'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the review page after applying results.
   */
  public function applyAll(string $type = 'verified'): RedirectResponse {
    $query = $this->database->select('saho_ai_tdih_results', 'r')
      ->fields('r', ['id', 'nid'])
      ->condition('status', 'processed')
      ->isNotNull('researched_date');

    if ($type === 'verified') {
      // Only fully verified dates.
      $query->condition('date_verified', 1);
    }

    $results = $query->execute()->fetchAll();

    $applied = 0;
    $failed = 0;

    foreach ($results as $result) {
      if ($this->processor->applyResult($result->id)) {
        $applied++;
      }
      else {
        $failed++;
      }
    }

    if ($applied > 0) {
      $this->messenger()->addStatus($this->t('Applied @count results to nodes.', ['@count' => $applied]));
    }
    if ($failed > 0) {
      $this->messenger()->addWarning($this->t('@count results failed to apply.', ['@count' => $failed]));
    }
    if ($applied === 0 && $failed === 0) {
      $this->messenger()->addWarning($this->t('No results to apply.'));
    }

    return new RedirectResponse(Url::fromRoute('saho_ai_tdih.review')->toString());
  }

  /**
   * Gets recent processing results.
   *
   * @param int $limit
   *   The maximum number of results to return.
   *
   * @return array
   *   An array of recent processing result records from the database.
   */
  protected function getRecentResults(int $limit): array {
    return $this->database->select('saho_ai_tdih_results', 'r')
      ->fields('r')
      ->orderBy('processed', 'DESC')
      ->range(0, $limit)
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets filtered results for review.
   *
   * @param string $filter
   *   The filter type to apply.
   * @param int $limit
   *   The maximum number of results to return.
   * @param int $offset
   *   The offset for pagination.
   *
   * @return array
   *   An array of filtered processing result records from the database.
   */
  protected function getFilteredResults(string $filter, int $limit, int $offset): array {
    $query = $this->database->select('saho_ai_tdih_results', 'r')
      ->fields('r');

    switch ($filter) {
      case 'pending_review':
        $query->condition('manual_review', 1);
        $query->condition('status', 'processed');
        break;

      case 'verified':
        $query->condition('date_verified', 1);
        break;

      case 'processed':
        $query->condition('status', 'processed');
        break;

      case 'applied':
        $query->condition('status', 'applied');
        break;
    }

    $query->orderBy('processed', 'DESC');
    $query->range($offset, $limit);

    return $query->execute()->fetchAll();
  }

  /**
   * Formats a timestamp for display.
   *
   * @param int $timestamp
   *   The timestamp to format.
   *
   * @return string
   *   The formatted date string, or '-' if the timestamp is 0.
   */
  protected function formatTimestamp(int $timestamp): string {
    if ($timestamp === 0) {
      return '-';
    }
    return $this->dateFormatter->format($timestamp, 'short');
  }

}
