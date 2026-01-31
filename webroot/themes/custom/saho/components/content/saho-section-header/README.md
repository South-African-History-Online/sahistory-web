# SAHO Section Header Component

Reusable section header component with title, optional subtitle, and colored accent bar. Used for landing pages, views, and content sections throughout the SAHO theme.

## Usage

```twig
{% include 'saho:saho-section-header' with {
  title: 'Featured Articles',
  heading_level: 'h1',
  subtitle: 'Explore featured content from South African History Online',
  accent_bar: true,
  accent_color: 'heritage-red',
  alignment: 'left',
  size: 'large'
} %}
```

## Props

### title (required)
**Type:** `string`
The main heading text for the section.

**Examples:**
- `'Featured Articles'`
- `'More to Explore'`
- `'Archives'`

### heading_level
**Type:** `string`
**Default:** `'h2'`
**Options:** `h1`, `h2`, `h3`, `h4`, `h5`, `h6`
HTML heading tag to use for proper semantic structure and SEO.

**Examples:**
```twig
{# Page title #}
heading_level: 'h1'

{# Section title #}
heading_level: 'h2'

{# Subsection title #}
heading_level: 'h3'
```

### subtitle
**Type:** `string`
**Default:** `null`
Optional subtitle or description text below the title.

**Example:**
```twig
subtitle: 'Explore featured content from South African History Online'
```

### accent_bar
**Type:** `boolean`
**Default:** `true`
Display colored accent bar below title.

### accent_color
**Type:** `string`
**Default:** `'primary'`
**Options:** `primary`, `secondary`, `accent`, `highlight`, `heritage-red`, `muted-gold`, `slate-blue`
Color variant for the accent bar.

**Examples:**
```twig
{# SAHO heritage red #}
accent_color: 'heritage-red'

{# Gold for special sections #}
accent_color: 'muted-gold'

{# Primary brand color #}
accent_color: 'primary'
```

### alignment
**Type:** `string`
**Default:** `'left'`
**Options:** `left`, `center`, `right`
Horizontal alignment of the header.

**Examples:**
```twig
{# Centered header for hero sections #}
alignment: 'center'

{# Left-aligned for standard sections #}
alignment: 'left'
```

### size
**Type:** `string`
**Default:** `'medium'`
**Options:** `small`, `medium`, `large`, `xlarge`
Size variation for the header.

**Font Sizes:**
- `small`: 1.5rem (mobile: 1.25rem)
- `medium`: 2rem (mobile: 1.5rem)
- `large`: 2.5rem (mobile: 2rem)
- `xlarge`: 3rem (mobile: 2.25rem)

### margin_bottom
**Type:** `string`
**Default:** `'medium'`
**Options:** `none`, `small`, `medium`, `large`
Spacing below the header.

**Values:**
- `none`: 0
- `small`: 1rem
- `medium`: 2rem
- `large`: 3rem

### attributes
**Type:** `Drupal\Core\Template\Attribute`
Additional HTML attributes for the wrapper element.

**Example:**
```twig
attributes: create_attribute().addClass('my-custom-class').setAttribute('data-section', 'hero')
```

## Slots

### actions
Optional slot for action buttons or links displayed on the right side of the header.

**Example:**
```twig
{% include 'saho:saho-section-header' with {
  title: 'Recent Articles',
  heading_level: 'h2'
} %}
  {% block actions %}
    <a href="/articles" class="btn btn-primary">View All</a>
  {% endblock %}
{% endembed %}
```

## Examples

### Basic Header
```twig
{% include 'saho:saho-section-header' with {
  title: 'Featured Content'
} %}
```

### Hero Section Header
```twig
{% include 'saho:saho-section-header' with {
  title: 'South African History Online',
  heading_level: 'h1',
  subtitle: 'Preserving and sharing the rich history of South Africa',
  alignment: 'center',
  size: 'xlarge',
  accent_color: 'heritage-red'
} %}
```

### Section with Actions
```twig
{% embed 'saho:saho-section-header' with {
  title: 'Recent Biographies',
  heading_level: 'h2',
  size: 'large'
} %}
  {% block actions %}
    <a href="/biographies" class="saho-button">
      View All
    </a>
  {% endblock %}
{% endembed %}
```

### Without Accent Bar
```twig
{% include 'saho:saho-section-header' with {
  title: 'Search Results',
  heading_level: 'h1',
  accent_bar: false,
  margin_bottom: 'small'
} %}
```

### Landing Page Section
```twig
{% include 'saho:saho-section-header' with {
  title: 'Archives',
  heading_level: 'h2',
  subtitle: 'Browse our extensive collection of historical documents and resources',
  accent_color: 'slate-blue',
  size: 'medium',
  margin_bottom: 'large'
} %}
```

## Migration from Legacy Classes

**Before:**
```twig
<div class="saho-section-title-wrapper">
  <h1 class="saho-section-title">Featured Articles</h1>
  <div class="saho-section-title-accent saho-bg-deep-heritage-red"></div>
</div>
<p class="lead">Explore our curated content</p>
```

**After:**
```twig
{% include 'saho:saho-section-header' with {
  title: 'Featured Articles',
  heading_level: 'h1',
  subtitle: 'Explore our curated content',
  accent_color: 'heritage-red'
} %}
```

## Accessibility

- Uses semantic HTML heading tags (h1-h6)
- Proper heading hierarchy for screen readers
- Color contrast meets WCAG AA standards
- Focus styles for keyboard navigation
- Print styles for document generation

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design (mobile-first)
- Graceful degradation for older browsers

## Related Components

- `saho-button` - For action buttons in the actions slot
- `saho-badge` - For category/type indicators
- `saho-card` - Often used below section headers
