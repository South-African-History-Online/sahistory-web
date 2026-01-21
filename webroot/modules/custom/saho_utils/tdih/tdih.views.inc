<?php

/**
 * @file
 * Views integration for TDIH module.
 */

/**
 * Implements hook_views_data_alter().
 */
function tdih_views_data_alter(array &$data) {
  // Add custom TDIH date range filter to the event date field.
  if (isset($data['node__field_event_date'])) {
    $data['node__field_event_date']['tdih_date_range'] = [
      'title' => t('TDIH Date Range (Month/Day)'),
      'help' => t('Filter by month and day range, ignoring the year. Useful for This Day in History exports.'),
      'filter' => [
        'id' => 'tdih_date_range',
        'field' => 'field_event_date_value',
      ],
    ];

    // Add custom TDIH date sort (by month/day, ignoring year).
    $data['node__field_event_date']['tdih_date_sort'] = [
      'title' => t('TDIH Date Sort (Month/Day)'),
      'help' => t('Sort by month and day, ignoring the year. Groups events by day-of-year.'),
      'sort' => [
        'id' => 'tdih_date_sort',
        'field' => 'field_event_date_value',
      ],
    ];
  }
}
