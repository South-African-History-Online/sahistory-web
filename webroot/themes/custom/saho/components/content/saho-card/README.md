# SAHO Card Component

**Reusable card component for displaying content with SAHO heritage branding.**

## Purpose

This component provides a consistent, accessible card pattern for displaying articles, biographies, events, places, and other content types across the SAHO website. It integrates with the saho-button component for action buttons.

## Basic Usage

```twig
{% include 'saho:saho-card' with {
  title: 'Nelson Mandela Biography',
  content: 'Nelson Mandela was a South African activist...',
  url: '/biographies/nelson-mandela',
  content_type: 'biography'
} %}
```

## With Image

```twig
{% include 'saho:saho-card' with {
  title: 'The June 16, 1976 Soweto Uprising',
  content: 'A pivotal moment in South African history...',
  image: {
    url: '/sites/default/files/soweto-uprising.jpg',
    alt: 'Students marching in Soweto'
  },
  url: '/events/soweto-uprising',
  content_type: 'event'
} %}
```

## With Action Button

```twig
{% include 'saho:saho-card' with {
  title: 'Robben Island',
  content: 'The island where Nelson Mandela was imprisoned...',
  image: {
    url: '/sites/default/files/robben-island.jpg',
    alt: 'Robben Island prison'
  },
  url: '/places/robben-island',
  content_type: 'place',
  button_text: 'Explore',
  button_variant: 'card-action',
  button_icon: 'arrow-right'
} %}
```

## With Metadata

```twig
{% include 'saho:saho-card' with {
  title: 'The Freedom Charter',
  content: 'Adopted in 1955, the Freedom Charter was a statement...',
  url: '/articles/freedom-charter',
  content_type: 'article',
  metadata: {
    date: 'Updated: 15 Jan 2026',
    author: 'SAHO Editorial Team',
    category: 'Documents',
    featured: true,
    staff_pick: false
  },
  button_text: 'Read More'
} %}
```

## Card Variants

### Primary (Default)
```twig
{% include 'saho:saho-card' with {
  title: 'Example Card',
  url: '#',
  variant: 'primary'
} %}
```

### Secondary
```twig
{% include 'saho:saho-card' with {
  title: 'Example Card',
  url: '#',
  variant: 'secondary'
} %}
```

### Accent
```twig
{% include 'saho:saho-card' with {
  title: 'Example Card',
  url: '#',
  variant: 'accent'
} %}
```

### Highlight
```twig
{% include 'saho:saho-card' with {
  title: 'Example Card',
  url: '#',
  variant: 'highlight'
} %}
```

## Size Variants

```twig
{# Small card #}
{% include 'saho:saho-card' with {
  title: 'Small Card',
  url: '#',
  size: 'small'
} %}

{# Medium card (default) #}
{% include 'saho:saho-card' with {
  title: 'Medium Card',
  url: '#',
  size: 'medium'
} %}

{# Large card #}
{% include 'saho:saho-card' with {
  title: 'Large Card',
  url: '#',
  size: 'large'
} %}
```

## Content Types

The `content_type` prop applies specific styling and default icons:

- `article` - General articles (default)
- `biography` - People biographies
- `place` - Locations and places
- `event` - Historical events
- `collection` - Content collections
- `archive` - Archived materials

```twig
{% include 'saho:saho-card' with {
  title: 'Steve Biko',
  content_type: 'biography',
  url: '/biographies/steve-biko'
} %}
```

## Button Customization

The card component integrates with the saho-button component for action buttons.

### Button Variants

```twig
{# Card action button (default - pill style) #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '#',
  button_text: 'Explore',
  button_variant: 'card-action'
} %}

{# Primary button (filled red) #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '#',
  button_text: 'Learn More',
  button_variant: 'primary'
} %}

{# Secondary button (outlined) #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '#',
  button_text: 'View Details',
  button_variant: 'secondary'
} %}

{# Link style button #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '#',
  button_text: 'Read More',
  button_variant: 'link'
} %}
```

### Button Sizes

```twig
{# Small button (default for cards) #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '#',
  button_text: 'View',
  button_size: 'small'
} %}

{# Medium button #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '#',
  button_text: 'Read More',
  button_size: 'medium'
} %}

{# Large button #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '#',
  button_text: 'Explore',
  button_size: 'large'
} %}
```

### Button Icons

```twig
{# Arrow right (default) #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '#',
  button_text: 'Continue',
  button_icon: 'arrow-right'
} %}

{# External link icon #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: 'https://example.com',
  button_text: 'Visit Site',
  button_icon: 'external'
} %}

{# Download icon #}
{% include 'saho:saho-card' with {
  title: 'Example',
  url: '/files/document.pdf',
  button_text: 'Download',
  button_icon: 'download'
} %}
```

## Using Slots

### Custom Header

```twig
{% embed 'saho:saho-card' with {
  title: 'Featured Article',
  url: '#'
} %}
  {% block card_header %}
    <div class="alert alert-info mb-0">
      <strong>Editor's Choice</strong>
    </div>
  {% endblock %}
{% endembed %}
```

### Custom Footer

```twig
{% embed 'saho:saho-card' with {
  title: 'Example Card',
  url: '#'
} %}
  {% block card_footer %}
    <div class="d-flex justify-content-between">
      <span>Views: 1,234</span>
      <span>Shares: 56</span>
    </div>
  {% endblock %}
{% endembed %}
```

### Custom Badge

```twig
{% embed 'saho:saho-card' with {
  title: 'New Article',
  image: { url: '/image.jpg', alt: 'Example' },
  url: '#'
} %}
  {% block card_badge %}
    <span class="badge bg-success">NEW</span>
  {% endblock %}
{% endembed %}
```

## Props Reference

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `title` | string | Yes | - | Card title/heading |
| `content` | string | No | - | Main body text (auto-truncated at 120 chars) |
| `image` | object | No | - | Image object with `url` and `alt` properties |
| `url` | string | No | - | Link destination (makes entire card clickable) |
| `variant` | string | No | `primary` | Visual variant: `primary`, `secondary`, `accent`, `highlight` |
| `size` | string | No | `medium` | Card size: `small`, `medium`, `large` |
| `content_type` | string | No | `article` | Content type: `article`, `biography`, `place`, `event` |
| `metadata` | object | No | - | Metadata object (see below) |
| `button_text` | string | No | - | Text for action button |
| `button_variant` | string | No | `card-action` | Button style: `primary`, `secondary`, `card-action`, `link` |
| `button_icon` | string | No | `arrow-right` | Button icon: `arrow-right`, `external`, `download` |
| `button_size` | string | No | `small` | Button size: `small`, `medium`, `large` |

### Metadata Object Properties

| Property | Type | Description |
|----------|------|-------------|
| `date` | string | Date string (e.g., "Updated: 15 Jan 2026") |
| `author` | string | Author name |
| `category` | string | Content category |
| `featured` | boolean | Show "Featured" badge |
| `staff_pick` | boolean | Show "Staff Pick" badge |

## Slots

| Slot | Description |
|------|-------------|
| `card_header` | Optional header content above the image |
| `card_footer` | Optional footer content above status badges |
| `card_badge` | Optional custom badge overlay on image |

## Accessibility Features

- **Semantic HTML**: Uses `<article>` element for content
- **Stretched Links**: Entire card is clickable when URL provided
- **Alt Text**: Image alt text with fallback to title
- **ARIA**: Proper roles and labels for interactive elements
- **Keyboard Navigation**: Full keyboard support with visible focus states
- **Screen Reader**: Icon labels marked as `aria-hidden`

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile Safari (iOS 12+)
- Chrome Mobile (Android 5+)

## Notes

- Button is visual-only when used with stretched link (entire card is clickable)
- Images are lazy-loaded by default
- Content is auto-truncated at 120 characters
- Hover effects include card lift and image zoom
- Responsive image heights on mobile devices
- Print-friendly styles included
