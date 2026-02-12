# SAHO Upcoming Events Card Component

Specialized card component for displaying upcoming events with status badges, countdown indicators, and date/venue information.

## Features

- **Status Indicators**: Visual badges for "Happening Now", "Upcoming", and "Past Event" states
- **Countdown Display**: Shows "Today", "Tomorrow", or "In X days" for upcoming events
- **Pulse Animation**: Animated indicator for events happening now
- **Two Format Variants**:
  - **Portrait**: Tall image format (400px height) for inline blocks
  - **Standard**: 16:9 aspect ratio for page views
- **Responsive**: Adapts to all screen sizes
- **Self-Contained**: Uses inline CSS custom properties, no external SCSS dependencies

## Usage

### Basic Usage

```twig
{% include 'saho:saho-upcoming-events-card' with {
  title: 'Heritage Day Celebration',
  url: '/events/heritage-day-2026',
  image: {
    url: '/sites/default/files/events/heritage-day.jpg',
    alt: 'Heritage Day Celebration'
  },
  event_status: 'upcoming',
  event_status_label: 'In 3 days',
  event_badge_variant: 'primary',
  start_date: '24 September 2026',
  venue: 'Freedom Park, Pretoria',
  card_format: 'standard'
} %}
```

### With Status Badge Slot

```twig
{% embed 'saho:saho-upcoming-events-card' with {
  title: 'Womens Day March',
  url: '/events/womens-day-2026',
  event_status: 'in-progress',
  event_status_label: 'Happening Now',
  event_badge_variant: 'heritage-red',
  show_pulse: true,
  start_date: '9 August 2026',
  venue: 'Union Buildings',
  card_format: 'portrait'
} %}
  {% block status_badge %}
    {% include 'saho:saho-badge' with {
      text: event_status_label,
      variant: event_badge_variant,
      size: 'small',
      icon: 'circle',
      rounded: true,
      uppercase: true
    } %}
    {% if show_pulse %}
      <span class="badge-pulse" aria-hidden="true"></span>
    {% endif %}
  {% endblock %}
{% endembed %}
```

## Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `title` | string | Yes | - | Event title |
| `url` | string (uri) | Yes | - | Event URL |
| `image` | object | No | - | Image object with `url` and `alt` properties |
| `event_status` | string | No | `upcoming` | Event status: `in-progress`, `upcoming`, or `past` |
| `event_status_label` | string | No | - | Status label text (e.g., "Happening Now", "In 3 days") |
| `event_badge_variant` | string | No | `primary` | Badge color: `heritage-red`, `primary`, or `secondary` |
| `show_pulse` | boolean | No | `false` | Show pulse animation for "Happening Now" |
| `countdown` | string | No | - | Countdown text |
| `start_date` | string | No | - | Formatted start date |
| `end_date` | string | No | - | Formatted end date |
| `venue` | string | No | - | Event location |
| `body_excerpt` | string | No | - | Brief description |
| `card_format` | string | No | `standard` | Card format: `portrait` or `standard` |
| `attributes` | Attribute | No | - | Additional HTML attributes |

## Slots

### status_badge

Custom status badge content. Use this to include the `saho-badge` component with custom configuration.

### countdown_badge

Custom countdown badge content for upcoming events.

## Card Formats

### Portrait Format

- **Use Case**: Inline blocks on home page
- **Image Height**: 400px (300px on mobile)
- **Grid**: 3 columns on desktop, 2 on tablet, 1 on mobile

```twig
{% include 'saho:saho-upcoming-events-card' with {
  card_format: 'portrait',
  ...
} %}
```

### Standard Format

- **Use Case**: Page views (e.g., /all-upcoming-events)
- **Image Aspect Ratio**: 16:9
- **Grid**: Auto-fill with minmax(300px, 1fr)

```twig
{% include 'saho:saho-upcoming-events-card' with {
  card_format: 'standard',
  ...
} %}
```

## Event Status States

| Status | Trigger | Label Example | Badge Variant | Icon | Pulse |
|--------|---------|---------------|---------------|------|-------|
| **in-progress** | `start_date <= today && end_date >= today` | "Happening Now" | `heritage-red` | `circle` | Yes |
| **upcoming** | `start_date > today` | "Today" / "Tomorrow" / "In 3 days" | `primary` | `calendar-alt` | No |
| **past** | `end_date < today` | "Past Event" | `secondary` | `calendar-check` | No |

## Dependencies

- **saho-badge**: Status indicator badges
- **saho-button**: CTA button styling (via component CSS)
- **Bootstrap 5**: Card classes and utilities

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE 11 not supported (uses CSS custom properties)

## Accessibility

- Semantic HTML5 `<article>` element
- SVG icons with `aria-hidden="true"`
- Image lazy loading with meaningful alt text
- Keyboard navigable links
- WCAG 2.1 AA compliant color contrast

## Related Components

- **saho-badge**: Status indicators
- **saho-button**: Action buttons
- **saho-card**: Generic card component
- **saho-card-grid**: Grid layout for cards
