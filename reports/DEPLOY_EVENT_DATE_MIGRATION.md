# Event Date Field Migration - Deployment Manual

**Branch:** `SAHO-event-date-field-consolidation`
**Purpose:** Consolidate `field_this_day_in_history_3` and `field_this_day_in_history_date_2` into single `field_event_date`

---

## Overview

This migration consolidates two legacy date fields into a single, properly-named field. The migration preserves all 3,507 event dates with 5 known corrections applied automatically.

### What Changes

| Component | Change |
|-----------|--------|
| **New field** | `field_event_date` (datetime, date-only) |
| **Deleted fields** | `field_this_day_in_history_3`, `field_this_day_in_history_date_2` |
| **Updated modules** | saho_timeline, saho_utils/tdih, saho_tools |
| **Updated templates** | node--event.html.twig, saho-timeline-event.html.twig |
| **Updated views** | front_page_twihs |

---

## Pre-Deployment Checklist

- [ ] Database backup taken
- [ ] All changes committed to branch
- [ ] Code review completed
- [ ] Staging deployment tested successfully

---

## Deployment Steps

### Step 1: Take Database Backup (CRITICAL)

```bash
# On production server
drush sql-dump --gzip > /backups/pre-event-date-migration-$(date +%Y%m%d-%H%M%S).sql.gz
```

### Step 2: Enable Maintenance Mode

```bash
drush state:set system.maintenance_mode 1 --input-format=integer
drush cr
```

### Step 3: Deploy Code

```bash
# Pull the branch
git fetch origin
git checkout SAHO-event-date-field-consolidation
git pull origin SAHO-event-date-field-consolidation

# Install dependencies
composer install --no-dev
```

### Step 4: Create New Field (BEFORE config import)

The new field must exist before we can migrate data to it:

```bash
# Create field storage
drush php:eval "
\$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_event_date');
if (!\$field_storage) {
    \$field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
        'field_name' => 'field_event_date',
        'entity_type' => 'node',
        'type' => 'datetime',
        'settings' => ['datetime_type' => 'date'],
    ]);
    \$field_storage->save();
    echo \"Created field_event_date storage\n\";
} else {
    echo \"Field storage already exists\n\";
}
"

# Create field instance
drush php:eval "
\$field = \Drupal\field\Entity\FieldConfig::loadByName('node', 'event', 'field_event_date');
if (!\$field) {
    \$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_event_date');
    \$field = \Drupal\field\Entity\FieldConfig::create([
        'field_storage' => \$field_storage,
        'bundle' => 'event',
        'label' => 'Event Date',
        'description' => 'The date this historical event occurred.',
    ]);
    \$field->save();
    echo \"Created field_event_date field\n\";
} else {
    echo \"Field already exists\n\";
}
"
```

### Step 5: Run Database Updates (Migrates Data)

```bash
drush updb -y
```

This runs:
- `saho_utils_update_10001`: Migrates all 3,507 dates from old fields to new field
- `saho_utils_update_10002`: Applies 5 known date corrections

**Expected output:**
```
Migrated 3507 event dates to field_event_date. Skipped 0 (already had values).
Verified/corrected 5 event dates.
```

### Step 6: Verify Migration Before Deleting Old Fields

```bash
# Check record count
drush sqlq "SELECT COUNT(*) FROM node__field_event_date"
# Expected: 3507

# Verify the 5 corrected records
drush sqlq "SELECT entity_id, field_event_date_value FROM node__field_event_date WHERE entity_id IN (11418, 11888, 12176, 93949, 124768)"
# Expected:
# 11418 | 1917-12-12
# 11888 | 1960-03-07
# 12176 | 1972-05-24
# 93949 | 1965-11-05
# 124768 | 1982-02-22

# Check orphan record migrated
drush sqlq "SELECT entity_id, field_event_date_value FROM node__field_event_date WHERE entity_id = 13474"
# Expected: 13474 | 2000-01-29
```

### Step 7: Import Configuration (Deletes Old Fields)

```bash
drush cim -y
```

This will:
- Delete `field.storage.node.field_this_day_in_history_3`
- Delete `field.storage.node.field_this_day_in_history_date_2`
- Delete `field.field.node.event.field_this_day_in_history_3`
- Delete `field.field.node.event.field_this_day_in_history_date_2`
- Update all view displays to use `field_event_date`
- Update views configuration

### Step 8: Clear Cache

```bash
drush cr
```

### Step 9: Disable Maintenance Mode

```bash
drush state:set system.maintenance_mode 0 --input-format=integer
drush cr
```

---

## Post-Deployment Verification

### 1. Check Event Node Display

Visit several event nodes and verify the date displays correctly:

```
/article/[any-event-nid]
```

### 2. Check This Day in History

```
/thisday
```

Verify events appear for the current date.

### 3. Check Timeline

```
/timeline
```

Verify timeline loads and displays events with correct dates.

### 4. Check Front Page TWIHS Block

Visit the front page and verify the "This Day in History" section displays events.

### 5. Verify Old Tables Removed

```bash
drush sqlq "SHOW TABLES LIKE '%this_day%'"
# Expected: No results (tables should be deleted)
```

---

## Rollback Procedure

If issues occur, rollback immediately:

### Quick Rollback (Config Only)

```bash
# Restore previous git state
git checkout main

# Import old config
drush cim -y
drush cr
```

### Full Rollback (With Data)

```bash
# Restore database from backup
gunzip < /backups/pre-event-date-migration-YYYYMMDD-HHMMSS.sql.gz | drush sql:cli

# Restore previous git state
git checkout main

# Clear cache
drush cr
```

---

## Files Changed

### Configuration (config/sync/)

| File | Change |
|------|--------|
| `field.storage.node.field_event_date.yml` | **NEW** - Field storage definition |
| `field.field.node.event.field_event_date.yml` | **NEW** - Field instance |
| `field.storage.node.field_this_day_in_history_3.yml` | **DELETED** |
| `field.storage.node.field_this_day_in_history_date_2.yml` | **DELETED** |
| `field.field.node.event.field_this_day_in_history_3.yml` | **DELETED** |
| `field.field.node.event.field_this_day_in_history_date_2.yml` | **DELETED** |
| `core.entity_form_display.node.event.default.yml` | Updated to use new field |
| `core.entity_view_display.node.event.*.yml` | Updated to use new field (7 files) |
| `views.view.front_page_twihs.yml` | Updated field reference |

### Custom Modules

| File | Change |
|------|--------|
| `saho_utils/saho_utils.install` | **NEW** - Migration update hooks |
| `saho_utils/saho_utils.module` | Updated field references |
| `saho_utils/tdih/src/Service/NodeFetcher.php` | Updated to query `field_event_date` |
| `saho_utils/tdih/src/Plugin/Block/TdihBlock.php` | Updated field references |
| `saho_utils/tdih/src/Plugin/Block/TdihInteractiveBlock.php` | Updated field references |
| `saho_utils/tdih/src/Form/DayMonthDateForm.php` | Updated field references |
| `saho_timeline/src/Service/TimelineEventService.php` | Updated to use `field_event_date` |
| `saho_timeline/src/Service/TimelineFilterService.php` | Updated field references |
| `saho_timeline/src/Controller/TimelineController.php` | Updated field references |
| `saho_timeline/src/Controller/TimelineApiController.php` | Updated field references |
| `saho_tools/src/Service/CitationService.php` | Updated field references |

### Templates

| File | Change |
|------|--------|
| `saho/templates/content/node--event.html.twig` | Updated to use `field_event_date` |
| `saho_timeline/templates/saho-timeline-event.html.twig` | Updated field reference |

---

## Data Migration Details

### Records Migrated

| Category | Count | Source |
|----------|-------|--------|
| Matching records | 3,443 | `field_this_day_in_history_3` |
| Only in field_3 | 59 | `field_this_day_in_history_3` |
| Only in field_2 | 1 | `field_this_day_in_history_date_2` |
| Date corrections | 5 | Manual fix in update_10002 |
| **Total** | **3,507** | |

### Corrected Records

| NID | Title | Wrong Value | Correct Value |
|-----|-------|-------------|---------------|
| 11418 | Benjamin Magson Kies birth | 1919-12-12 | 1917-12-12 |
| 11888 | SA Native mineworker article | 1970-01-01 | 1960-03-07 |
| 12176 | SAA hijacking | 1940-05-24 | 1972-05-24 |
| 93949 | Ian Smith emergency | 1955-11-05 | 1965-11-05 |
| 124768 | Jack Parow birth | 1982-02-21 | 1982-02-22 |

---

## Troubleshooting

### "Target table does not exist" error

The field must be created before running updates:

```bash
# Create field manually
drush php:eval "
\$field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    'field_name' => 'field_event_date',
    'entity_type' => 'node',
    'type' => 'datetime',
    'settings' => ['datetime_type' => 'date'],
]);
\$field_storage->save();
"
```

### "Source table does not exist" error

This means config was imported before running updates. Restore database and try again with correct order.

### Events not showing on This Day in History

1. Check cache is cleared: `drush cr`
2. Verify data migrated: `drush sqlq "SELECT COUNT(*) FROM node__field_event_date"`
3. Check NodeFetcher queries the correct field

### Timeline not loading

1. Check browser console for JavaScript errors
2. Verify TimelineEventService uses `field_event_date`
3. Check API endpoint: `/api/timeline/events`

---

## Contact

For issues during deployment, contact the development team.
