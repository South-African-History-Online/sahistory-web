<?php

declare(strict_types=1);

namespace Drupal\saho_webform_bulk\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\saho_webform_bulk\DeleteFilteredBatch;
use Drupal\saho_webform_bulk\FilteredSubmissionQuery;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirms deleting every submission that matches the results filter.
 */
final class DeleteFilteredConfirmForm extends ConfirmFormBase {

  /**
   * The webform.
   */
  private WebformInterface $webform;

  /**
   * The filter query for the webform and the request's search/state.
   */
  private FilteredSubmissionQuery $query;

  /**
   * Matching submission count, taken when the form is built.
   */
  private int $total = 0;

  public function __construct(private readonly ContainerInterface $container) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'saho_webform_bulk_delete_filtered_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WebformInterface $webform = NULL): array {
    $this->webform = $webform;
    $request = $this->getRequest();
    $this->query = FilteredSubmissionQuery::forFilter(
      $this->container,
      $webform,
      $request->query->get('search'),
      $request->query->get('state')
    );
    $this->total = $this->query->total();

    $form = parent::buildForm($form, $form_state);
    if ($this->total === 0) {
      $form['actions']['submit']['#disabled'] = TRUE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(
      $this->total,
      'Delete @count submission of %webform matching the current filter?',
      'Delete @count submissions of %webform matching the current filter?',
      ['%webform' => $this->webform->label()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $keys = $this->query->keys();
    $state = $this->query->state();
    if ($keys === '' && $state === '') {
      $filter = $this->t('No filter is set: this is every submission of the webform.');
    }
    else {
      $parts = [];
      if ($keys !== '') {
        $parts[] = $this->t('search "@keys"', ['@keys' => $keys]);
      }
      if ($state !== '') {
        $parts[] = $this->t('state "@state"', ['@state' => $state]);
      }
      $filter = $this->t('Filter: @filter.', ['@filter' => implode(', ', $parts)]);
    }
    return $filter . ' ' . $this->t('Submissions are deleted in batches of @chunk. This action cannot be undone.', [
      '@chunk' => DeleteFilteredBatch::CHUNK,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->formatPlural($this->total, 'Delete @count submission', 'Delete @count submissions');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('entity.webform.results_submissions', ['webform' => $this->webform->id()], [
      'query' => array_filter(['search' => $this->query->keys(), 'state' => $this->query->state()]),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    batch_set([
      'title' => $this->t('Deleting submissions of %webform', ['%webform' => $this->webform->label()]),
      'operations' => [
        [[DeleteFilteredBatch::class, 'process'], [$this->webform->id(), $this->query->keys(), $this->query->state()]],
      ],
      'finished' => [DeleteFilteredBatch::class, 'finished'],
      'progress_message' => $this->t('Deleted @current of @total...'),
    ]);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
