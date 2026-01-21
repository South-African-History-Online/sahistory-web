<?php

namespace Drupal\tdih\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler for TDIH dates by month and day (ignoring year).
 *
 * @ViewsSort("tdih_date_sort")
 *
 * @property \Drupal\views\Plugin\views\query\Sql $query
 */
class TdihDateSort extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Ensure the event date table is joined.
    $this->query->ensureTable('node__field_event_date');
    $field = "node__field_event_date.field_event_date_value";

    // Sort by month first, then by day, ignoring year.
    // This groups all January 1st events together, then January 2nd, etc.
    $this->query->addOrderBy(
      NULL,
      "MONTH($field)",
      $this->options['order'],
      'tdih_month_sort'
    );
    $this->query->addOrderBy(
      NULL,
      "DAY($field)",
      $this->options['order'],
      'tdih_day_sort'
    );
    // Secondary sort by year for events on the same day.
    $this->query->addOrderBy(
      NULL,
      "YEAR($field)",
      $this->options['order'],
      'tdih_year_sort'
    );
  }

}
