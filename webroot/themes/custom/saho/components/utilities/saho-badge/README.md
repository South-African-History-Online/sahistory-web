# SAHO Badge Component

Small colored label component for content types, categories, status indicators, and tags. Used on cards, headers, and throughout the site for visual categorization.

## Usage

```twig
{% include 'saho:saho-badge' with {
  text: 'Biography',
  variant: 'heritage-red',
  size: 'small',
  icon: 'user'
} %}
```

## Props

### text (required)
**Type:** `string`
The text content displayed in the badge.

**Examples:**
- `'Biography'`
- `'Featured'`
- `'New'`
- `'Archive'`

### variant
**Type:** `string`
**Default:** `'primary'`
**Options:** `primary`, `secondary`, `accent`, `highlight`, `success`, `warning`, `danger`, `info`, `light`, `dark`, `heritage-red`, `muted-gold`, `slate-blue`

Color variant of the badge.

**Examples:**
```twig
{# SAHO heritage red for important items #}
variant: 'heritage-red'

{# Success green for published content #}
variant: 'success'

{# Warning amber for drafts #}
variant: 'warning'
```

### size
**Type:** `string`
**Default:** `'medium'`
**Options:** `small`, `medium`, `large`

**Examples:**
```twig
{# Small badge on cards #}
size: 'small'

{# Large badge for prominent features #}
size: 'large'
```

### icon
**Type:** `string`
**Default:** `null`

Font Awesome icon name (without `fa-` prefix).

**Examples:**
```twig
{# Star icon for featured items #}
icon: 'star'

{# User icon for biographies #}
icon: 'user'

{# Tag icon for categories #}
icon: 'tag'
```

### rounded
**Type:** `boolean`
**Default:** `false`

Use pill-shape (fully rounded) instead of slightly rounded corners.

**Examples:**
```twig
{# Pill-shaped badge #}
rounded: true
```

### uppercase
**Type:** `boolean`
**Default:** `true`

Transform text to uppercase with letter-spacing.

### position
**Type:** `string`
**Default:** `'inline'`
**Options:** `inline`, `top-left`, `top-right`, `bottom-left`, `bottom-right`

Position variant when used as overlay on images (like on cards).

**Examples:**
```twig
{# Overlay on card image #}
position: 'top-right'

{# Inline with text #}
position: 'inline'
```

### url
**Type:** `string`
**Default:** `null`

Optional URL to make badge clickable.

**Example:**
```twig
url: '/category/biography'
```

## Examples

### Content Type Badge on Card
```twig
{% include 'saho:saho-badge' with {
  text: 'Biography',
  variant: 'heritage-red',
  size: 'small',
  position: 'top-right'
} %}
```

### Featured Badge with Icon
```twig
{% include 'saho:saho-badge' with {
  text: 'Featured',
  variant: 'success',
  icon: 'star',
  rounded: true
} %}
```

### Clickable Category Badge
```twig
{% include 'saho:saho-badge' with {
  text: 'Politics & Society',
  variant: 'slate-blue',
  url: '/category/politics',
  size: 'medium'
} %}
```

### Status Indicator
```twig
{% include 'saho:saho-badge' with {
  text: 'New',
  variant: 'info',
  size: 'small',
  icon: 'bolt'
} %}
```

### Staff Pick
```twig
{% include 'saho:saho-badge' with {
  text: 'Staff Pick',
  variant: 'warning',
  icon: 'bookmark',
  uppercase: true
} %}
```

### Multiple Badges
```twig
<div class="badges-group">
  {% include 'saho:saho-badge' with {
    text: 'Biography',
    variant: 'heritage-red',
    size: 'small'
  } %}

  {% include 'saho:saho-badge' with {
    text: 'Featured',
    variant: 'success',
    size: 'small',
    icon: 'star'
  } %}
</div>
```

## Color Variants Reference

| Variant | Use Case | Text Color |
|---------|----------|------------|
| `primary` | Default, general purpose | White |
| `secondary` | Alternate style | White |
| `accent` | Highlight items | White |
| `highlight` | Important items | White |
| `success` | Published, approved | White |
| `warning` | Drafts, pending | Dark |
| `danger` | Errors, unpublished | White |
| `info` | Informational | White |
| `light` | Subtle labels | Dark |
| `dark` | High contrast | White |
| `heritage-red` | SAHO brand (default for content types) | White |
| `muted-gold` | Special collections | Dark |
| `slate-blue` | Categories | White |

## Migration from Legacy

**Before:**
```twig
<div class="saho-card-badge">{{ content_type }}</div>
```

**After:**
```twig
{% include 'saho:saho-badge' with {
  text: content_type,
  variant: 'heritage-red',
  position: 'top-right',
  size: 'small'
} %}
```

## Accessibility

- Proper color contrast ratios (WCAG AA compliant)
- Focus styles for clickable badges
- Icons marked with `aria-hidden="true"`
- Screen reader friendly text
- Keyboard accessible when used as links

## Styling Notes

- Uses CSS custom properties for colors
- Inherits from SAHO design system
- Responsive sizing on mobile
- Print-friendly styles
- Smooth transitions on hover

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Graceful degradation
- Print styles included

## Related Components

- `saho-card` - Often contains badges
- `saho-section-header` - Can use badges in titles
- `saho-metadata` - Badges work well with metadata displays
