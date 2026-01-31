# SAHO Component Library

Single Directory Components (SDC) for the South African History Online theme. All components follow Drupal 11 SDC specification with schema validation, Twig templates, isolated CSS, and comprehensive documentation.

## Component Architecture

### Organization

Components are organized by function:

```
components/
├── content/         # Content display components
│   ├── saho-card/
│   ├── saho-metadata/
│   └── saho-section-header/
├── layout/          # Layout and structure components
│   └── saho-card-grid/
├── media/           # Media handling components
│   └── saho-image/
└── utilities/       # Utility components
    ├── saho-badge/
    └── saho-button/
```

### Component Structure

Each component includes:
- `[name].component.yml` - Schema with prop definitions
- `[name].twig` - Twig template
- `[name].css` - Component-specific styles
- `README.md` - Usage documentation and examples

## Available Components

### Content Components

#### saho-card
**Location**: `components/content/saho-card/`
**Description**: Primary card component for content display with image, title, content, metadata, and call-to-action button. Used extensively across the site for article cards, biography cards, and resource listings.

**Key Props**:
- `title` (required): Card title
- `image`: Image URL with alt text
- `content`: Card description/synopsis
- `url`: Card destination link
- `content_type`: Badge text (e.g., "Biography", "Article")
- `metadata`: Date and author information
- `button_text`: CTA button text
- `variant`: Style variant (default, featured, compact)

**Usage**:
```twig
{% include 'saho:saho-card' with {
  title: 'Nelson Mandela',
  image: {url: '/path/to/image.jpg', alt: 'Portrait'},
  content: 'South African anti-apartheid revolutionary...',
  url: '/node/123',
  content_type: 'Biography',
  button_text: 'Read More'
} %}
```

**Documentation**: [saho-card/README.md](content/saho-card/README.md)

---

#### saho-metadata
**Location**: `components/content/saho-metadata/`
**Description**: Displays content metadata (date, author, category) with icons, labels, and optional links. Supports inline, stacked, and pill layouts.

**Key Props**:
- `items` (required): Array of metadata items
- `separator`: Separator character (•, |, -, etc.)
- `layout`: Display layout (inline, stacked, pills)
- `size`: Text size (small, medium, large)
- `color`: Color variant (muted, primary, dark)

**Usage**:
```twig
{% include 'saho:saho-metadata' with {
  items: [
    {icon: 'calendar', value: '31 January 2026'},
    {icon: 'user', label: 'By', value: 'SAHO Team', url: '/author/saho'},
    {icon: 'folder', value: 'Biography', url: '/category/biography'}
  ],
  size: 'small'
} %}
```

**Documentation**: [saho-metadata/README.md](content/saho-metadata/README.md)

---

#### saho-section-header
**Location**: `components/content/saho-section-header/`
**Description**: Section header with title, optional subtitle, colored accent bars, and action slot. Used for page sections, view headers, and content groupings.

**Key Props**:
- `title` (required): Section heading text
- `heading_level`: Semantic heading level (h1-h6)
- `subtitle`: Optional subtitle text
- `accent_bar`: Show colored accent bar
- `accent_color`: Accent bar color variant
- `alignment`: Text alignment (left, center, right)

**Usage**:
```twig
{% include 'saho:saho-section-header' with {
  title: 'Featured Biographies',
  heading_level: 'h2',
  accent_bar: true,
  accent_color: 'heritage-red',
  alignment: 'center'
} %}
```

**Documentation**: [saho-section-header/README.md](content/saho-section-header/README.md)

---

### Layout Components

#### saho-card-grid
**Location**: `components/layout/saho-card-grid/`
**Description**: Responsive grid layout wrapper for cards. Handles 2-3-4 column responsive breakpoints with consistent gap and alignment.

**Key Props**:
- `columns`: Number of columns (2, 3, 4, auto)
- `gap`: Spacing between items (small, medium, large)
- `alignment`: Vertical alignment (stretch, start, center)

**Usage**:
```twig
{% include 'saho:saho-card-grid' with {
  columns: '3',
  gap: 'medium'
} %}
  {% for item in items %}
    {% include 'saho:saho-card' with item %}
  {% endfor %}
{% endinclude %}
```

**Documentation**: [saho-card-grid/README.md](layout/saho-card-grid/README.md)

---

### Media Components

#### saho-image
**Location**: `components/media/saho-image/`
**Description**: Responsive image component with lazy loading, srcset, aspect ratios, captions, and performance optimizations. Prevents layout shift and improves Core Web Vitals.

**Key Props**:
- `src` (required): Image URL
- `alt`: Alt text for accessibility
- `srcset`: Responsive image sources
- `sizes`: Media queries for srcset
- `aspect_ratio`: Image aspect ratio (16/9, 4/3, 1/1, etc.)
- `lazy`: Enable lazy loading (default: true)
- `caption`: Optional caption text
- `link`: Make image clickable

**Usage**:
```twig
{% include 'saho:saho-image' with {
  src: '/sites/default/files/hero.jpg',
  alt: 'South African historical photograph',
  aspect_ratio: '16/9',
  lazy: true,
  caption: 'Photo credit: Historical Archives'
} %}
```

**Documentation**: [saho-image/README.md](media/saho-image/README.md)

---

### Utility Components

#### saho-badge
**Location**: `components/utilities/saho-badge/`
**Description**: Small colored label for content types, categories, status indicators, and tags. Supports 13 color variants, optional icons, and positioning modes.

**Key Props**:
- `text` (required): Badge text
- `variant`: Color variant (primary, heritage-red, success, warning, etc.)
- `size`: Badge size (small, medium, large)
- `icon`: Optional Font Awesome icon
- `rounded`: Pill-shape (fully rounded)
- `position`: Position on parent (inline, top-right, etc.)

**Usage**:
```twig
{% include 'saho:saho-badge' with {
  text: 'Biography',
  variant: 'heritage-red',
  size: 'small',
  position: 'top-right'
} %}
```

**Documentation**: [saho-badge/README.md](utilities/saho-badge/README.md)

---

#### saho-button
**Location**: `components/utilities/saho-button/`
**Description**: Unified button component for all button and CTA link use cases. Supports multiple variants, sizes, icons, and states.

**Key Props**:
- `text` (required): Button text
- `url`: Button destination (creates link, otherwise button element)
- `variant`: Style variant (primary, secondary, outline, card-action)
- `size`: Button size (small, medium, large)
- `icon`: Optional icon name
- `icon_position`: Icon position (before, after)

**Usage**:
```twig
{% include 'saho:saho-button' with {
  text: 'Read More',
  url: '/node/123',
  variant: 'card-action',
  icon: 'arrow-right',
  icon_position: 'after'
} %}
```

**Documentation**: [saho-button/README.md](utilities/saho-button/README.md)

---

## Design System

### Color Palette

#### SAHO Heritage Colors
```scss
--saho-deep-heritage-red: #8B1513;      // Primary brand color
--saho-muted-heritage-red: #A64D4D;     // Lighter heritage red
--saho-heritage-gold: #C4975F;           // Muted gold accent
--saho-slate-blue: #4A6FA5;             // Cool blue accent
```

#### Semantic Colors
```scss
--saho-primary: var(--saho-deep-heritage-red);
--saho-secondary: #6c757d;
--saho-success: #28a745;
--saho-warning: #ffc107;
--saho-danger: #dc3545;
--saho-info: #17a2b8;
```

#### Text Colors
```scss
--saho-text: #212529;
--saho-text-muted: #6c757d;
--saho-text-light: #f8f9fa;
```

#### Background Colors
```scss
--saho-background: #fff;
--saho-background-muted: #f8f9fa;
--saho-border-light: rgba(0, 0, 0, 0.1);
```

### Typography

#### Headings
- h1: 2.5rem (40px) - Page titles
- h2: 2rem (32px) - Section headers
- h3: 1.75rem (28px) - Subsection headers
- h4: 1.5rem (24px) - Card titles
- h5: 1.25rem (20px) - Metadata labels
- h6: 1rem (16px) - Small headings

#### Body Text
- Base: 1rem (16px)
- Small: 0.875rem (14px)
- Tiny: 0.75rem (12px)

### Spacing Scale

```scss
--saho-spacing-xs: 0.25rem;  // 4px
--saho-spacing-sm: 0.5rem;   // 8px
--saho-spacing-md: 1rem;     // 16px
--saho-spacing-lg: 1.5rem;   // 24px
--saho-spacing-xl: 2rem;     // 32px
--saho-spacing-2xl: 3rem;    // 48px
```

### Border Radius

```scss
--saho-radius-sm: 4px;
--saho-radius-md: 8px;
--saho-radius-lg: 16px;
--saho-radius-full: 999px;  // Pills
```

### Transitions

```scss
--saho-transition-fast: 0.15s ease;
--saho-transition-base: 0.3s ease;
--saho-transition-slow: 0.5s ease;
```

---

## Component Development Guide

### Creating a New Component

1. **Generate Component Scaffold**
   ```bash
   cd webroot/themes/contrib/radix
   drupal-radix-cli generate
   # Follow prompts to create component
   ```

2. **Define Schema** (`[name].component.yml`)
   ```yaml
   '$schema': 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/modules/sdc/src/metadata.schema.json'
   name: 'Component Name'
   description: 'Brief description'
   group: 'Category'
   props:
     type: object
     properties:
       prop_name:
         type: string
         title: 'Prop Title'
         description: 'What this prop does'
     required:
       - prop_name
   ```

3. **Create Template** (`[name].twig`)
   ```twig
   {#
   /**
    * @file
    * Component description
    *
    * Available variables:
    * - prop_name: Description
    *
    * Usage:
    * {% include 'saho:[name]' with {...} %}
    */
   #}

   {% set classes = [
     'component-base-class',
     variant ? 'component--' ~ variant : '',
   ] %}

   <div{{ attributes.addClass(classes) }}>
     {# Component markup #}
   </div>
   ```

4. **Write Styles** (`[name].css`)
   ```css
   /**
    * Component Name Styles
    * Drupal 11 SDC
    */

   .component-base-class {
     /* Base styles */
   }

   .component--variant {
     /* Variant styles */
   }

   /* Responsive */
   @media (max-width: 768px) {
     /* Mobile styles */
   }
   ```

5. **Document Usage** (`README.md`)
   Include:
   - Component description
   - Props documentation with types, defaults, examples
   - Usage examples (basic → complex)
   - Common use cases
   - Migration guide from legacy patterns
   - Accessibility notes
   - Related components

6. **Test Component**
   ```bash
   # Clear cache
   ddev drush cr

   # Verify in browser
   # Check console for errors
   # Test responsive views
   # Validate accessibility
   ```

### Best Practices

#### Schema Design
- ✅ Use descriptive prop names
- ✅ Provide examples for each prop
- ✅ Set sensible defaults
- ✅ Document valid enum values
- ✅ Mark required props

#### Template Design
- ✅ Use semantic HTML
- ✅ Add proper ARIA attributes
- ✅ Support attributes parameter
- ✅ Provide slot support where needed
- ✅ Handle missing/empty data gracefully

#### CSS Best Practices
- ✅ Use BEM naming: `.saho-{block}[__{element}][--{modifier}]`
- ✅ Use CSS custom properties for values
- ✅ Mobile-first responsive design
- ✅ Include print styles
- ✅ Support reduced motion preference
- ✅ Add legacy compatibility (`@extend`) during migration

#### Documentation
- ✅ Comprehensive README with all props
- ✅ Real-world usage examples
- ✅ Migration guide from legacy patterns
- ✅ Screenshots/visual examples
- ✅ Accessibility notes
- ✅ Related component links

---

## Migration Guide

### Converting Templates to Use Components

#### Before (Manual HTML)
```twig
<div class="saho-card">
  <div class="saho-card-image">
    <img src="{{ image_url }}" alt="{{ image_alt }}">
  </div>
  <div class="saho-card-content">
    <span class="saho-card-badge">{{ content_type }}</span>
    <h3>{{ title }}</h3>
    <div class="saho-card-meta">
      <i class="fa fa-calendar"></i> {{ date }}
      •
      <i class="fa fa-user"></i> {{ author }}
    </div>
    <p>{{ description }}</p>
    <a href="{{ url }}" class="saho-card-button">Read More</a>
  </div>
</div>
```

#### After (Component-Based)
```twig
{% include 'saho:saho-card' with {
  title: title,
  image: {url: image_url, alt: image_alt},
  content: description,
  url: url,
  content_type: content_type,
  metadata: {
    date: date,
    author: author
  },
  button_text: 'Read More'
} %}
```

**Benefits**:
- 70% less code in templates
- Consistent styling across site
- Schema validation prevents errors
- Centralized updates (fix once, apply everywhere)
- Better accessibility (built into component)

### Legacy Class Compatibility

Components support legacy classes via CSS `@extend` during migration:

```css
/* Legacy compatibility */
.saho-card__meta {
  @extend .saho-metadata;
  @extend .saho-metadata--inline;
  @extend .saho-metadata--small;
}
```

This allows gradual migration without breaking existing templates.

---

## Performance

### Bundle Sizes
- **Component CSS**: Auto-aggregated by Drupal when components used
- **Main CSS**: 464 KiB (target: 350 KiB after full migration)
- **Main JS**: 77 KiB

### Optimization Features
- PurgeCSS removes unused styles
- CSS/JS minification in production
- Lazy loading for images
- Responsive srcset for performance
- CSS custom properties reduce duplication

---

## Accessibility

All components are WCAG 2.1 AA compliant:
- ✅ Semantic HTML
- ✅ ARIA attributes where needed
- ✅ Keyboard accessible
- ✅ Focus indicators
- ✅ Color contrast ≥ 4.5:1
- ✅ Screen reader friendly
- ✅ Reduced motion support

---

## Browser Support

- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile Safari (iOS 15+)
- ✅ Chrome Mobile (Android)

---

## Resources

### Drupal SDC Documentation
- [Single Directory Components](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components)
- [SDC Schema Specification](https://git.drupalcode.org/project/drupal/-/blob/HEAD/core/modules/sdc/src/metadata.schema.json)

### SAHO Project
- [GitHub Repository](https://github.com/South-African-History-Online/sahistory-web)
- [Theme Documentation](../README.md)
- [Testing Guide](../TESTING.md)
- [Development Guide](../../../CLAUDE.md)

### Design System
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [Radix Theme](https://www.drupal.org/project/radix)
- [Font Awesome Icons](https://fontawesome.com/icons)

---

## Support

For questions or issues with components:
1. Check component README.md
2. Review this documentation
3. See [TESTING.md](../TESTING.md) for testing procedures
4. Create [GitHub issue](https://github.com/South-African-History-Online/sahistory-web/issues)

---

**Last Updated**: January 2026
**Component Count**: 7 components
**Drupal Version**: 11.1.7
**Theme**: Radix with SAHO subtheme
