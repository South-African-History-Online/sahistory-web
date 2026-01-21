<?php

namespace Drupal\tdih\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter for TDIH date range by month and day (ignoring year).
 *
 * @ViewsFilter("tdih_date_range")
 *
 * @property \Drupal\views\Plugin\views\query\Sql $query
 */
class TdihDateRangeFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['from_month'] = ['default' => ''];
    $options['expose']['contains']['from_day'] = ['default' => ''];
    $options['expose']['contains']['to_month'] = ['default' => ''];
    $options['expose']['contains']['to_day'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $months = [
      '' => '- Any -',
      '1' => 'January',
      '2' => 'February',
      '3' => 'March',
      '4' => 'April',
      '5' => 'May',
      '6' => 'June',
      '7' => 'July',
      '8' => 'August',
      '9' => 'September',
      '10' => 'October',
      '11' => 'November',
      '12' => 'December',
    ];

    $days = ['' => '- Any -'];
    for ($i = 1; $i <= 31; $i++) {
      $days[$i] = $i;
    }

    $identifier = $this->options['expose']['identifier'];

    $form[$identifier . '_from_month'] = [
      '#type' => 'select',
      '#title' => $this->t('From Month'),
      '#options' => $months,
      '#default_value' => $this->value['from_month'] ?? '',
    ];

    $form[$identifier . '_from_day'] = [
      '#type' => 'select',
      '#title' => $this->t('From Day'),
      '#options' => $days,
      '#default_value' => $this->value['from_day'] ?? '',
    ];

    $form[$identifier . '_to_month'] = [
      '#type' => 'select',
      '#title' => $this->t('To Month'),
      '#options' => $months,
      '#default_value' => $this->value['to_month'] ?? '',
    ];

    $form[$identifier . '_to_day'] = [
      '#type' => 'select',
      '#title' => $this->t('To Day'),
      '#options' => $days,
      '#default_value' => $this->value['to_day'] ?? '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $identifier = $this->options['expose']['identifier'];

    $this->value['from_month'] = $input[$identifier . '_from_month'] ?? '';
    $this->value['from_day'] = $input[$identifier . '_from_day'] ?? '';
    $this->value['to_month'] = $input[$identifier . '_to_month'] ?? '';
    $this->value['to_day'] = $input[$identifier . '_to_day'] ?? '';

    // Return TRUE if any value is set.
    return !empty($this->value['from_month']) || !empty($this->value['from_day']) ||
           !empty($this->value['to_month']) || !empty($this->value['to_day']);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $from_month = (int) ($this->value['from_month'] ?? 0);
    $from_day = (int) ($this->value['from_day'] ?? 0);
    $to_month = (int) ($this->value['to_month'] ?? 0);
    $to_day = (int) ($this->value['to_day'] ?? 0);

    // If no values set, don't filter.
    if (!$from_month && !$from_day && !$to_month && !$to_day) {
      return;
    }

    $field = "$this->tableAlias.$this->realField";

    // Build the date range condition.
    // Convert month-day to a comparable format (MMDD as integer).
    if ($from_month && $from_day && $to_month && $to_day) {
      // Full range specified.
      $from_mmdd = $from_month * 100 + $from_day;
      $to_mmdd = $to_month * 100 + $to_day;

      // Use MONTH() and DAY() functions to extract and compare.
      $mmdd_expression = "MONTH($field) * 100 + DAY($field)";

      if ($from_mmdd <= $to_mmdd) {
        // Normal range (e.g., Feb 1 to Feb 14).
        $this->query->addWhereExpression(
          $this->options['group'],
          "$mmdd_expression BETWEEN :from_mmdd AND :to_mmdd",
          [':from_mmdd' => $from_mmdd, ':to_mmdd' => $to_mmdd]
        );
      }
      else {
        // Wrap-around range (e.g., Dec 15 to Jan 15).
        $this->query->addWhereExpression(
          $this->options['group'],
          "($mmdd_expression >= :from_mmdd OR $mmdd_expression <= :to_mmdd)",
          [':from_mmdd' => $from_mmdd, ':to_mmdd' => $to_mmdd]
        );
      }
    }
    elseif ($from_month || $to_month) {
      // Only month(s) specified.
      if ($from_month && $to_month) {
        if ($from_month <= $to_month) {
          $this->query->addWhereExpression(
            $this->options['group'],
            "MONTH($field) BETWEEN :from_month AND :to_month",
            [':from_month' => $from_month, ':to_month' => $to_month]
          );
        }
        else {
          // Wrap-around (e.g., November to February).
          $this->query->addWhereExpression(
            $this->options['group'],
            "(MONTH($field) >= :from_month OR MONTH($field) <= :to_month)",
            [':from_month' => $from_month, ':to_month' => $to_month]
          );
        }
      }
      elseif ($from_month) {
        $this->query->addWhereExpression(
          $this->options['group'],
          "MONTH($field) = :month",
          [':month' => $from_month]
        );
      }
      elseif ($to_month) {
        $this->query->addWhereExpression(
          $this->options['group'],
          "MONTH($field) = :month",
          [':month' => $to_month]
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->t('TDIH Date Range');
  }

}
