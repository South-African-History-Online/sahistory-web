# Non-Destructive Refactor Plan for SAHO
## Safety-First Approach to Content & Field Consolidation

**Last Updated:** 2026-02-12
**Status:** Planning Phase
**Author:** Development Team

---

## Table of Contents

1. [Guiding Principles](#guiding-principles)
2. [Pre-Flight Checklist](#pre-flight-checklist)
3. [Phase 1: Quick Wins (Zero Risk)](#phase-1-quick-wins-zero-risk)
4. [Phase 2: Field Consolidation (Low Risk)](#phase-2-field-consolidation-low-risk)
5. [Phase 3: Content Type Migration (Medium Risk)](#phase-3-content-type-migration-medium-risk)
6. [Phase 4: Legacy Cleanup (High Risk)](#phase-4-legacy-cleanup-high-risk)
7. [Rollback Procedures](#rollback-procedures)
8. [Testing & Verification](#testing--verification)
9. [Monitoring & Alerts](#monitoring--alerts)

---

## Guiding Principles

### 1. **Never Delete, Always Deprecate First**
- Mark fields/content types as "deprecated" before removal
- Run in parallel for 30+ days
- Monitor usage with logging
- Only delete after zero usage confirmed

### 2. **Copy, Don't Move**
- Create new unified fields alongside old ones
- Migrate data by copying (not moving)
- Verify data integrity before switching
- Keep old fields as backup

### 3. **Feature Flags for Everything**
- All new functionality behind feature flags
- Enable for testing, disable if issues
- Gradual rollout by percentage
- Easy revert without code changes

### 4. **Incremental Migrations**
- Process in batches (100-500 nodes at a time)
- Verify each batch before continuing
- Pause/resume capability
- Detailed logging of all changes

### 5. **Test on Clone First**
- Every change tested on database clone
- Production database never touched directly
- Dry-run mode for all migrations
- Compare before/after states

### 6. **Audit Trail for Everything**
- Log every data change with timestamp
- Store old values before modification
- Track who initiated each change
- Generate migration reports

---

## Pre-Flight Checklist

### Before ANY Refactor Work

#### ✅ Backup Strategy
```bash
# 1. Full database backup
ddev export-db --file=backups/pre-refactor-$(date +%Y%m%d).sql.gz

# 2. Files backup
tar -czf backups/files-$(date +%Y%m%d).tar.gz webroot/sites/default/files/

# 3. Code backup (tag current state)
git tag pre-refactor-$(date +%Y%m%d)
git push origin --tags

# 4. Config export
ddev drush config:export --destination=backups/config-$(date +%Y%m%d)

# 5. Verify backups
ls -lh backups/
```

#### ✅ Clone Production to Staging
```bash
# Create staging environment with production data
ddev export-db --file=staging.sql.gz
# Import to staging site
# Test ALL changes on staging first
```

#### ✅ Install Helper Modules
```bash
# For safe migrations
composer require drupal/backup_migrate
composer require drupal/devel
composer require drupal/field_tools
composer require drupal/migrate_tools
composer require drupal/migrate_plus

# Enable on LOCAL/STAGING only (not production)
ddev drush en backup_migrate devel field_tools migrate_tools -y
```

#### ✅ Enable Detailed Logging
```php
// settings.local.php
$config['system.logging']['error_level'] = 'verbose';
$settings['update_free_access'] = FALSE;
```

#### ✅ Notify Stakeholders
- Email content editors: "System maintenance planned"
- Set site to read-only mode during migrations
- Post maintenance banner
- Prepare rollback communication

---

## Phase 1: Quick Wins (Zero Risk)

**Risk Level:** 🟢 None
**Data Changes:** None
**Rollback Time:** Instant
**Estimated Time:** 4 hours

### 1.1 Create robots.txt

**Why Safe:** Static file, no database changes

```bash
# Create file
cat > webroot/robots.txt << 'EOF'
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /user/
Disallow: /api/internal/
Disallow: /*.pdf$

# AI Crawlers
User-agent: GPTBot
User-agent: ChatGPT-User
User-agent: CCBot
User-agent: anthropic-ai
Allow: /

Sitemap: https://sahistory.org.za/sitemap.xml
EOF

# Test locally
curl http://sahistory-web.ddev.site/robots.txt

# Commit
git add webroot/robots.txt
git commit -m "Add robots.txt for search engine guidance"
```

**Rollback:** `git revert HEAD` or delete file

---

### 1.2 Enable JSON:API

**Why Safe:** Read-only API, no data changes

```bash
# Enable module
ddev drush en jsonapi -y

# Set to read-only (safe default)
ddev drush config:set jsonapi.settings read_only true -y

# Test endpoint
curl https://sahistory-web.ddev.site/jsonapi/node/article

# Export config
ddev drush config:export -y
```

**Feature Flag:**
```php
// settings.php
// Disable JSON:API if issues arise
$config['jsonapi.settings']['read_only'] = TRUE;
// OR completely disable:
// $config['jsonapi.settings']['enabled'] = FALSE;
```

**Rollback:** `ddev drush pmu jsonapi -y`

---

### 1.3 Add Organization Schema (Site-Wide)

**Why Safe:** Adding new markup, not changing existing

**File:** `webroot/themes/custom/saho/templates/system/html.html.twig`

```twig
{# Add at end of <head> section #}
{% if not logged_in %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "name": "South African History Online",
  "alternateName": "SAHO",
  "url": "https://sahistory.org.za",
  "logo": "https://sahistory.org.za/themes/custom/saho/logo.svg",
  "description": "Towards a People's History of South Africa",
  "foundingDate": "2000",
  "sameAs": [
    "https://www.facebook.com/sahistoryonline",
    "https://twitter.com/sahistoryonline"
  ],
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "General Inquiries",
    "url": "https://sahistory.org.za/contact"
  }
}
</script>
{% endif %}
```

**Testing:**
```bash
# Validate with Google Rich Results Test
# https://search.google.com/test/rich-results

# Check HTML source
curl https://sahistory-web.ddev.site/ | grep -A 20 "application/ld+json"
```

**Rollback:** Remove `<script>` block from template

---

### 1.4 Embed Schema.org in Node Templates

**Why Safe:** Adding data, not changing existing content

**Create Service Wrapper (Non-Destructive):**

File: `webroot/themes/custom/saho/saho.theme`

```php
/**
 * Implements hook_preprocess_node().
 *
 * Add Schema.org JSON-LD to node pages (non-destructive).
 */
function saho_preprocess_node(&$variables) {
  // Only on full view mode
  if ($variables['view_mode'] !== 'full') {
    return;
  }

  $node = $variables['node'];

  // Get Schema.org service (already exists)
  try {
    $schema_service = \Drupal::service('saho_tools.schema_org');
    $schema_data = $schema_service->getNodeSchema($node);

    if ($schema_data) {
      // Add to variables for template
      $variables['schema_json'] = json_encode(
        $schema_data,
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
      );
    }
  }
  catch (\Exception $e) {
    // Log error but don't break page
    \Drupal::logger('saho')->error('Schema.org generation failed: @message', [
      '@message' => $e->getMessage(),
    ]);
  }
}
```

**Update Node Templates:**

File: `webroot/themes/custom/saho/templates/content/node.html.twig`

```twig
{# Add before closing </article> tag #}
{% if schema_json %}
  <script type="application/ld+json">
    {{ schema_json|raw }}
  </script>
{% endif %}
```

**Testing:**
```bash
# Test on local first
curl http://sahistory-web.ddev.site/node/123 | grep "application/ld+json" -A 50

# Validate JSON-LD
# https://validator.schema.org/
# https://search.google.com/test/rich-results
```

**Rollback:** Comment out preprocess function

---

## Phase 2: Field Consolidation (Low Risk)

**Risk Level:** 🟡 Low
**Data Changes:** Yes (copying only)
**Rollback Time:** Immediate (keep old fields)
**Estimated Time:** 12 hours

### Strategy: Parallel Fields

**Never delete old fields immediately:**
1. Create new unified field
2. Copy data to new field
3. Update templates to use new field (with fallback to old)
4. Monitor for 30 days
5. If stable, deprecate old field (don't delete)
6. After 90 days of zero issues, consider removal

---

### 2.1 Author Field Consolidation

**Current State:**
- `field_author` (text)
- `field_article_author` (text)
- `field_book_author` (text)

**Goal:** Single `field_author_unified` (entity reference to taxonomy)

#### Step 1: Create New Field (No Data Changes)

```bash
# Create new author taxonomy vocabulary
ddev drush generate:taxonomy --name="Authors" --vid="authors"

# Create unified field storage
ddev drush field:create node --field-name=field_author_unified \
  --field-type=entity_reference --target-type=taxonomy_term \
  --cardinality=-1

# Add to content types (alongside existing fields)
ddev drush field-attach node.article field_author_unified
ddev drush field-attach node.biography field_author_unified
ddev drush field-attach node.archive field_author_unified
ddev drush field-attach node.event field_author_unified
ddev drush field-attach node.product field_author_unified

# Export config
ddev drush config:export -y
```

#### Step 2: Create Migration Script (Read-Only First)

File: `scripts/migrate-authors-dry-run.php`

```php
<?php
/**
 * Dry-run migration of author fields to unified field.
 * NO DATA CHANGES - Only reports what WOULD happen.
 */

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

// Load Drupal
require_once 'webroot/autoload.php';
$kernel = \Drupal\Core\DrupalKernel::createFromRequest(
  \Symfony\Component\HttpFoundation\Request::createFromGlobals()
);
$kernel->boot();
$container = $kernel->getContainer();

// Dry-run mode
$dry_run = TRUE;
$batch_size = 100;
$offset = 0;

$report = [
  'total' => 0,
  'would_migrate' => 0,
  'would_skip' => 0,
  'author_terms_to_create' => [],
  'errors' => [],
];

echo "=== DRY RUN: Author Field Migration ===\n";
echo "Scanning content...\n\n";

// Field mapping
$field_map = [
  'article' => 'field_article_author',
  'biography' => 'field_article_author',
  'archive' => 'field_author',
  'event' => 'field_article_author',
  'product' => 'field_book_author',
];

foreach ($field_map as $content_type => $old_field) {
  echo "Checking content type: $content_type ($old_field)\n";

  $query = \Drupal::entityQuery('node')
    ->condition('type', $content_type)
    ->condition('status', 1)
    ->range($offset, $batch_size);

  $nids = $query->execute();
  $nodes = Node::loadMultiple($nids);

  foreach ($nodes as $node) {
    $report['total']++;

    // Check if old field has value
    if ($node->hasField($old_field) && !$node->get($old_field)->isEmpty()) {
      $author_text = $node->get($old_field)->value;

      // Would create/find term
      if (!empty($author_text)) {
        $report['would_migrate']++;
        $report['author_terms_to_create'][$author_text] =
          ($report['author_terms_to_create'][$author_text] ?? 0) + 1;

        echo "  [WOULD MIGRATE] Node {$node->id()}: '$author_text' → term\n";
      }
    }
    else {
      $report['would_skip']++;
    }
  }
}

echo "\n=== DRY RUN SUMMARY ===\n";
echo "Total nodes scanned: {$report['total']}\n";
echo "Would migrate: {$report['would_migrate']}\n";
echo "Would skip (no author): {$report['would_skip']}\n";
echo "\nUnique authors to create as terms: " . count($report['author_terms_to_create']) . "\n";

// Show top 20 authors
arsort($report['author_terms_to_create']);
$top_authors = array_slice($report['author_terms_to_create'], 0, 20, true);
echo "\nTop 20 authors (by node count):\n";
foreach ($top_authors as $author => $count) {
  echo "  $author: $count nodes\n";
}

echo "\n✅ DRY RUN COMPLETE - No data was changed\n";
echo "Review output, then run with --execute flag\n";
```

**Run Dry-Run:**
```bash
php scripts/migrate-authors-dry-run.php > logs/author-migration-dry-run.txt
cat logs/author-migration-dry-run.txt
```

#### Step 3: Execute Migration (With Rollback Support)

File: `scripts/migrate-authors-execute.php`

```php
<?php
/**
 * Execute author field migration with full logging and rollback support.
 */

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

// Config
$dry_run = FALSE; // SET TO TRUE FOR TESTING
$batch_size = 100;
$log_file = 'logs/author-migration-' . date('Y-m-d-His') . '.log';
$rollback_file = 'logs/author-migration-rollback-' . date('Y-m-d-His') . '.json';

// Initialize
$log = [];
$rollback_data = [];

function write_log($message) {
  global $log_file;
  echo $message . "\n";
  file_put_contents($log_file, $message . "\n", FILE_APPEND);
}

write_log("=== Author Field Migration ===");
write_log("Started: " . date('Y-m-d H:i:s'));
write_log("Dry run: " . ($dry_run ? 'YES' : 'NO'));

// Create or find author term
function get_author_term($author_name) {
  // Search for existing term
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'vid' => 'authors',
      'name' => trim($author_name),
    ]);

  if (!empty($terms)) {
    return reset($terms)->id();
  }

  // Create new term
  $term = Term::create([
    'vid' => 'authors',
    'name' => trim($author_name),
  ]);
  $term->save();

  write_log("  Created author term: $author_name (ID: {$term->id()})");
  return $term->id();
}

// Migrate nodes
$field_map = [
  'article' => 'field_article_author',
  'biography' => 'field_article_author',
  'archive' => 'field_author',
  'event' => 'field_article_author',
  'product' => 'field_book_author',
];

$stats = ['migrated' => 0, 'skipped' => 0, 'errors' => 0];

foreach ($field_map as $content_type => $old_field) {
  write_log("\nProcessing content type: $content_type");

  $query = \Drupal::entityQuery('node')
    ->condition('type', $content_type)
    ->condition('status', 1);

  $nids = $query->execute();
  $total = count($nids);
  write_log("  Found $total nodes");

  $processed = 0;
  foreach (array_chunk($nids, $batch_size) as $batch_nids) {
    $nodes = Node::loadMultiple($batch_nids);

    foreach ($nodes as $node) {
      try {
        // Check if already has unified field value
        if ($node->hasField('field_author_unified') &&
            !$node->get('field_author_unified')->isEmpty()) {
          $stats['skipped']++;
          continue; // Already migrated
        }

        // Get old field value
        if ($node->hasField($old_field) && !$node->get($old_field)->isEmpty()) {
          $author_text = $node->get($old_field)->value;

          if (!empty(trim($author_text))) {
            // Store rollback data
            $rollback_data[$node->id()] = [
              'nid' => $node->id(),
              'type' => $content_type,
              'old_field' => $old_field,
              'old_value' => $author_text,
              'timestamp' => time(),
            ];

            if (!$dry_run) {
              // Get or create author term
              $term_id = get_author_term($author_text);

              // Set new field value (COPY, not move)
              $node->set('field_author_unified', ['target_id' => $term_id]);

              // Keep old field intact (don't delete)
              // $node->set($old_field, NULL); // DON'T DO THIS

              $node->save();

              write_log("  ✓ Migrated node {$node->id()}: '$author_text'");
            }

            $stats['migrated']++;
          }
        }
      }
      catch (\Exception $e) {
        $stats['errors']++;
        write_log("  ✗ ERROR on node {$node->id()}: " . $e->getMessage());
      }

      $processed++;
      if ($processed % 100 == 0) {
        write_log("  Progress: $processed / $total");
      }
    }
  }
}

// Save rollback data
file_put_contents($rollback_file, json_encode($rollback_data, JSON_PRETTY_PRINT));

write_log("\n=== MIGRATION COMPLETE ===");
write_log("Migrated: {$stats['migrated']}");
write_log("Skipped: {$stats['skipped']}");
write_log("Errors: {$stats['errors']}");
write_log("Rollback data: $rollback_file");
write_log("Finished: " . date('Y-m-d H:i:s'));
```

**Execute Migration:**
```bash
# TEST FIRST with dry run
php scripts/migrate-authors-execute.php --dry-run

# Review logs
cat logs/author-migration-*.log

# Execute for real
php scripts/migrate-authors-execute.php

# Verify migration
ddev drush sqlq "SELECT COUNT(*) FROM node__field_author_unified"
```

#### Step 4: Update Templates (With Fallback)

File: `webroot/themes/custom/saho/templates/content/node--article.html.twig`

```twig
{# Author display with fallback to old field #}
{% if node.field_author_unified.0 %}
  {# New unified field #}
  <div class="article-author">
    By: {{ content.field_author_unified }}
  </div>
{% elseif node.field_article_author.value %}
  {# Fallback to old field during migration #}
  <div class="article-author">
    By: {{ node.field_article_author.value }}
  </div>
{% endif %}
```

**Why Safe:** Templates check both fields - no content disappears

#### Step 5: Monitoring Period (30 Days)

```bash
# Create monitoring script
cat > scripts/monitor-author-field-usage.php << 'EOF'
<?php
/**
 * Monitor usage of old vs. new author fields.
 */

// Check old fields still being used
$old_fields = [
  'field_article_author',
  'field_author',
  'field_book_author',
];

echo "=== Author Field Usage Report ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$db = \Drupal::database();

// Count nodes using old fields
foreach ($old_fields as $field) {
  $count = $db->query("SELECT COUNT(*) FROM {node__" . $field . "}")->fetchField();
  echo "$field: $count nodes\n";
}

// Count nodes using new field
$new_count = $db->query("SELECT COUNT(*) FROM {node__field_author_unified}")->fetchField();
echo "\nfield_author_unified: $new_count nodes\n";

// Check for nodes with ONLY old field (migration incomplete)
echo "\nNodes with old field but NO unified field:\n";
foreach ($old_fields as $field) {
  $query = "SELECT COUNT(DISTINCT entity_id) FROM {node__" . $field . "} old
            WHERE NOT EXISTS (
              SELECT 1 FROM {node__field_author_unified} new
              WHERE new.entity_id = old.entity_id
            )";
  $count = $db->query($query)->fetchField();
  echo "$field: $count nodes need migration\n";
}
EOF

# Run daily for 30 days
php scripts/monitor-author-field-usage.php
```

#### Step 6: Rollback Procedure

File: `scripts/rollback-author-migration.php`

```php
<?php
/**
 * Rollback author field migration.
 * Removes field_author_unified values, keeps old fields intact.
 */

// Load rollback data
$rollback_file = $argv[1] ?? null;
if (!$rollback_file || !file_exists($rollback_file)) {
  die("Usage: php rollback-author-migration.php <rollback-file.json>\n");
}

$rollback_data = json_decode(file_get_contents($rollback_file), true);

echo "=== ROLLBACK Author Field Migration ===\n";
echo "Rollback file: $rollback_file\n";
echo "Nodes to rollback: " . count($rollback_data) . "\n";
echo "Proceed? (yes/no): ";

$confirm = trim(fgets(STDIN));
if ($confirm !== 'yes') {
  die("Rollback cancelled\n");
}

$rolled_back = 0;
foreach ($rollback_data as $data) {
  $node = \Drupal\node\Entity\Node::load($data['nid']);

  if ($node) {
    // Clear new field
    $node->set('field_author_unified', []);
    $node->save();
    $rolled_back++;

    echo "  Rolled back node {$data['nid']}\n";
  }
}

echo "\n✅ Rollback complete: $rolled_back nodes\n";
echo "Old field values were never changed, so data is intact\n";
```

**Execute Rollback (if needed):**
```bash
php scripts/rollback-author-migration.php logs/author-migration-rollback-2026-02-12-143022.json
```

---

### 2.2 Image Field Consolidation

**Similar approach to author fields:**
1. Create `field_image_unified` (media reference)
2. Migrate images to Media Library
3. Copy references to new field
4. Update templates with fallback
5. Monitor for 30 days
6. Deprecate old fields

**Script structure:** (Same pattern as author migration)

---

## Phase 3: Content Type Migration (Medium Risk)

**Risk Level:** 🟠 Medium
**Data Changes:** Yes (creating new content)
**Rollback Time:** Hours (delete migrated content)
**Estimated Time:** 20 hours

### Strategy: Shadow Content Types

**Never delete old content types immediately:**
1. Create new content type (e.g., `gallery_v2`)
2. Migrate data to new content type
3. Create redirects from old → new
4. Update views/blocks to show both types
5. Hide old content type from menus (don't delete)
6. After 90 days, archive old content type

---

### 3.1 Node Gallery Migration

**Current:** `node_gallery_gallery` + `node_gallery_item`
**Target:** Standard media library galleries

#### Step 1: Audit Existing Galleries

```bash
# Count galleries and items
ddev drush sqlq "SELECT COUNT(*) FROM node WHERE type='node_gallery_gallery' AND status=1"
ddev drush sqlq "SELECT COUNT(*) FROM node WHERE type='node_gallery_item' AND status=1"

# Export gallery data for backup
ddev drush sql-query "SELECT * FROM node WHERE type='node_gallery_gallery'" > backups/node-gallery-audit.csv
```

#### Step 2: Create New Gallery Content Type

```yaml
# config/staging/node.type.gallery.yml
langcode: en
status: true
dependencies: {  }
name: 'Photo Gallery'
type: gallery
description: 'Collection of images using Media Library (replaces Node Gallery)'
help: ''
new_revision: true
preview_mode: 1
display_submitted: true
```

```bash
# Import config
ddev drush config:import staging -y

# Add media field
ddev drush field:create node --field-name=field_gallery_images \
  --field-type=entity_reference --target-type=media \
  --cardinality=-1
```

#### Step 3: Migration Script (Non-Destructive)

File: `scripts/migrate-node-gallery.php`

```php
<?php
/**
 * Migrate Node Gallery content to new Gallery content type.
 *
 * Strategy:
 * 1. Create NEW gallery nodes (don't modify old)
 * 2. Migrate images to Media Library
 * 3. Link new nodes to new gallery
 * 4. Create 301 redirects old → new
 * 5. Keep old galleries for rollback
 */

$dry_run = TRUE; // Safety first

// Load old galleries
$query = \Drupal::entityQuery('node')
  ->condition('type', 'node_gallery_gallery')
  ->condition('status', 1);

$gallery_nids = $query->execute();
$galleries = \Drupal\node\Entity\Node::loadMultiple($gallery_nids);

foreach ($galleries as $old_gallery) {
  echo "Processing gallery: {$old_gallery->getTitle()}\n";

  if (!$dry_run) {
    // Create NEW gallery node (don't modify old)
    $new_gallery = \Drupal\node\Entity\Node::create([
      'type' => 'gallery',
      'title' => $old_gallery->getTitle() . ' (Migrated)',
      'body' => $old_gallery->get('body')->value,
      'status' => 1,
      'uid' => $old_gallery->getOwnerId(),
      'created' => $old_gallery->getCreatedTime(),
    ]);

    // Load gallery items
    $item_query = \Drupal::entityQuery('node')
      ->condition('type', 'node_gallery_item')
      ->condition('node_gallery_ref_1', $old_gallery->id());

    $item_nids = $item_query->execute();
    $items = \Drupal\node\Entity\Node::loadMultiple($item_nids);

    $media_ids = [];
    foreach ($items as $item) {
      // Create media entity from image
      if ($item->hasField('node_gallery_media') &&
          !$item->get('node_gallery_media')->isEmpty()) {

        $file = $item->get('node_gallery_media')->entity;
        if ($file) {
          $media = \Drupal\media\Entity\Media::create([
            'bundle' => 'image',
            'uid' => $item->getOwnerId(),
            'name' => $item->getTitle(),
            'field_media_image' => [
              'target_id' => $file->id(),
              'alt' => $item->getTitle(),
            ],
          ]);
          $media->save();
          $media_ids[] = $media->id();
        }
      }
    }

    // Attach media to new gallery
    $new_gallery->set('field_gallery_images', $media_ids);
    $new_gallery->save();

    // Create redirect old → new
    \Drupal::service('redirect.repository')->create(
      '/node/' . $old_gallery->id(),
      'internal:/node/' . $new_gallery->id(),
      'en',
      301
    )->save();

    echo "  ✓ Created new gallery: node/{$new_gallery->id()}\n";
    echo "  ✓ Migrated " . count($media_ids) . " images\n";
    echo "  ✓ Created redirect\n";

    // DON'T DELETE OLD GALLERY
    // $old_gallery->delete(); // NO!
  }
}

echo "\n" . ($dry_run ? "DRY RUN COMPLETE\n" : "MIGRATION COMPLETE\n");
```

**Rollback Strategy:**
```php
<?php
// Delete NEW galleries created by migration
// Old galleries are untouched, so no data loss
$query = \Drupal::entityQuery('node')
  ->condition('type', 'gallery')
  ->condition('title', '% (Migrated)', 'LIKE');

$nids = $query->execute();
foreach ($nids as $nid) {
  $node = \Drupal\node\Entity\Node::load($nid);
  $node->delete();
  echo "Deleted migrated gallery: $nid\n";
}

// Delete redirects
\Drupal::database()->delete('redirect')
  ->condition('redirect_source__path', '/node/%', 'LIKE')
  ->execute();

echo "Rollback complete - old galleries intact\n";
```

---

## Phase 4: Legacy Cleanup (High Risk)

**Risk Level:** 🔴 High
**Data Changes:** Yes (potential deletion)
**Rollback Time:** Days (restore from backup)
**Estimated Time:** 8 hours

### Strategy: Archive Before Delete

**Only after 90+ days of stable operation:**

#### Step 1: Archive Content

```bash
# Export old content types to archive
ddev drush sql-query "SELECT * FROM node WHERE type='node_gallery_gallery'" \
  > archives/node_gallery_gallery-$(date +%Y%m%d).sql

# Export field data
ddev drush sql-query "SELECT * FROM node__field_article_author" \
  > archives/field_article_author-$(date +%Y%m%d).sql
```

#### Step 2: Mark as Deprecated

```php
// Don't delete fields yet - mark as deprecated
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_article_author');
if ($field_storage) {
  $field_storage->setDescription('DEPRECATED: Use field_author_unified instead. Scheduled for removal: 2026-06-01');
  $field_storage->save();
}

// Hide from content add forms
$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.article.default');

$form_display->removeComponent('field_article_author')->save();
```

#### Step 3: Final Deletion (After 90 Days)

```bash
# Only after:
# - 90+ days of stable operation
# - Zero usage confirmed
# - Archives verified
# - Stakeholder approval
# - Full backup created

ddev drush field:delete field_article_author
ddev drush field:delete field_book_author

# Delete old content types
ddev drush entity:delete node_type node_gallery_gallery
ddev drush entity:delete node_type node_gallery_item
```

---

## Rollback Procedures

### Complete System Rollback

```bash
# Stop all services
ddev stop

# Restore database
ddev import-db --src=backups/pre-refactor-20260212.sql.gz

# Restore files
tar -xzf backups/files-20260212.tar.gz -C webroot/sites/default/

# Restore config
cp -r backups/config-20260212/* config/sync/
ddev drush config:import -y

# Clear all caches
ddev drush cr

# Restart
ddev start

# Verify
ddev drush status
```

### Partial Rollback (Single Phase)

Each phase has specific rollback scripts:
- Phase 1: Revert commits, delete files
- Phase 2: Run rollback scripts (keeps old data)
- Phase 3: Delete new content types (old still exists)
- Phase 4: Restore from archive

---

## Testing & Verification

### Automated Tests

Create test suite for each phase:

File: `tests/Functional/AuthorFieldMigrationTest.php`

```php
<?php

namespace Drupal\Tests\saho\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests author field migration.
 */
class AuthorFieldMigrationTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  public function testAuthorFieldMigration() {
    // Create test article with old field
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Article',
      'field_article_author' => 'John Doe',
    ]);

    // Run migration
    $this->runMigration();

    // Reload node
    $node = $this->drupalGetNodeByTitle('Test Article', TRUE);

    // Verify new field populated
    $this->assertFalse($node->get('field_author_unified')->isEmpty());

    // Verify old field still exists (non-destructive)
    $this->assertEquals('John Doe', $node->get('field_article_author')->value);

    // Verify term created
    $term_id = $node->get('field_author_unified')->target_id;
    $term = \Drupal\taxonomy\Entity\Term::load($term_id);
    $this->assertEquals('John Doe', $term->getName());
  }
}
```

### Manual Testing Checklist

Before ANY production deployment:

- [ ] Test on database clone
- [ ] Run dry-run migration
- [ ] Review logs for errors
- [ ] Verify data integrity (spot checks)
- [ ] Test rollback procedure
- [ ] Test frontend rendering
- [ ] Test search results
- [ ] Test views/blocks
- [ ] Test API endpoints
- [ ] Performance testing (no slowdown)
- [ ] Accessibility testing (no regression)
- [ ] Mobile testing
- [ ] Cross-browser testing
- [ ] Load testing (if high traffic expected)
- [ ] Backup restoration test

---

## Monitoring & Alerts

### Set Up Monitoring

```php
// web/modules/custom/saho_monitoring/saho_monitoring.module

/**
 * Monitor migration progress and issues.
 */
function saho_monitoring_cron() {
  $messenger = \Drupal::messenger();

  // Check for nodes with old fields but no new field
  $query = \Drupal::database()->query("
    SELECT COUNT(DISTINCT old.entity_id)
    FROM {node__field_article_author} old
    LEFT JOIN {node__field_author_unified} new ON new.entity_id = old.entity_id
    WHERE new.entity_id IS NULL
  ");

  $unmigrated = $query->fetchField();

  if ($unmigrated > 0) {
    \Drupal::logger('saho_monitoring')->warning(
      '@count nodes still using old author field',
      ['@count' => $unmigrated]
    );

    // Send email to admins if > 100
    if ($unmigrated > 100) {
      $mailManager = \Drupal::service('plugin.manager.mail');
      $mailManager->mail(
        'saho_monitoring',
        'migration_alert',
        'admin@sahistory.org.za',
        'en',
        ['unmigrated' => $unmigrated]
      );
    }
  }
}
```

### Watchdog Logging

```php
// Log every data change
\Drupal::logger('saho_migration')->info('Migrated node @nid from @old to @new', [
  '@nid' => $node->id(),
  '@old' => 'field_article_author',
  '@new' => 'field_author_unified',
]);
```

---

## Communication Plan

### Stakeholder Updates

**Before Migration:**
- Email content editors: "System improvements coming, no action needed"
- Post site banner: "Maintenance window: [date/time]"
- Update status page

**During Migration:**
- Real-time status updates
- Progress dashboard
- Issue log accessible to team

**After Migration:**
- Success report with metrics
- "What changed" guide for editors
- Training on new features (if applicable)

---

## Success Criteria

### Phase 1 (Quick Wins)
- [ ] robots.txt live and validated
- [ ] JSON:API enabled and tested
- [ ] Schema.org in HTML validated by Google
- [ ] No errors in logs
- [ ] Zero user complaints

### Phase 2 (Field Consolidation)
- [ ] 100% data integrity verified
- [ ] Old fields still readable (fallback works)
- [ ] New fields populated correctly
- [ ] Performance unchanged or improved
- [ ] Zero data loss

### Phase 3 (Content Type Migration)
- [ ] All content migrated successfully
- [ ] 301 redirects working
- [ ] Search results show new content
- [ ] Old content still accessible (not deleted)
- [ ] Rollback tested and ready

### Phase 4 (Legacy Cleanup)
- [ ] 90+ days stable operation
- [ ] Zero usage of deprecated fields
- [ ] Archives created and verified
- [ ] Stakeholder approval obtained
- [ ] Final backup created
- [ ] Deletion executed successfully

---

## Timeline

### Conservative Approach (Recommended)

| Phase | Duration | Monitoring Period | Total |
|-------|----------|-------------------|-------|
| Phase 1 | 1 day | 7 days | 8 days |
| Phase 2 | 3 days | 30 days | 33 days |
| Phase 3 | 5 days | 30 days | 35 days |
| Phase 4 | 2 days | 90 days | 92 days |
| **TOTAL** | **11 days** | **157 days** | **~6 months** |

### Aggressive Approach (Higher Risk)

| Phase | Duration | Monitoring Period | Total |
|-------|----------|-------------------|-------|
| Phase 1 | 4 hours | 2 days | 2 days |
| Phase 2 | 2 days | 14 days | 16 days |
| Phase 3 | 3 days | 14 days | 17 days |
| Phase 4 | 1 day | 30 days | 31 days |
| **TOTAL** | **6.5 days** | **60 days** | **~2 months** |

**Recommendation:** Start with conservative approach for Phase 1-2, then evaluate risk tolerance for Phase 3-4.

---

## Appendix A: Emergency Contacts

- **Lead Developer:** [Name] - [Phone]
- **Database Admin:** [Name] - [Phone]
- **Hosting Provider:** [Support Contact]
- **Backup Admin:** [Name] - [Phone]

## Appendix B: Backup Locations

- Database backups: `backups/db/`
- File backups: `backups/files/`
- Config backups: `backups/config/`
- Migration logs: `logs/`
- Rollback data: `logs/*-rollback-*.json`

## Appendix C: Testing Environments

- **Production:** https://sahistory.org.za (NEVER test here)
- **Staging:** https://staging.sahistory.org.za (Clone of production)
- **Development:** https://sahistory-web.ddev.site (Local)
- **Testing:** https://test.sahistory.org.za (QA environment)

---

**Last Updated:** 2026-02-12
**Next Review:** Weekly during active migration
**Document Owner:** Development Team
**Version:** 1.0.0
