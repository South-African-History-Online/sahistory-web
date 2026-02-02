# Top Content Block Implementation Plan
**SAHO Custom Inline Block - Statistics-Based Trending Content**

## Executive Summary

Create a new custom inline block that displays top/trending content based on view statistics with configurable time periods (week, month, year, all-time).

**Status**: Ready for implementation
**Priority**: Medium
**Estimated Effort**: 8-12 hours
**Dependencies**: Statistics module (already enabled)

---

## Problem Statement

SAHO needs a way to surface popular/trending content to users based on actual viewing statistics. Currently, there's no block that showcases:
- Most viewed articles in the last week
- Top content for the month
- Popular content for the year
- All-time most viewed content

This block will help editors highlight trending topics and improve content discovery.

---

## Current Statistics Infrastructure

### Enabled Modules
- ✅ **Core Statistics**: Enabled and tracking 81,359 nodes
- ✅ **saho_statistics**: Custom module with TermTracker service
- ✅ **entity_usage**: Tracking term usage

### Available Data
**Database Table**: `node_counter`
```sql
Fields:
- nid (int) - Node ID
- totalcount (bigint) - Total lifetime views (3,442,394 total across all nodes)
- daycount (mediumint) - Views today (reset daily by cron)
- timestamp (bigint) - Unix timestamp of last view
```

**Sample Top Content**:
- Node 144647: 114,388 views
- Node 98760: 55,585 views
- Node 120864: 43,446 views

### Critical Limitation

**ISSUE**: Core Statistics module does NOT store historical view data per time period.

**What's Missing**:
- Weekly view aggregations
- Monthly view aggregations
- Yearly view aggregations
- View history over time

**Impact**: Cannot query "most viewed in last 7 days" directly from database.

### Workaround Strategy

Use **timestamp-based filtering** + total views:

```sql
-- "Recently Popular" Content (last 7 days)
WHERE timestamp >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
ORDER BY totalcount DESC
```

**Interpretation**: Shows content that was recently viewed AND has high total views (trending popular content).

**Alternative Considered**: Google Analytics 4 API integration (future enhancement).

---

## Block Specification

### Module Location
**Path**: `/webroot/modules/custom/saho_utils/top_content/`

**Structure**:
```
top_content/
├── src/
│   └── Plugin/
│       └── Block/
│           └── TopContentBlock.php
├── templates/
│   └── top-content-block.html.twig
├── css/
│   └── top-content-block.css
├── top_content.info.yml
├── top_content.libraries.yml
└── top_content.module
```

### Configuration Options

```yaml
Plugin Configuration:
  time_period:
    type: radios
    options: [week, month, year, all-time]
    default: week

  content_types:
    type: checkboxes
    options: [article, biography, event, archive, place, image]
    default: [] (all types)

  item_limit:
    type: select
    range: 1-20
    default: 5

  display_mode:
    type: radios
    options: [card_grid, compact_list]
    default: card_grid

  show_view_count:
    type: checkbox
    default: FALSE

  enable_carousel:
    type: checkbox
    default: FALSE (for future enhancement)

  cache_duration:
    type: number
    range: 300-86400 (5 min - 24 hours)
    default: 3600 (1 hour)
```

### Query Logic

**Service Dependencies**:
- `EntityTypeManagerInterface` - Load nodes
- `Connection` - Database queries
- `FileUrlGeneratorInterface` - Image URLs
- `CacheBackendInterface` - Query caching

**Query Implementation**:
```php
protected function getTopContent() {
  $config = $this->configuration;

  // Time period thresholds
  $intervals = [
    'week' => strtotime('-7 days'),
    'month' => strtotime('-30 days'),
    'year' => strtotime('-1 year'),
    'all-time' => 0,
  ];

  $query = $this->database->select('node_counter', 'nc');
  $query->addField('nc', 'nid');
  $query->addField('nc', 'totalcount', 'view_count');

  // Join with node table for status/type filtering
  $query->join('node_field_data', 'n', 'n.nid = nc.nid');
  $query->condition('n.status', 1);

  // Time period filter
  if ($config['time_period'] !== 'all-time') {
    $threshold = $intervals[$config['time_period']];
    $query->condition('nc.timestamp', $threshold, '>=');
  }

  // Content type filter
  if (!empty($config['content_types'])) {
    $query->condition('n.type', array_filter($config['content_types']), 'IN');
  }

  $query->orderBy('nc.totalcount', 'DESC');
  $query->range(0, $config['item_limit']);

  return $query->execute()->fetchAllKeyed();
}
```

### Display Modes

#### Card Grid Mode (Primary)
- Uses `saho-card-grid` component
- Displays image, title, content type badge, optional view count
- Responsive grid (300px min card width)
- Design token-based spacing

#### Compact List Mode (Secondary)
- Numbered ordered list
- Title + optional view count
- Minimal styling
- Good for sidebars

### Template Structure

**Theme Hook**:
```php
'top_content_block' => [
  'variables' => [
    'items' => [],              // Array of content items
    'time_period' => 'week',    // Selected time period
    'display_mode' => 'card_grid',
    'show_view_count' => FALSE,
    'block_title' => '',        // Auto-generated from time period
  ],
  'template' => 'top-content-block',
],
```

**Template** (`top-content-block.html.twig`):
```twig
<div{{ attributes.addClass('top-content-block', 'top-content-block--' ~ time_period) }}>
  {% if block_title %}
    <h2 class="top-content-block__title">{{ block_title }}</h2>
  {% endif %}

  {% if display_mode == 'card_grid' %}
    <div class="saho-card-grid">
      {% for item in items %}
        <article class="saho-card saho-card--primary">
          <a href="{{ item.url }}" class="saho-card-link">
            {% if item.image %}
              <div class="saho-card__image">
                <img src="{{ item.image }}" alt="{{ item.title }}" loading="lazy">
              </div>
            {% endif %}
            <div class="saho-card__body">
              <div class="saho-card__type-badge">{{ item.content_type_label }}</div>
              <h3 class="saho-card__title">{{ item.title }}</h3>
              {% if show_view_count %}
                <div class="saho-card__meta">
                  <svg class="icon icon-eye" aria-hidden="true">
                    <use href="#icon-eye"></use>
                  </svg>
                  <span class="view-count">{{ item.view_count|number_format }} views</span>
                </div>
              {% endif %}
              {% if item.summary %}
                <p class="saho-card__content">{{ item.summary }}</p>
              {% endif %}
            </div>
          </a>
        </article>
      {% endfor %}
    </div>
  {% else %}
    <ol class="top-content-list">
      {% for item in items %}
        <li class="top-content-list__item">
          <a href="{{ item.url }}" class="top-content-list__link">
            <span class="top-content-list__title">{{ item.title }}</span>
            {% if show_view_count %}
              <span class="top-content-list__count">({{ item.view_count|number_format }} views)</span>
            {% endif %}
          </a>
        </li>
      {% endfor %}
    </ol>
  {% endif %}
</div>
```

### CSS Implementation (Design Token-Based)

**File**: `css/top-content-block.css`

```css
/**
 * Top Content Block Styles
 * Uses SAHO design token system for consistency
 */

.top-content-block {
  margin-bottom: var(--spacing-xxl);
  max-width: var(--container-wide);
  margin-left: auto;
  margin-right: auto;
}

.top-content-block__title {
  font-size: var(--font-size-2xl);
  font-weight: 600;
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-sm);
  border-bottom: 3px solid var(--color-primary);
}

/* Card Grid Mode - Uses Design Tokens */
.top-content-block .saho-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(var(--card-grid-min-width), 1fr));
  gap: var(--spacing-lg);
}

.top-content-block .saho-card__type-badge {
  display: inline-block;
  padding: var(--spacing-xs);
  background: var(--color-primary-light);
  color: var(--color-white);
  font-size: var(--font-size-xs);
  font-weight: 600;
  text-transform: uppercase;
  border-radius: var(--border-radius-sm);
  margin-bottom: var(--spacing-xs);
}

.top-content-block .saho-card__meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
  margin-top: var(--spacing-sm);
}

.top-content-block .icon-eye {
  width: 16px;
  height: 16px;
}

/* Compact List Mode */
.top-content-list {
  list-style: none;
  counter-reset: top-content-counter;
  max-width: var(--container-standard);
  margin: 0 auto;
  padding: var(--spacing-md);
  background: var(--color-surface);
  border-radius: var(--border-radius-md);
  box-shadow: var(--shadow-md);
}

.top-content-list__item {
  counter-increment: top-content-counter;
  padding: var(--spacing-sm);
  border-bottom: 1px solid var(--color-border);
  position: relative;
  padding-left: var(--spacing-xl);
}

.top-content-list__item:last-child {
  border-bottom: none;
}

.top-content-list__item::before {
  content: counter(top-content-counter);
  position: absolute;
  left: var(--spacing-xs);
  top: var(--spacing-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: var(--color-primary);
  color: var(--color-white);
  border-radius: 50%;
  font-size: var(--font-size-sm);
  font-weight: 600;
}

.top-content-list__link {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: var(--spacing-sm);
  color: var(--color-text-primary);
  text-decoration: none;
  transition: color 0.2s ease;
}

.top-content-list__link:hover {
  color: var(--color-primary);
}

.top-content-list__title {
  flex: 1;
  font-size: var(--font-size-md);
  font-weight: 500;
}

.top-content-list__count {
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
  white-space: nowrap;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .top-content-block .saho-card-grid {
    grid-template-columns: 1fr;
  }

  .top-content-list {
    padding: var(--spacing-sm);
  }

  .top-content-list__item {
    padding-left: var(--spacing-lg);
  }
}

/* Accessibility: Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .top-content-list__link,
  .saho-card-link {
    transition: none !important;
  }
}
```

### Caching Strategy

```php
public function build() {
  // ... build logic ...

  return [
    '#theme' => 'top_content_block',
    '#items' => $items,
    // ... other variables ...
    '#cache' => [
      'max-age' => $this->configuration['cache_duration'],
      'contexts' => ['url.query_args'],  // Different for different config
      'tags' => [
        'node_list',
        'node_counter',  // Invalidate when statistics change
        'top_content_block:' . $this->configuration['time_period'],
      ],
    ],
    '#attached' => [
      'library' => ['top_content/top-content-block'],
    ],
  ];
}

public function getCacheTags() {
  return Cache::mergeTags(
    parent::getCacheTags(),
    ['node_counter', 'node_list']
  );
}

public function getCacheContexts() {
  return Cache::mergeContexts(
    parent::getCacheContexts(),
    ['url.query_args']
  );
}
```

---

## Implementation Phases

### Phase 1: Core Block (Week 1)
- [ ] Create module structure (info.yml, .module, .libraries.yml)
- [ ] Create TopContentBlock plugin class
- [ ] Implement dependency injection (EntityTypeManager, Database, FileUrlGenerator)
- [ ] Create defaultConfiguration() method
- [ ] Implement blockForm() configuration form
- [ ] Implement blockSubmit() save logic
- [ ] Create getTopContent() query method
- [ ] Test basic functionality

### Phase 2: Display & Template (Week 1)
- [ ] Create template file (top-content-block.html.twig)
- [ ] Implement buildContentItem() method
- [ ] Add image field handling with fallbacks
- [ ] Add summary/excerpt extraction
- [ ] Create hook_theme() definition
- [ ] Test both display modes (card grid, compact list)

### Phase 3: Styling (Week 2)
- [ ] Create CSS file using design tokens
- [ ] Implement card grid styling
- [ ] Implement compact list styling with numbered items
- [ ] Add responsive breakpoints
- [ ] Test accessibility (keyboard nav, screen readers)
- [ ] Add reduced motion support
- [ ] Mobile testing

### Phase 4: Polish & Documentation (Week 2)
- [ ] Implement caching strategy
- [ ] Add graceful fallback if statistics unavailable
- [ ] Add admin help text/descriptions
- [ ] Create README.md with configuration guide
- [ ] Add inline code documentation
- [ ] Test with different content types
- [ ] Test different time periods
- [ ] Performance testing

---

## Code Patterns to Follow

### Block Plugin Structure (from FeaturedBiographyBlock)
```php
namespace Drupal\top_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Top Content' block.
 *
 * @Block(
 *   id = "top_content_block",
 *   admin_label = @Translation("Top Content (Week/Month/Year)"),
 *   category = @Translation("All custom"),
 * )
 */
class TopContentBlock extends BlockBase implements ContainerFactoryPluginInterface {
  // Implementation...
}
```

### Image Handling Pattern (from FeaturedArticleBlock)
```php
protected function getNodeImage(NodeInterface $node) {
  // Try multiple image fields in order
  $image_fields = [
    'field_article_image',
    'field_bio_pic',
    'field_image',
    'field_tdih_image',
  ];

  foreach ($image_fields as $field_name) {
    if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
      $file = $node->get($field_name)->entity;
      if ($file && $file instanceof FileInterface) {
        return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }
  }

  return NULL;
}
```

---

## Testing Checklist

### Functional Testing
- [ ] Block appears in Layout Builder
- [ ] All time periods work (week, month, year, all-time)
- [ ] Content type filtering works
- [ ] Item limit respected
- [ ] Both display modes render correctly
- [ ] View count displays when enabled
- [ ] No PHP errors in watchdog logs

### Visual Testing
- [ ] Card grid responsive on mobile/tablet/desktop
- [ ] Compact list displays properly
- [ ] Images load correctly with lazy loading
- [ ] Typography uses design tokens
- [ ] Spacing uses design tokens
- [ ] Colors use design tokens

### Performance Testing
- [ ] Page load time acceptable (<500ms impact)
- [ ] Caching working (check cache hits)
- [ ] Database queries optimized (< 50ms)
- [ ] No N+1 query problems

### Accessibility Testing
- [ ] Keyboard navigation works
- [ ] Screen reader announces content properly
- [ ] Color contrast WCAG 2.1 AA compliant
- [ ] Focus indicators visible
- [ ] Reduced motion respected

---

## Future Enhancements

### Phase 2 Features (Future)
1. **Carousel Mode**: Enable carousel for multiple items (using existing patterns)
2. **Category Filtering**: Filter by taxonomy terms
3. **Manual Override**: Allow editors to pin specific content
4. **Google Analytics Integration**: True time-period analytics via GA4 API
5. **Trending Indicator**: Show % change vs previous period
6. **View Count Animation**: Animated counter on page load

### Alternative Analytics Approaches
1. **Custom Tracking Table**: Store daily/weekly/monthly view aggregations
2. **Matomo Integration**: Self-hosted analytics
3. **Server Log Analysis**: Parse nginx/Apache logs for view data

---

## Dependencies

### Required (Already Available)
- ✅ Statistics module (enabled)
- ✅ Entity Usage module (enabled)
- ✅ saho_statistics module (enabled)
- ✅ Design token system (implemented)
- ✅ SAHO card component (exists)

### Optional (Future)
- Google Analytics Reports module (for GA4 integration)
- Views integration (create view display for advanced filtering)

---

## Related Documentation

- **Statistics Module**: Core Statistics module tracking node views
- **SAHO Card System**: `/webroot/themes/custom/saho/src/scss/components/_unified-cards.scss`
- **Design Tokens**: `/webroot/themes/custom/saho/src/scss/abstracts/_design-tokens.scss`
- **Block Patterns**: Featured Biography, Featured Article blocks

---

## Success Metrics

### Technical
- [ ] Zero PHP errors/warnings
- [ ] Page load impact < 500ms
- [ ] Cache hit rate > 85%
- [ ] Mobile responsive 100%
- [ ] WCAG 2.1 AA compliant

### User Experience
- [ ] Content discovery improved (user testing)
- [ ] Click-through rate on top content > 5%
- [ ] Block used on at least 3 high-traffic pages
- [ ] Positive feedback from content editors

---

## Timeline

**Week 1**: Core implementation + display
**Week 2**: Styling + testing + documentation
**Total Effort**: 8-12 hours over 2 weeks

---

## Notes

**Limitation Disclosure**: Block description should clearly state:
> "Shows popular content based on recent viewing activity and total views. Due to Statistics module limitations, time periods show content that was recently viewed AND has high total views (trending popular content), rather than precise view counts for each period."

**Accessibility Priority**: All interactive elements must be keyboard accessible and screen-reader friendly.

**Design Token Usage**: 100% of CSS values must use design tokens (no hardcoded colors, spacing, fonts).
