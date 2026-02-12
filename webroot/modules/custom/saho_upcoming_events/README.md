# SAHO Upcoming Events Module

This module provides a configurable block for displaying upcoming events on the SAHO website. The block is fully compatible with Layout Builder and can be inserted inline anywhere on the site.

## Features

- **Layout Builder Compatible**: Can be added as an inline block in Layout Builder
- **Configurable Display**: Multiple settings to customize the appearance and content
- **Responsive Design**: Mobile-friendly grid layout
- **Event Filtering**: Automatically shows only future events
- **Image Support**: Uses the configured image style for consistent display

## Block Configuration Options

### Basic Settings
- **Block Title**: Customize the title displayed above the events
- **Number of Events**: Choose how many events to display (1-20)

### Display Options
- **Show Event Images**: Toggle event image display on/off
- **Show Event Venue**: Toggle venue information display
- **Show Event Excerpt**: Toggle excerpt/description display
- **Excerpt Length**: Configure how many characters to show in excerpts (50-500)
- **Show "View All Events" Link**: Toggle the link to view all events

## Usage

### Adding to Layout Builder

1. Navigate to the page you want to edit
2. Click "Layout" tab
3. Click "Add block" in the desired section
4. Look for "SAHO Upcoming Events" in the "All custom" category
5. Configure the display options as needed
6. Save the layout

### Adding to Block Layout

1. Go to Structure → Block Layout
2. Click "Place block" in the desired region
3. Find "SAHO Upcoming Events" and click "Place block"
4. Configure the display options
5. Save the configuration

## Technical Details

### Dependencies
- Drupal Core: Node, Views, DateTime, Image modules
- Content Type: `upcomingevent` must exist with required fields

### Required Fields on Upcoming Event Content Type
- `field_start_date`: Event start date
- `field_upcomingevent_image`: Event image
- `field_upcoming_venue`: Event venue (optional)
- `body`: Event description (optional)

### CSS Classes
- `.saho-upcoming-events-block`: Main container
- `.upcoming-events-grid`: Grid container for event cards
- `.upcoming-event-card`: Individual event card
- `.event-image`, `.event-content`, `.event-meta`: Event components

### Cache Tags
The block automatically includes cache tags for `node_list:upcomingevent` with a 1-hour cache lifetime.

## Event Display Logic

### Date Handling
All date comparisons use Africa/Johannesburg (SAST) timezone for consistency with local event times.

### Event Categories
The block categorizes events into three types:

- **Happening Now**: Events where `start_date <= today AND end_date >= today`
  - These events are sorted first in all displays
  - Shows "Happening Now" badge in the block template
  - Includes multi-day events that started in the past but haven't ended yet

- **Upcoming**: Events where `start_date > today`
  - Shows countdown indicators ("Today", "Tomorrow", "In X days")
  - Sorted by start date after "happening now" events

- **Past**: Events where `end_date < today`
  - Only shown in the "All Events" page (collapsed by default)
  - Not included in the block display

### Single-Day Events
If `field_end_date` is empty, the event is treated as a single-day event using `field_start_date` as both the start and effective end date.

### Block Query Strategy
The block uses a two-phase approach to handle "happening now" events correctly:

1. **Database Query**: Retrieves all published events sorted by start date (no date filtering at query level)
2. **PHP Filtering**: Filters events to include only those where `effective_end_date >= today`
3. **Sorting**: Orders results with "happening now" events first, then upcoming events by start date

This strategy ensures that multi-day events (e.g., conferences that started yesterday but end tomorrow) appear correctly in the block as "happening now" rather than being excluded by a `start_date >= today` filter.

## Styling

The module includes comprehensive CSS styling that matches the SAHO brand colors and design patterns. The styling is responsive and includes hover effects for better user interaction.

### Brand Colors
- Primary: #97212d (SAHO Red)
- Secondary: #7a1b24 (Darker SAHO Red)
- Text: #333, #666, #555

## Installation

1. Place the module in `/modules/custom/saho_upcoming_events/`
2. Enable the module: `drush en saho_upcoming_events`
3. Clear cache: `drush cr`
4. The block will be available in Layout Builder and Block Layout

## Troubleshooting

**Block not showing events:**
- Verify upcoming events exist with future dates
- Check that events are published
- Ensure the `field_start_date` field is properly configured

**Styling issues:**
- Clear cache after enabling the module
- Verify the CSS library is loading correctly
- Check for CSS conflicts with theme styles