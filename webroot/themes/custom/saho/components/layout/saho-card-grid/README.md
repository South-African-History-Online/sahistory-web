# SAHO Card Grid Component

**Responsive grid layout component for displaying collections of cards.**

## Purpose

This layout component provides a consistent, responsive grid system for displaying multiple saho-card components. It automatically handles responsive behavior, spacing, and alignment across different screen sizes.

## Basic Usage

```twig
{% embed 'saho:saho-card-grid' %}
  {% block default %}
    {% for item in items %}
      {% include 'saho:saho-card' with {
        title: item.title,
        content: item.summary,
        url: item.url,
        image: { url: item.image_url, alt: item.image_alt }
      } %}
    {% endfor %}
  {% endblock %}
{% endembed %}
```

## Auto-Fill Grid (Default)

The default configuration automatically fills columns based on available space:

```twig
{% embed 'saho:saho-card-grid' with {
  columns: 'auto',
  min_card_width: '300px'
} %}
  {% block default %}
    {# Cards here #}
  {% endblock %}
{% endembed %}
```

## Fixed Column Layouts

### Two Columns

```twig
{% embed 'saho:saho-card-grid' with {
  columns: 'two'
} %}
  {% block default %}
    {% include 'saho:saho-card' with {
      title: 'Card 1',
      url: '#'
    } %}
    {% include 'saho:saho-card' with {
      title: 'Card 2',
      url: '#'
    } %}
  {% endblock %}
{% endembed %}
```

### Three Columns

```twig
{% embed 'saho:saho-card-grid' with {
  columns: 'three',
  gap: 'normal'
} %}
  {% block default %}
    {# 3 cards per row on desktop, responsive on mobile #}
  {% endblock %}
{% endembed %}
```

### Four Columns

```twig
{% embed 'saho:saho-card-grid' with {
  columns: 'four',
  gap: 'compact'
} %}
  {% block default %}
    {# 4 cards per row on large screens, responsive #}
  {% endblock %}
{% endembed %}
```

## Grid Spacing

### Compact Spacing

```twig
{% embed 'saho:saho-card-grid' with {
  gap: 'compact'
} %}
  {% block default %}
    {# Cards with 1rem gap #}
  {% endblock %}
{% endembed %}
```

### Normal Spacing (Default)

```twig
{% embed 'saho:saho-card-grid' with {
  gap: 'normal'
} %}
  {% block default %}
    {# Cards with 1.5rem gap #}
  {% endblock %}
{% endembed %}
```

### Relaxed Spacing

```twig
{% embed 'saho:saho-card-grid' with {
  gap: 'relaxed'
} %}
  {% block default %}
    {# Cards with 2rem gap #}
  {% endblock %}
{% endembed %}
```

## Grid Alignment

### Start Alignment (Default)

```twig
{% embed 'saho:saho-card-grid' with {
  align: 'start'
} %}
  {% block default %}
    {# Cards aligned to the left #}
  {% endblock %}
{% endembed %}
```

### Center Alignment

```twig
{% embed 'saho:saho-card-grid' with {
  align: 'center'
} %}
  {% block default %}
    {# Cards centered in their grid cells #}
  {% endblock %}
{% endembed %}
```

### End Alignment

```twig
{% embed 'saho:saho-card-grid' with {
  align: 'end'
} %}
  {% block default %}
    {# Cards aligned to the right #}
  {% endblock %}
{% endembed %}
```

## Custom Minimum Width

Control the minimum card width for auto-fill grids:

```twig
{% embed 'saho:saho-card-grid' with {
  columns: 'auto',
  min_card_width: '280px'
} %}
  {% block default %}
    {# Cards will be at least 280px wide #}
  {% endblock %}
{% endembed %}
```

## Additional Attributes

### Custom CSS Classes

```twig
{% embed 'saho:saho-card-grid' with {
  attributes: {
    class: ['custom-grid', 'featured-items'],
    id: 'featured-grid'
  }
} %}
  {% block default %}
    {# Cards here #}
  {% endblock %}
{% endembed %}
```

## Complete Example

```twig
{% embed 'saho:saho-card-grid' with {
  columns: 'three',
  gap: 'normal',
  align: 'start',
  attributes: {
    class: ['biography-grid'],
    id: 'biographies'
  }
} %}
  {% block default %}
    {% for biography in biographies %}
      {% include 'saho:saho-card' with {
        title: biography.name,
        content: biography.summary,
        image: {
          url: biography.portrait_url,
          alt: biography.name ~ ' portrait'
        },
        url: biography.url,
        content_type: 'biography',
        button_text: 'Read Biography',
        button_variant: 'card-action',
        metadata: {
          date: biography.birth_date ~ ' - ' ~ biography.death_date,
          category: 'Activists',
          staff_pick: biography.is_featured
        }
      } %}
    {% endfor %}
  {% endblock %}
{% endembed %}
```

## Props Reference

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `columns` | string | No | `auto` | Column configuration: `auto`, `one`, `two`, `three`, `four` |
| `gap` | string | No | `normal` | Spacing between cards: `compact`, `normal`, `relaxed` |
| `min_card_width` | string | No | `300px` | Minimum card width for auto-fill grids |
| `align` | string | No | `start` | Grid alignment: `start`, `center`, `end` |
| `attributes` | object | No | - | Additional HTML attributes (class, id) |

## Responsive Behavior

### Auto-Fill Grid (`columns: 'auto'`)
- Automatically creates as many columns as will fit
- Based on `min_card_width` setting
- Fully responsive without breakpoints

### Fixed Column Grids

**One Column** (`columns: 'one'`)
- 1 column on all screen sizes

**Two Columns** (`columns: 'two'`)
- Mobile (< 768px): 1 column
- Tablet/Desktop (≥ 768px): 2 columns

**Three Columns** (`columns: 'three'`)
- Mobile (< 768px): 1 column
- Tablet (768px - 991px): 2 columns
- Desktop (≥ 992px): 3 columns

**Four Columns** (`columns: 'four'`)
- Mobile (< 768px): 1 column
- Tablet (768px - 991px): 2 columns
- Desktop (992px - 1199px): 3 columns
- Large Desktop (≥ 1200px): 4 columns

## Gap Sizes by Breakpoint

### Desktop
- Compact: 1rem
- Normal: 1.5rem
- Relaxed: 2rem

### Mobile (< 768px)
- Compact: 0.875rem
- Normal: 1rem
- Relaxed: 1.25rem

## Features

- **Auto-responsive**: No JavaScript required
- **Flexible spacing**: Three gap size options
- **Smart alignment**: Control horizontal positioning
- **Drupal-friendly**: Handles Views wrapper divs
- **Print-optimized**: Cards stack in print mode
- **Accessibility**: Respects reduced motion preferences
- **Equal heights**: Cards in same row match height
- **Single card handling**: Constrains width on large screens

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile Safari (iOS 12+)
- Chrome Mobile (Android 5+)

## Notes

- Grid uses CSS Grid for modern, performant layout
- Automatically handles Drupal Views wrapper `<div>` elements
- Cards maintain equal heights within rows
- Single cards are constrained to 450px max width on desktop
- Mobile padding applied automatically for proper spacing
- Print-friendly with page-break-inside avoidance
