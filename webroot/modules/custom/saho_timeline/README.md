# SAHO Timeline Module

## Overview
The SAHO Timeline module provides comprehensive timeline functionality for displaying historical events chronologically on the South African History Online website. It includes features for migrating existing timeline articles to the event content type and displaying them in various interactive timeline formats.

## Features

### Core Functionality
- **Interactive Timeline Display**: Multiple display modes (vertical, horizontal, compact, expanded)
- **Event Management**: Full CRUD operations for timeline events
- **Content Migration**: Tools to migrate timeline articles to event nodes
- **Filtering & Search**: Advanced filtering by date, category, and keywords
- **Responsive Design**: Mobile-friendly timeline displays
- **AJAX Loading**: Dynamic content loading without page refreshes

### Display Modes
1. **Default Timeline**: Vertical timeline with period grouping
2. **Compact View**: Condensed event display for overview
3. **Expanded View**: Detailed event information with images
4. **Horizontal Timeline**: Side-scrolling timeline layout

## Installation

1. Enable the module:
   ```bash
   ddev drush en saho_timeline -y
   ```

2. Clear caches:
   ```bash
   ddev drush cr
   ```

3. Configure permissions at `/admin/people/permissions#module-saho_timeline`

## Configuration

### Admin Settings
Navigate to `/admin/config/saho/timeline/settings` to configure:
- Default display mode
- Event grouping (year, decade, century)
- Number of events per page
- Filter options
- Date format preferences

### Block Configuration
Add the SAHO Timeline block to any region:
1. Go to `/admin/structure/block`
2. Click "Place block" in desired region
3. Search for "SAHO Timeline"
4. Configure block settings

## Usage

### Displaying the Timeline

#### As a Page
Visit `/timeline` to see the full timeline page.

#### As a Block
```php
// Programmatically render the timeline block
$block_manager = \Drupal::service('plugin.manager.block');
$config = [
  'display_mode' => 'default',
  'event_limit' => 20,
  'show_filters' => TRUE,
  'group_by' => 'decade',
];
$plugin_block = $block_manager->createInstance('saho_timeline_block', $config);
$render = $plugin_block->build();
```

#### In a Template
```twig
{# Render timeline with custom configuration #}
{{ {'#theme': 'saho_timeline', '#events': events, '#timeline_type': 'horizontal'}|render }}
```

### Content Migration

#### Automatic Migration
1. Navigate to `/admin/config/saho/timeline/migration`
2. Click "Identify Timeline Articles"
3. Review the articles marked for migration
4. Click "Migrate Selected Articles"

#### Manual Migration via Drush
```bash
# Identify timeline articles
ddev drush saho-timeline:identify

# Migrate specific articles
ddev drush saho-timeline:migrate 123,456,789

# Migrate all identified articles
ddev drush saho-timeline:migrate-all
```

#### Programmatic Migration
```php
$migration_service = \Drupal::service('saho_timeline.migration_service');

// Identify timeline articles
$articles = $migration_service->identifyTimelineArticles();

// Migrate a single article
$article = Node::load(123);
$event = $migration_service->migrateArticleToEvent($article);

// Batch migrate
$results = $migration_service->batchMigrateArticles([123, 456, 789]);
```

## API

### Services

#### TimelineEventService
Manages timeline events and provides query methods.

```php
$event_service = \Drupal::service('saho_timeline.event_service');

// Get events by date range
$events = $event_service->getEventsByDateRange('2020-01-01', '2020-12-31');

// Get events grouped by period
$grouped = $event_service->getEventsGroupedByPeriod('decade');

// Search events
$results = $event_service->searchEvents('apartheid', ['field_category' => 'political']);
```

#### TimelineMigrationService
Handles content migration from articles to events.

```php
$migration_service = \Drupal::service('saho_timeline.migration_service');

// Identify articles for migration
$articles = $migration_service->identifyTimelineArticles();

// Migrate articles
$results = $migration_service->batchMigrateArticles($article_ids);
```

### REST API Endpoints

#### Get Timeline Events
```
GET /api/timeline/events
```

Query parameters:
- `start_date`: Start date (YYYY-MM-DD)
- `end_date`: End date (YYYY-MM-DD)
- `category`: Event category
- `limit`: Number of events
- `offset`: Pagination offset

Example:
```javascript
fetch('/api/timeline/events?start_date=2020-01-01&category=political&limit=10')
  .then(response => response.json())
  .then(events => console.log(events));
```

## Theming

### Template Files
- `saho-timeline.html.twig`: Main timeline container
- `saho-timeline-event.html.twig`: Individual event display
- `saho-timeline-filters.html.twig`: Filter controls

### CSS Classes
```css
.saho-timeline                    /* Main container */
.saho-timeline--horizontal        /* Horizontal layout modifier */
.saho-timeline__period            /* Period container */
.saho-timeline-event              /* Event container */
.saho-timeline-event--compact     /* Compact event modifier */
.saho-timeline-event--visible     /* Visible state for animations */
```

### JavaScript API
```javascript
// Format a date
Drupal.sahoTimeline.formatDate('2020-01-01');

// Scroll to specific event
Drupal.sahoTimeline.scrollToEvent('event-123');

// Manually trigger filter update
jQuery('.saho-timeline').trigger('timeline:filter', {category: 'political'});
```

## Development

### Adding Custom Event Fields
1. Add fields to the event content type
2. Update the timeline templates to display new fields
3. Clear caches

### Extending the Module
```php
/**
 * Implements hook_saho_timeline_event_alter().
 */
function mymodule_saho_timeline_event_alter(&$event_data, $context) {
  // Modify event data before display
  $event_data['custom_field'] = 'value';
}
```

### Custom Display Modes
```php
/**
 * Implements hook_saho_timeline_display_modes_alter().
 */
function mymodule_saho_timeline_display_modes_alter(&$modes) {
  $modes['custom'] = t('Custom Timeline');
}
```

## Troubleshooting

### Events Not Displaying
1. Check that event nodes are published
2. Verify date field values are properly formatted
3. Clear Drupal caches: `ddev drush cr`

### Migration Issues
1. Check article identification criteria in settings
2. Verify field mappings are correct
3. Review migration logs at `/admin/reports/dblog`

### Performance Optimization
1. Enable caching for timeline blocks
2. Use pagination for large event sets
3. Consider using Views for complex queries

## Support
For issues or feature requests, please contact the SAHO development team or create an issue in the project repository.