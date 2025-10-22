# SAHO Performance Improvement Plan
## Core Web Vitals Optimization Strategy

**Goal:** Pass Core Web Vitals Assessment for both Mobile and Desktop

---

## Phase 1: Critical CSS/JS Optimization (Highest Impact)

### 1.1 Enable Production Minification (IMMEDIATE - 1 hour)
**Impact:** Reduce CSS from 632KB to ~150-200KB, JS from 100KB to ~30-40KB
**Effort:** Low

**Actions:**
```bash
cd webroot/themes/custom/saho
```

**Update `webpack.mix.js`:**
```javascript
// Add after line 21
if (mix.inProduction()) {
  mix.options({
    processCssUrls: false,
    terser: {
      terserOptions: {
        compress: {
          drop_console: true,
        },
      },
    },
  });
  // Disable source maps in production
  mix.sourceMaps(false);
} else {
  mix.sourceMaps().webpackConfig({
    devtool: 'source-map',
  });
}
```

**Update `package.json` scripts:**
```json
"production": "NODE_ENV=production npx mix --production",
"build:prod": "NODE_ENV=production npx mix --production && npm run compress",
"compress": "find build -name '*.css' -exec gzip -k -9 {} \\; && find build -name '*.js' -exec gzip -k -9 {} \\;"
```

**Test:**
```bash
npm run production
du -sh build/css/main.style.css
```

**Expected Result:** CSS should drop to ~150-200KB, JS to ~30-40KB

---

### 1.2 Implement CSS Splitting & Tree Shaking (2-3 hours)
**Impact:** Reduce initial CSS load by 60-70%
**Effort:** Medium

**Create `webpack.mix.js` optimization:**
```javascript
// Add after line 52
const purgecss = require('@fullhuman/postcss-purgecss');

mix.sass("src/scss/main.style.scss", "build/css/main.style.css")
  .options({
    postCss: [
      require('autoprefixer'),
      ...(mix.inProduction() ? [
        purgecss({
          content: [
            './templates/**/*.twig',
            './components/**/*.twig',
            './src/js/**/*.js',
            './components/**/*.js',
          ],
          safelist: [
            /^modal/,
            /^dropdown/,
            /^collapse/,
            /^show$/,
            /^fade$/,
            /^active$/,
            /^nav-/,
            /^saho-/,
            /^drupal-/,
            /^js-/,
          ],
          defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || []
        })
      ] : [])
    ]
  });
```

**Install dependencies:**
```bash
npm install --save-dev @fullhuman/postcss-purgecss
```

**Expected Result:** CSS reduced to ~80-120KB

---

### 1.3 Enable Brotli Compression (1 hour)
**Impact:** Additional 20-25% size reduction over gzip
**Effort:** Low (server config)

**Add to `.htaccess` or server config:**
```apache
<IfModule mod_brotli.c>
  AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/xml text/css text/javascript application/javascript application/json application/xml
  BrotliCompressionLevel 6
</IfModule>

# Serve pre-compressed files
<IfModule mod_rewrite.c>
  RewriteCond %{HTTP:Accept-Encoding} br
  RewriteCond %{REQUEST_FILENAME}.br -f
  RewriteRule ^(.*)$ $1.br [L]
</IfModule>
```

---

## Phase 2: Image Optimization (HIGHEST IMPACT ON LCP)

### 2.1 Implement Responsive Images with Image Styles (3-4 hours)
**Impact:** Reduce image payload by 70-90%, fix LCP
**Effort:** Medium-High

**Problem:** Templates use `file_url()` serving full-resolution images
**Current:** `node--article.html.twig:24` - loads original images
**Impact:** LCP of 4-8 seconds on mobile

**Step 1: Create Image Styles via Drush**
```bash
ddev drush config:get image.style.large
ddev drush config:get image.style.medium

# If needed, create custom styles:
# Admin UI: /admin/config/media/image-styles
```

**Recommended Image Styles:**
- `saho_hero` - 1920x800 (for feature banners)
- `saho_large` - 1200x630 (for article images)
- `saho_medium` - 800x450 (for cards)
- `saho_thumbnail` - 400x225 (for small cards)

**Step 2: Create Twig Function for Responsive Images**

Create `webroot/themes/custom/saho/saho.theme`:
```php
<?php

/**
 * @file
 * Functions to support theming in the SAHO theme.
 */

use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;

/**
 * Generate responsive image markup with multiple sizes.
 */
function saho_get_responsive_image($file_entity, $alt = '', $styles = ['large', 'medium'], $sizes = '100vw', $loading = 'lazy', $class = '') {
  if (!$file_entity) {
    return '';
  }

  $uri = $file_entity->getFileUri();
  $srcset = [];

  foreach ($styles as $style_name) {
    if ($style = ImageStyle::load($style_name)) {
      $url = $style->buildUrl($uri);
      $dimensions = [];
      $style->transformDimensions($dimensions, $uri);
      $width = $dimensions['width'] ?? 0;
      if ($width) {
        $srcset[] = "$url {$width}w";
      }
    }
  }

  $fallback = ImageStyle::load($styles[0])->buildUrl($uri);

  // Get original dimensions
  $image = \Drupal::service('image.factory')->get($uri);
  $width = $image->getWidth();
  $height = $image->getHeight();

  $markup = sprintf(
    '<img src="%s" srcset="%s" sizes="%s" alt="%s" width="%d" height="%d" loading="%s" class="%s">',
    $fallback,
    implode(', ', $srcset),
    $sizes,
    htmlspecialchars($alt),
    $width,
    $height,
    $loading,
    $class
  );

  return ['#markup' => \Drupal\Core\Render\Markup::create($markup)];
}
```

**Step 3: Update Templates**

Replace lines 23-27 in `node--article.html.twig`:
```twig
{% if node.field_feature_banner.entity %}
  <div class="saho-feature-banner">
    {{ saho_get_responsive_image(
      node.field_feature_banner.entity,
      node.field_feature_banner.alt,
      ['saho_hero', 'saho_large'],
      '100vw',
      'eager',
      'saho-feature-banner-image'
    ) }}
  </div>
{% elseif node.field_article_image.entity %}
```

**Do the same for all image loads throughout templates (lines 32-36, 184-188, 378, 419, 459, 499, 539, 579, 618)**

---

### 2.2 Add Width/Height to ALL Images (CRITICAL FOR CLS) (2 hours)
**Impact:** Eliminates layout shift, fixes CLS score
**Effort:** Medium

**Problem:** No width/height attributes = browser can't reserve space = CLS failure

**Solution:** Already included in responsive image function above. Must be applied everywhere.

**Verify in DevTools:**
```javascript
// Check all images have dimensions
document.querySelectorAll('img').forEach(img => {
  if (!img.width || !img.height) {
    console.error('Missing dimensions:', img.src);
  }
});
```

---

### 2.3 Implement Native Lazy Loading (1 hour)
**Impact:** Reduces initial page weight by 50-70%
**Effort:** Low

**Already partially implemented** (line 378, 419, etc. have `loading="lazy"`)

**Update all images EXCEPT:**
- Feature banners (use `loading="eager"`)
- First card image (use `loading="eager"`)
- All others: `loading="lazy"`

---

### 2.4 Preload LCP Image (CRITICAL) (30 minutes)
**Impact:** Improves LCP by 1-2 seconds
**Effort:** Low

**Create `html.html.twig` override or use hook_page_attachments:**

In `saho.theme`:
```php
/**
 * Implements hook_page_attachments().
 */
function saho_page_attachments(array &$attachments) {
  $node = \Drupal::routeMatch()->getParameter('node');

  if ($node && $node->hasField('field_feature_banner')) {
    $image = $node->get('field_feature_banner')->entity;
    if ($image) {
      $uri = $image->getFileUri();
      $url = ImageStyle::load('saho_hero')->buildUrl($uri);

      $attachments['#attached']['html_head_link'][][] = [
        'rel' => 'preload',
        'as' => 'image',
        'href' => $url,
        'imagesrcset' => sprintf(
          '%s 1920w, %s 1200w',
          $url,
          ImageStyle::load('saho_large')->buildUrl($uri)
        ),
        'imagesizes' => '100vw',
      ];
    }
  }
}
```

---

## Phase 3: JavaScript Optimization

### 3.1 Defer Non-Critical JavaScript (30 minutes)
**Impact:** Improves INP and LCP
**Effort:** Low

**Already implemented** in `saho.libraries.yml:9` with `defer: true` ✓

**Verify all libraries have defer:**
```yaml
style:
  js:
    build/js/main.script.js: { attributes: { defer: true } }
```

---

### 3.2 Split JavaScript Bundle (2-3 hours)
**Impact:** Reduce main bundle, enable code splitting
**Effort:** Medium

**Update `webpack.mix.js`:**
```javascript
mix.webpackConfig({
  optimization: {
    splitChunks: {
      chunks: 'all',
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendor',
          priority: 10,
        },
        bootstrap: {
          test: /[\\/]node_modules[\\/]bootstrap/,
          name: 'bootstrap',
          priority: 20,
        },
      },
    },
  },
});
```

**Update library to load vendor bundle first:**
```yaml
style:
  js:
    build/js/vendor.js: { attributes: { defer: true }, weight: -10 }
    build/js/main.script.js: { attributes: { defer: true } }
```

---

### 3.3 Optimize Bootstrap Import (1 hour)
**Impact:** Reduce JS bundle by 30-40KB
**Effort:** Low

**Current:** Likely importing entire Bootstrap
**Update `src/js/main.script.js` to only import used components:**

```javascript
// Instead of:
// import 'bootstrap';

// Import only what's needed:
import { Dropdown } from 'bootstrap';
import { Collapse } from 'bootstrap';
import { Modal } from 'bootstrap';
// Add only components actually used
```

---

## Phase 4: Font Optimization

### 4.1 Preload Critical Fonts (30 minutes)
**Impact:** Eliminates font swap CLS
**Effort:** Low

**Check current fonts:**
```bash
ls -lah webroot/themes/custom/saho/build/fonts/
```

**Add to `saho.info.yml` or via hook:**
```php
$attachments['#attached']['html_head_link'][][] = [
  'rel' => 'preload',
  'as' => 'font',
  'type' => 'font/woff2',
  'href' => '/themes/custom/saho/build/fonts/your-font.woff2',
  'crossorigin' => 'anonymous',
];
```

---

### 4.2 Use font-display: swap (15 minutes)
**Impact:** Improves perceived performance
**Effort:** Very Low

**In your SCSS files, ensure:**
```scss
@font-face {
  font-family: 'YourFont';
  src: url('../fonts/your-font.woff2') format('woff2');
  font-display: swap; /* Add this */
  font-weight: 400;
  font-style: normal;
}
```

---

## Phase 5: Drupal Configuration

### 5.1 Enable Aggressive Caching (15 minutes)
**Impact:** Reduces TTFB significantly
**Effort:** Very Low

**Already configured** in `system.performance.yml`:
- CSS aggregation: ✓
- JS aggregation: ✓
- Page cache: 1800s ✓

**Recommended additions via Drush:**
```bash
ddev drush config:set system.performance cache.page.max_age 3600
ddev drush config:set advagg.settings enabled 1  # If AdvAgg installed
```

---

### 5.2 Add Cache Tags to Custom Blocks/Modules (2-3 hours)
**Impact:** Improves repeat visit performance
**Effort:** Medium

**Review all custom modules in `webroot/modules/custom/saho_*`**

Ensure all blocks have proper cache metadata:
```php
public function build() {
  return [
    '#theme' => 'saho_block',
    '#cache' => [
      'contexts' => ['url.path'],
      'tags' => ['node_list:article'],
      'max-age' => 3600,
    ],
  ];
}
```

---

## Phase 6: Server & Hosting Optimization

### 6.1 HTTP/2 & HTTP/3 (Server Config)
**Impact:** Parallel loading, faster TTFB
**Effort:** Low (if hosting supports)

**Verify current status:**
```bash
curl -I --http2 https://sahistory.org.za
```

**If not enabled, contact hosting or add to Nginx/Apache config**

---

### 6.2 CDN for Static Assets (Optional)
**Impact:** Reduces latency, improves global performance
**Effort:** Medium

**Options:**
- Cloudflare (free tier)
- BunnyCDN
- CloudFront

---

## Phase 7: Content Optimization

### 7.1 Lazy Load Drupal Views (2 hours)
**Impact:** Reduces initial page load
**Effort:** Medium

**For heavy views (like related content), implement Intersection Observer:**

```javascript
// Add to mobile-enhancements.js
(function (Drupal, once) {
  Drupal.behaviors.lazyViews = {
    attach(context) {
      once('lazy-view', '[data-lazy-view]', context).forEach((element) => {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const viewId = element.dataset.viewId;
              const displayId = element.dataset.displayId;
              // Load view via AJAX
              fetch(`/views/ajax?view_name=${viewId}&display_id=${displayId}`)
                .then(response => response.json())
                .then(data => {
                  element.innerHTML = data.html;
                });
              observer.unobserve(element);
            }
          });
        });
        observer.observe(element);
      });
    },
  };
})(Drupal, once);
```

---

## Implementation Priority Matrix

### Week 1 (Immediate Impact):
1. ✅ Enable production minification (1.1)
2. ✅ Implement responsive images (2.1)
3. ✅ Add width/height to images (2.2)
4. ✅ Preload LCP image (2.4)
5. ✅ Preload critical fonts (4.1)

**Expected Impact:** LCP: -2-3s, CLS: -0.15-0.25

### Week 2 (Optimization):
1. ✅ CSS splitting with PurgeCSS (1.2)
2. ✅ JavaScript bundle splitting (3.2)
3. ✅ Optimize Bootstrap imports (3.3)
4. ✅ Enable Brotli compression (1.3)

**Expected Impact:** Additional LCP: -1-2s, INP: -50-100ms

### Week 3 (Fine-tuning):
1. ✅ Lazy load views (7.1)
2. ✅ Font optimization (4.2)
3. ✅ Cache optimization (5.2)

**Expected Impact:** Overall improvement 10-20%

---

## Testing & Validation

### After Each Phase:
```bash
# Test locally
npm run production
ddev drush cr

# Test production build
cd webroot/themes/custom/saho
ls -lh build/css/main.style.css
ls -lh build/js/main.script.js
```

### Measure Core Web Vitals:
1. Chrome DevTools Lighthouse
2. WebPageTest.org
3. PageSpeed Insights (after deploying)

### Target Metrics:
- **LCP:** < 2.5s (currently likely 4-8s)
- **INP:** < 200ms (currently likely 200-500ms)
- **CLS:** < 0.1 (currently likely 0.15-0.35)

---

## Quick Wins (Do These First - 2-3 hours total)

1. **Enable minification** (1.1)
2. **Add image dimensions** (2.2) - Use responsive image function
3. **Preload LCP image** (2.4)
4. **Defer JS** (already done ✓)

**These 4 actions should improve your score by 30-40 points**

---

## Notes on "Critical CSS"

You mentioned critical CSS failed for you. **Good news:** You don't need it with this approach because:

1. PurgeCSS removes unused CSS (similar benefit)
2. Proper minification + compression makes CSS load fast enough
3. HTTP/2 parallel loading helps
4. Preloading LCP images has bigger impact

**Critical CSS is only needed when:**
- CSS is 1MB+ (yours will be <120KB after optimization)
- Can't reduce CSS size other ways
- Need sub-1s FCP (First Contentful Paint)

Your current approach (aggressive optimization + caching) is better and more maintainable.

---

## Estimated Total Effort
- **Phase 1-2:** 8-10 hours (Critical)
- **Phase 3-4:** 6-8 hours (Important)
- **Phase 5-7:** 6-10 hours (Optimization)

**Total:** 20-28 hours for complete implementation

---

## Success Criteria

### Before:
- Performance Score: ~40-60 (estimated)
- LCP: 4-8s
- CLS: 0.15-0.35
- INP: 200-500ms

### After (Target):
- Performance Score: 85-95+
- LCP: < 2.5s ✓
- CLS: < 0.1 ✓
- INP: < 200ms ✓
- **Core Web Vitals: PASSING** ✓

---

## Support Files to Create

1. `webpack.mix.js` (updated)
2. `saho.theme` (responsive image function)
3. Updated templates (node--article, etc.)
4. New SCSS with font-display
5. Updated package.json scripts

Would you like me to implement any of these phases immediately?
