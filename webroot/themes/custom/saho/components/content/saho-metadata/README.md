# SAHO Metadata Component

Content metadata display component for showing date, author, category, and other meta information with icons and optional links. Used on cards, article headers, and throughout the site.

## Usage

```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'calendar', value: '31 January 2026'},
    {icon: 'user', label: 'By', value: 'John Doe', url: '/author/john-doe'},
    {icon: 'folder', value: 'Biography', url: '/category/biography'}
  ]
} %}
```

## Props

### items (required)
**Type:** `array`
Array of metadata items to display.

**Each item can have:**
- `icon` (string): Font Awesome icon name (without `fa-` prefix)
- `label` (string): Optional label before value (e.g., "By", "In")
- `value` (string): The metadata value to display
- `url` (string): Optional URL to make value clickable

**Examples:**
```twig
items: [
  {icon: 'calendar', value: post_date},
  {icon: 'user', label: 'By', value: author_name, url: author_url},
  {icon: 'tag', value: 'Politics & Society', url: '/category/politics'}
]
```

### separator
**Type:** `string`
**Default:** `'•'`
**Options:** `•`, `|`, `·`, `-`, `,`, `''` (empty)

Separator character displayed between metadata items.

**Examples:**
```twig
{# Bullet separator (default) #}
separator: '•'

{# Pipe separator #}
separator: '|'

{# No separator #}
separator: ''
```

### layout
**Type:** `string`
**Default:** `'inline'`
**Options:** `inline`, `stacked`, `pills`

Display layout for metadata items.

**Examples:**
```twig
{# Inline with separators #}
layout: 'inline'

{# Vertical stack (no separators) #}
layout: 'stacked'

{# Pill-shaped items (no separators) #}
layout: 'pills'
```

### size
**Type:** `string`
**Default:** `'medium'`
**Options:** `small`, `medium`, `large`

**Examples:**
```twig
{# Small text for card footers #}
size: 'small'

{# Large text for article headers #}
size: 'large'
```

### color
**Type:** `string`
**Default:** `'muted'`
**Options:** `muted`, `primary`, `dark`

**Examples:**
```twig
{# Muted gray (default for cards) #}
color: 'muted'

{# Primary brand color #}
color: 'primary'
```

### icon_position
**Type:** `string`
**Default:** `'before'`
**Options:** `before`, `after`, `none`

Position of icons relative to text.

## Examples

### Basic Card Metadata
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'calendar', value: '31 January 2026'},
    {icon: 'user', value: 'SAHO Team'}
  ],
  size: 'small',
  color: 'muted'
} %}
```

### Article Header with Links
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'calendar', value: node.created.value|date('j F Y')},
    {icon: 'user', label: 'By', value: author, url: author_url},
    {icon: 'folder', value: category, url: category_url}
  ],
  size: 'medium',
  separator: '|'
} %}
```

### Pills Layout
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'tag', value: 'Biography'},
    {icon: 'star', value: 'Featured'},
    {icon: 'bookmark', value: 'Staff Pick'}
  ],
  layout: 'pills',
  color: 'primary'
} %}
```

### Stacked Layout
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'calendar', label: 'Published', value: '31 January 2026'},
    {icon: 'user', label: 'Author', value: 'John Doe'},
    {icon: 'folder', label: 'Category', value: 'Politics'}
  ],
  layout: 'stacked',
  size: 'small'
} %}
```

### No Icons
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {value: '31 January 2026'},
    {value: 'John Doe'},
    {value: 'Politics & Society'}
  ],
  icon_position: 'none',
  separator: ' | '
} %}
```

### Complex Example with All Features
```twig
{% set metadata_items = [] %}

{# Add date #}
{% if node.created %}
  {% set metadata_items = metadata_items|merge([
    {icon: 'calendar', value: node.created.value|date('j F Y')}
  ]) %}
{% endif %}

{# Add author with link #}
{% if author %}
  {% set metadata_items = metadata_items|merge([
    {icon: 'user', label: 'By', value: author, url: '/author/' ~ author|lower}
  ]) %}
{% endif %}

{# Add category with link #}
{% if category %}
  {% set metadata_items = metadata_items|merge([
    {icon: 'folder', value: category, url: '/category/' ~ category|lower}
  ]) %}
{% endif %}

{# Display metadata #}
{% include 'saho:saho-metadata' with {
  items: metadata_items,
  size: 'small',
  color: 'muted'
} %}
```

## Common Use Cases

### On saho-card
```twig
<div class="saho-card-content">
  <h3 class="saho-card-title">{{ title }}</h3>

  {% include 'saho:saho-metadata' with {
    items: [
      {icon: 'calendar', value: date},
      {icon: 'user', value: author}
    ],
    size: 'small',
    color: 'muted'
  } %}

  <p class="saho-card-description">{{ description }}</p>
</div>
```

### Article Header
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'calendar', value: published_date},
    {icon: 'clock', value: reading_time ~ ' min read'},
    {icon: 'user', label: 'By', value: author, url: author_url},
    {icon: 'folder', value: category, url: category_url}
  ],
  size: 'medium',
  separator: '•'
} %}
```

### Biography Metadata
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'calendar', label: 'Born', value: birth_date},
    {icon: 'calendar', label: 'Died', value: death_date},
    {icon: 'map-marker', label: 'Place', value: birth_place}
  ],
  layout: 'stacked',
  size: 'small'
} %}
```

## Migration from Legacy

**Before:**
```twig
<div class="saho-card__meta">
  <span><i class="fas fa-calendar"></i> {{ date }}</span>
  •
  <span><i class="fas fa-user"></i> {{ author }}</span>
</div>
```

**After:**
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'calendar', value: date},
    {icon: 'user', value: author}
  ]
} %}
```

## Accessibility

- Proper semantic HTML
- Icons marked with `aria-hidden="true"`
- Focus styles for links
- Screen reader friendly
- Keyboard accessible

## Styling Notes

- Uses CSS custom properties
- Inherits from SAHO design system
- Responsive font sizing
- Print-friendly styles
- Smooth transitions

## Browser Support

- Modern browsers
- Responsive design
- Graceful degradation

## Related Components

- `saho-badge` - For category/type indicators
- `saho-card` - Often contains metadata
- `saho-section-header` - Headers with metadata
