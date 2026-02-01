# Sitemap Configuration for SEO Optimization

## Status

The Simple XML Sitemap module is installed and has successfully generated **2,131 sitemap entries**.

## Configuration Needed

To ensure Google can find and index your sitemap:

### 1. Verify Sitemap Path

Check your sitemap configuration:
```bash
ddev drush config:get simple_sitemap.settings
```

The sitemap should be accessible at one of:
- `/sitemap.xml` (preferred)
- `/simple-sitemap/default` (module default)
- `/sitemap/default.xml`

### 2. Configure Simple Sitemap Base URL

Set the base URL to your production domain:
```bash
ddev drush config:set simple_sitemap.settings base_url "https://sahistory.org.za"
```

### 3. Enable Content Types

Ensure all important content types are included in the sitemap:
```bash
# Check what's enabled
ddev drush simple-sitemap:entities-list

# Enable a content type if needed
ddev drush simple-sitemap:entity-type-enable node article
ddev drush simple-sitemap:entity-type-enable node biography
ddev drush simple-sitemap:entity-type-enable node event
ddev drush simple-sitemap:entity-type-enable node archive
ddev drush simple-sitemap:entity-type-enable node place
```

### 4. Regenerate Sitemap

After configuration:
```bash
ddev drush simple-sitemap:generate
```

### 5. Submit to Google Search Console

Once the sitemap is accessible:

1. Go to https://search.google.com/search-console
2. Select your property (sahistory.org.za)
3. Navigate to Sitemaps (left sidebar)
4. Click "Add a new sitemap"
5. Enter sitemap URL (e.g., `sitemap.xml`)
6. Click "Submit"

## Sitemap Benefits for Schema.org

Having a properly configured sitemap helps Google:
- **Discover all 81,341 nodes** with Schema.org markup faster
- **Index rich results** from your structured data
- **Crawl new content** as it's published
- **Understand site structure** via breadcrumb schemas
- **Prioritize important pages** (articles, biographies, events)

## Recommended Sitemap Settings

```yaml
# Priority by content type (0.0 to 1.0)
article: 0.9        # High priority - original content
biography: 0.9      # High priority - unique historical data
event: 0.8          # High - historical events
archive: 0.7        # Medium-high - primary sources
place: 0.6          # Medium - geographic data
product: 0.5        # Medium - books/publications
image: 0.4          # Lower - supplementary content

# Change frequency
article: weekly     # Updated content
biography: monthly  # Relatively stable
event: monthly      # Historical events are stable
archive: monthly    # Archival materials
```

## Current Status

- Simple Sitemap: **Installed**
- Entries Generated: **2,131 items**
- Schema.org Coverage: **81,341 nodes**
- Next Step: Configure base URL and verify accessibility

## Integration with Schema.org

Your Schema.org implementation is already live on all pages. Once the sitemap is properly configured and submitted to Google:

1. **Week 1-2**: Google discovers your Schema.org markup
2. **Week 2-4**: Rich results start appearing in search
3. **Week 4-8**: Full indexing of structured data
4. **Month 2+**: Measurable SEO improvements (CTR, rankings)

## Troubleshooting

### Sitemap not found (404)
```bash
# Check if module is enabled
ddev drush pm:list | grep sitemap

# Regenerate
ddev drush simple-sitemap:generate

# Check URL aliases
ddev drush route:get simple_sitemap.sitemap_default
```

### Empty sitemap
```bash
# Enable content types
ddev drush simple-sitemap:entity-type-enable node article

# Rebuild
ddev drush simple-sitemap:generate
```

### Slow generation
- Sitemap generation runs in queue
- Check queue status: `ddev drush queue:list`
- Process queue: `ddev drush queue:run simple_sitemap`

## Next Actions

1. **Configure base URL** for production domain
2. **Verify sitemap accessibility** at standard path
3. **Submit to Google Search Console**
4. **Monitor indexing** in Search Console → Coverage
5. **Track rich results** in Search Console → Enhancements

---

**Related Documentation**:
- Schema.org Implementation: `webroot/modules/custom/saho_tools/README_SCHEMA.md`
- Simple Sitemap Docs: https://www.drupal.org/project/simple_sitemap
