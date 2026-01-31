# SAHO Image Component

Responsive image component with lazy loading, WebP support, srcset, aspect ratios, captions, and accessibility features. Provides optimized image display with modern performance techniques.

## Usage

```twig
{% include 'saho:saho-image' with {
  src: '/sites/default/files/images/biography-hero.jpg',
  alt: 'Portrait of Nelson Mandela',
  aspect_ratio: '16/9',
  lazy: true
} %}
```

## Props

### src (required)
**Type:** `string`
Primary image URL.

**Examples:**
- `'/sites/default/files/images/hero.jpg'`
- `'https://example.com/image.jpg'`

### alt
**Type:** `string`
Alternative text for accessibility. Required for semantic images. Use empty string or `decorative: true` for decorative images.

**Examples:**
```twig
alt: 'Portrait of Nelson Mandela at his inauguration'
alt: 'Protesters marching in Soweto, 1976'
```

### srcset
**Type:** `string`
**Default:** `null`

Responsive image sources with pixel densities or widths for high-resolution displays.

**Examples:**
```twig
{# Width descriptors #}
srcset: 'image-320.jpg 320w, image-640.jpg 640w, image-1280.jpg 1280w'

{# Pixel density descriptors #}
srcset: 'image.jpg 1x, image@2x.jpg 2x, image@3x.jpg 3x'
```

### sizes
**Type:** `string`
**Default:** `'100vw'`

Media queries for responsive image selection (use with srcset).

**Examples:**
```twig
{# Half viewport on desktop, full on mobile #}
sizes: '(min-width: 768px) 50vw, 100vw'

{# Three columns on large screens #}
sizes: '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw'
```

### aspect_ratio
**Type:** `string`
**Default:** `'auto'`
**Options:** `auto`, `16/9`, `4/3`, `3/2`, `1/1`, `3/4`, `2/3`

Image aspect ratio for consistent sizing and preventing layout shift.

**Examples:**
```twig
{# Widescreen hero images #}
aspect_ratio: '16/9'

{# Square profile images #}
aspect_ratio: '1/1'

{# Traditional photography #}
aspect_ratio: '4/3'
```

### lazy
**Type:** `boolean`
**Default:** `true`

Enable native browser lazy loading for performance.

**Examples:**
```twig
{# Lazy load (default) #}
lazy: true

{# Eager load for above-the-fold images #}
lazy: false
```

### caption
**Type:** `string`
**Default:** `null`

Optional caption text displayed with the image.

**Examples:**
```twig
caption: 'Nelson Mandela at his inauguration, May 10, 1994'
caption: 'Photo credit: Historical Archives of South Africa'
```

### caption_position
**Type:** `string`
**Default:** `'bottom'`
**Options:** `bottom`, `overlay`

Position of the caption relative to the image.

**Examples:**
```twig
{# Below image (default) #}
caption_position: 'bottom'

{# Overlay on hover (desktop) or visible (mobile) #}
caption_position: 'overlay'
```

### placeholder
**Type:** `boolean`
**Default:** `true`

Show animated shimmer placeholder while image loads.

**Examples:**
```twig
{# Show placeholder (default) #}
placeholder: true

{# No placeholder #}
placeholder: false
```

### placeholder_color
**Type:** `string`
**Default:** `'#f5f5f5'`

Background color for placeholder effect.

**Examples:**
```twig
placeholder_color: '#f5f5f5'
placeholder_color: 'var(--saho-background-muted)'
```

### object_fit
**Type:** `string`
**Default:** `'cover'`
**Options:** `cover`, `contain`, `fill`, `none`

How the image fills its container.

**Examples:**
```twig
{# Crop to fill (default) #}
object_fit: 'cover'

{# Scale to fit within bounds #}
object_fit: 'contain'
```

### object_position
**Type:** `string`
**Default:** `'center'`

Image alignment within container when using `object_fit: 'cover'`.

**Examples:**
```twig
object_position: 'center'
object_position: 'top'
object_position: 'bottom'
object_position: '50% 25%'
```

### border_radius
**Type:** `string`
**Default:** `'none'`
**Options:** `none`, `small`, `medium`, `large`, `full`

Rounded corner variant.

**Examples:**
```twig
{# No rounding (default) #}
border_radius: 'none'

{# Card-like rounding #}
border_radius: 'medium'

{# Circular (use with aspect_ratio: '1/1') #}
border_radius: 'full'
```

### width
**Type:** `string`
**Default:** `null`

Explicit width (CSS value). Overrides default 100% width.

**Examples:**
```twig
width: '400px'
width: '50%'
width: 'auto'
```

### height
**Type:** `string`
**Default:** `null`

Explicit height (CSS value). Usually better to use `aspect_ratio` instead.

**Examples:**
```twig
height: '300px'
height: 'auto'
```

### link
**Type:** `string`
**Default:** `null`

Optional URL to make the entire image clickable.

**Examples:**
```twig
link: '/node/123'
link: 'https://example.com'
link: path('entity.node.canonical', {'node': node.id()})
```

### link_title
**Type:** `string`
**Default:** `null`

Title attribute for image link (improves accessibility).

**Examples:**
```twig
link_title: 'View full biography'
link_title: 'Read more about this event'
```

### decorative
**Type:** `boolean`
**Default:** `false`

Mark image as decorative (uses empty alt, adds aria-hidden). Use for images that don't convey information.

**Examples:**
```twig
{# Decorative background pattern #}
decorative: true

{# Semantic image with meaning (default) #}
decorative: false
```

## Examples

### Basic Image
```twig
{% include 'saho:saho-image' with {
  src: '/sites/default/files/biography-hero.jpg',
  alt: 'Portrait of Nelson Mandela'
} %}
```

### Responsive Image with Srcset
```twig
{% include 'saho:saho-image' with {
  src: '/sites/default/files/hero-640.jpg',
  srcset: '/sites/default/files/hero-320.jpg 320w, /sites/default/files/hero-640.jpg 640w, /sites/default/files/hero-1280.jpg 1280w',
  sizes: '(max-width: 768px) 100vw, 50vw',
  alt: 'Historical protest march',
  aspect_ratio: '16/9'
} %}
```

### Card Image with Link
```twig
{% include 'saho:saho-image' with {
  src: image_url,
  alt: image_alt,
  aspect_ratio: '4/3',
  border_radius: 'medium',
  link: node_url,
  link_title: 'Read ' ~ title,
  lazy: true
} %}
```

### Hero Image (Eager Load)
```twig
{% include 'saho:saho-image' with {
  src: '/sites/default/files/homepage-hero.jpg',
  alt: 'South African History Online homepage',
  aspect_ratio: '16/9',
  lazy: false,
  object_position: 'center 25%'
} %}
```

### Image with Caption
```twig
{% include 'saho:saho-image' with {
  src: '/sites/default/files/archive-photo.jpg',
  alt: 'Soweto uprising, June 16, 1976',
  caption: 'Students protesting against Afrikaans education policy. Photo credit: Historical Archives',
  caption_position: 'bottom',
  aspect_ratio: '3/2'
} %}
```

### Image with Overlay Caption
```twig
{% include 'saho:saho-image' with {
  src: gallery_image,
  alt: image_description,
  caption: image_credit,
  caption_position: 'overlay',
  aspect_ratio: '1/1',
  link: image_full_url
} %}
```

### Profile Image (Circular)
```twig
{% include 'saho:saho-image' with {
  src: author_avatar,
  alt: author_name,
  aspect_ratio: '1/1',
  border_radius: 'full',
  width: '80px',
  object_fit: 'cover'
} %}
```

### Thumbnail Grid
```twig
<div class="thumbnail-grid">
  {% for item in items %}
    {% include 'saho:saho-image' with {
      src: item.thumbnail,
      alt: item.title,
      aspect_ratio: '1/1',
      border_radius: 'small',
      link: item.url,
      link_title: 'View ' ~ item.title
    } %}
  {% endfor %}
</div>
```

### Decorative Image
```twig
{% include 'saho:saho-image' with {
  src: '/themes/custom/saho/images/pattern-bg.png',
  decorative: true,
  placeholder: false
} %}
```

### Complex Example with All Features
```twig
{% set image_data = {
  src: node.field_image.entity.uri.value|file_url,
  srcset: [
    node.field_image.entity.uri.value|image_style('medium')|file_url ~ ' 640w',
    node.field_image.entity.uri.value|image_style('large')|file_url ~ ' 1280w',
  ]|join(', '),
  sizes: '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 800px',
  alt: node.field_image.alt,
  aspect_ratio: '16/9',
  caption: node.field_image_caption.value,
  caption_position: 'overlay',
  link: path('entity.node.canonical', {'node': node.id()}),
  link_title: 'Read full article: ' ~ node.label(),
  lazy: not loop.first,
  border_radius: 'medium',
  object_fit: 'cover',
  object_position: 'center'
} %}

{% include 'saho:saho-image' with image_data %}
```

## Common Use Cases

### On saho-card
```twig
<div class="saho-card">
  {% include 'saho:saho-image' with {
    src: card_image,
    alt: card_title,
    aspect_ratio: '16/9',
    border_radius: 'medium',
    link: card_url
  } %}

  <div class="saho-card-content">
    <h3>{{ card_title }}</h3>
    <p>{{ card_description }}</p>
  </div>
</div>
```

### Article Header Image
```twig
{% include 'saho:saho-image' with {
  src: node.field_hero_image.entity.uri.value|file_url,
  alt: node.field_hero_image.alt,
  aspect_ratio: '16/9',
  lazy: false,
  caption: node.field_photo_credit.value,
  caption_position: 'overlay'
} %}
```

### Biography Portrait
```twig
{% include 'saho:saho-image' with {
  src: biography_portrait,
  alt: person_name,
  aspect_ratio: '3/4',
  border_radius: 'small',
  width: '300px'
} %}
```

### Gallery Thumbnails
```twig
<div class="gallery-grid">
  {% for image in gallery_images %}
    {% include 'saho:saho-image' with {
      src: image.thumbnail,
      alt: image.alt,
      aspect_ratio: '1/1',
      link: image.full_url,
      link_title: 'View larger image',
      caption: image.caption,
      caption_position: 'overlay'
    } %}
  {% endfor %}
</div>
```

## Migration from Legacy

**Before:**
```twig
<div class="saho-card-image">
  <img src="{{ image_url }}" alt="{{ image_alt }}" loading="lazy">
</div>
```

**After:**
```twig
{% include 'saho:saho-image' with {
  src: image_url,
  alt: image_alt,
  aspect_ratio: '16/9',
  lazy: true
} %}
```

**Before (with link):**
```twig
<a href="{{ url }}">
  <img src="{{ image }}" alt="{{ alt }}">
</a>
```

**After:**
```twig
{% include 'saho:saho-image' with {
  src: image,
  alt: alt,
  link: url
} %}
```

## Performance Best Practices

### Lazy Loading Strategy
- **Above-the-fold images**: Set `lazy: false` (hero images, logo)
- **Below-the-fold images**: Use `lazy: true` (default) for cards, galleries
- **First item in loops**: `lazy: not loop.first` for first item eager, rest lazy

### Responsive Images
Always provide `srcset` and `sizes` for images > 320px width:
```twig
srcset: 'image-320.jpg 320w, image-640.jpg 640w, image-1280.jpg 1280w'
sizes: '(max-width: 768px) 100vw, 50vw'
```

### Aspect Ratios
Always specify `aspect_ratio` to prevent layout shift:
- Prevents Cumulative Layout Shift (CLS)
- Improves Core Web Vitals score
- Better user experience

### WebP Support
Generate WebP variants with provided scripts:
```bash
./scripts/optimize-images.sh /sites/default/files/images
```

Then use Drupal's WebP module or picture element.

## Accessibility

- Always provide meaningful `alt` text for semantic images
- Use `decorative: true` for purely decorative images (empty alt, aria-hidden)
- Provide `link_title` when images are clickable
- Captions improve context for screen reader users
- Focus indicators for keyboard navigation
- High contrast mode support

## Styling Notes

- Uses CSS custom properties for theming
- Inherits from SAHO design system
- Responsive font sizing for captions
- Print-friendly styles (static captions)
- Supports reduced motion preferences
- Smooth transitions (disabled in reduced-motion mode)

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Native lazy loading (fallback for older browsers via polyfill if needed)
- CSS aspect-ratio (fallback padding-top hack for IE11 if needed)
- Responsive images (srcset/sizes)
- WebP support with fallback

## Related Components

- `saho-card` - Often contains images
- `saho-badge` - Can overlay on images
- `saho-section-header` - Headers with optional background images

## Tips

1. **Prevent Layout Shift**: Always use `aspect_ratio`
2. **Performance**: Use `lazy: true` for below-fold images
3. **Accessibility**: Meaningful alt text or `decorative: true`
4. **Responsive**: Provide srcset for images > 320px
5. **Loading Speed**: Eager load hero images (`lazy: false`)
6. **User Experience**: Add captions for context
7. **SEO**: Descriptive alt text helps search engines
8. **Mobile**: Test overlay captions (always visible on mobile)
