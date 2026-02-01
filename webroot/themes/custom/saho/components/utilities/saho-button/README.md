# SAHO Button Component

**Unified button component for consistent user interactions across the SAHO website.**

## Purpose

This component replaces three separate button systems that previously existed in the theme:
1. Bootstrap buttons (`btn btn-primary`, `btn btn-outline-secondary`)
2. Card buttons (`.saho-card-button`)
3. Block buttons (`.block-button`, `.block-button-primary`)

By consolidating these into a single component, we ensure:
- Consistent visual appearance
- Reduced code duplication
- Easier maintenance
- Better accessibility

## Usage

### Basic Button

```twig
{% include 'saho:saho-button' with {
  text: 'Read More',
  url: '/article/example'
} %}
```

### Button Variants

#### Primary (Default) - Filled Red Button
```twig
{% include 'saho:saho-button' with {
  text: 'Learn More',
  url: '/about',
  variant: 'primary'
} %}
```

#### Secondary - Outlined Red Button
```twig
{% include 'saho:saho-button' with {
  text: 'View All',
  url: '/archive',
  variant: 'secondary'
} %}
```

#### Card Action - For Use in Cards
```twig
{% include 'saho:saho-button' with {
  text: 'Explore',
  url: entity_url,
  variant: 'card-action',
  icon: 'arrow-right'
} %}
```

#### Block Action - For Block Sections
```twig
{% include 'saho:saho-button' with {
  text: 'Discover More',
  url: '/collections',
  variant: 'block-action'
} %}
```

#### Link Style - Text Link with Icon
```twig
{% include 'saho:saho-button' with {
  text: 'Download PDF',
  url: '/files/report.pdf',
  variant: 'link',
  icon: 'download'
} %}
```

### Button Sizes

```twig
{# Small button #}
{% include 'saho:saho-button' with {
  text: 'View',
  url: '#',
  size: 'small'
} %}

{# Medium button (default) #}
{% include 'saho:saho-button' with {
  text: 'Read More',
  url: '#',
  size: 'medium'
} %}

{# Large button #}
{% include 'saho:saho-button' with {
  text: 'Get Started',
  url: '#',
  size: 'large'
} %}
```

### Buttons with Icons

#### Icon After Text (Default)
```twig
{% include 'saho:saho-button' with {
  text: 'Continue Reading',
  url: '/article',
  icon: 'arrow-right'
} %}
```

#### Icon Before Text
```twig
{% include 'saho:saho-button' with {
  text: 'Download',
  url: '/files/document.pdf',
  icon: 'download',
  icon_position: 'before'
} %}
```

#### Available Icons
- `arrow-right` - Right arrow (default for navigation)
- `external` - External link icon
- `download` - Download icon

### External Links

```twig
{% include 'saho:saho-button' with {
  text: 'Visit External Site',
  url: 'https://example.com',
  icon: 'external',
  attributes: {
    target: '_blank',
    rel: 'noopener noreferrer'
  }
} %}
```

### Button Elements (Not Links)

```twig
{% include 'saho:saho-button' with {
  text: 'Submit',
  element: 'button',
  attributes: {
    id: 'submit-button',
    'aria-label': 'Submit form'
  }
} %}
```

### Additional Attributes

```twig
{% include 'saho:saho-button' with {
  text: 'Special Button',
  url: '/page',
  attributes: {
    class: ['custom-class', 'another-class'],
    id: 'my-button',
    'data-analytics': 'click-event',
    'aria-label': 'Descriptive label for screen readers'
  }
} %}
```

## Migration Examples

### From Bootstrap Buttons

**Before:**
```twig
<a href="{{ url }}" class="btn btn-primary">Read More</a>
<a href="{{ url }}" class="btn btn-outline-secondary">View All</a>
```

**After:**
```twig
{% include 'saho:saho-button' with {
  text: 'Read More',
  url: url,
  variant: 'primary'
} %}

{% include 'saho:saho-button' with {
  text: 'View All',
  url: url,
  variant: 'secondary'
} %}
```

### From Card Buttons

**Before:**
```twig
<a href="{{ url }}" class="saho-card-button">
  Explore
  <svg>...</svg>
</a>
```

**After:**
```twig
{% include 'saho:saho-button' with {
  text: 'Explore',
  url: url,
  variant: 'card-action',
  icon: 'arrow-right'
} %}
```

### From Block Buttons

**Before:**
```twig
<a href="{{ url }}" class="block-button-primary">Learn More</a>
<a href="{{ url }}" class="block-button-secondary">View All</a>
```

**After:**
```twig
{% include 'saho:saho-button' with {
  text: 'Learn More',
  url: url,
  variant: 'block-action'
} %}

{% include 'saho:saho-button' with {
  text: 'View All',
  url: url,
  variant: 'secondary'
} %}
```

## Props Reference

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `text` | string | Yes | - | Button text content |
| `url` | string | No | `#` | Destination URL |
| `variant` | string | No | `primary` | Visual style: `primary`, `secondary`, `outline`, `card-action`, `block-action`, `link` |
| `size` | string | No | `medium` | Size: `small`, `medium`, `large` |
| `icon` | string | No | - | Icon name: `arrow-right`, `external`, `download` |
| `icon_position` | string | No | `after` | Icon position: `before`, `after` |
| `element` | string | No | `a` | HTML element: `a`, `button` |
| `attributes` | object | No | - | Additional HTML attributes |

## Accessibility Features

- **Keyboard navigation**: All buttons are fully keyboard accessible
- **Focus indicators**: Clear focus states with visible outlines
- **Screen reader support**: Proper ARIA labels and semantic HTML
- **High contrast mode**: Enhanced borders in high contrast mode
- **Reduced motion**: Respects `prefers-reduced-motion` preference
- **External link security**: Auto-adds `rel="noopener noreferrer"` for `target="_blank"`

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile Safari (iOS 12+)
- Chrome Mobile (Android 5+)

## Notes

- The component automatically handles z-index layering when used inside cards with stretched links
- Icons are inline SVG for performance and accessibility
- All transitions respect user motion preferences
- Color values use SAHO heritage color tokens
