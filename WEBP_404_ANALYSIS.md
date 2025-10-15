# WebP 404 Error Analysis & Solution

## Executive Summary

WebP 404 errors are caused by **THREE distinct problems**, not one:

1. **Database URI mismatch** - file_managed table has wrong paths (20,332 files affected)
2. **Missing WebP conversions** - only 50.4% of images have WebP versions
3. **Hardcoded old paths** - body content references non-existent directories

## Problem 1: Database URI Mismatch (PRIMARY ISSUE)

### Evidence
```sql
-- Database says:
file_managed: public://images/sowetouprising.png

-- Actual file location:
webroot/sites/default/files/images_new/sowetouprising.png

-- Verification:
$ test -f webroot/sites/default/files/images/sowetouprising.png
File NOT at database path - database is wrong!
```

### Impact
- **20,332 file_managed entries** point to `public://images/*`
- WebP files generated next to ACTUAL files in `images_new/`
- Drupal looks for WebP based on DATABASE path → 404

### 404 Examples
- `/sites/default/files/images/sowetouprising.webp` (2,624 hits) → Should be `images_new/`
- `/sites/default/files/images/Plaatje_on_bike.webp` (1,992 hits) → Should be `images_new/`
- `/sites/default/files/images/map_beaumont_report_small.webp` (1,981 hits) → Should be `images_new/`

### Root Cause
During Drupal 7→8 migration, physical files were moved to `images_new/` but `file_managed` table entries were not updated.

### Solution Required
Update `file_managed` table:
```sql
UPDATE file_managed
SET uri = REPLACE(uri, 'public://images/', 'public://images_new/')
WHERE uri LIKE 'public://images/%'
AND uri NOT LIKE '%images_new%'
```

**MUST verify actual file exists before updating!**

## Problem 2: Missing WebP Conversions

### Evidence
```bash
# WebP conversion status:
Total images (JPG/PNG): 110,439
WebP files created: 55,625
Conversion rate: 50.4%
```

### Example
```bash
# File exists:
$ ls webroot/sites/default/files/bio_pics/ReggieWilliams_1.jpg
-rw-rw-r-- 1 mno mno 87K Aug 23 23:03 ReggieWilliams_1.jpg

# WebP version: MISSING
$ ls webroot/sites/default/files/bio_pics/ReggieWilliams_1.webp
(does not exist)

# Result: 4,216 404 errors
```

### Impact
- 54,814 images (49.6%) have NO WebP version
- Browsers request WebP, get 404
- Fallback to original images works but generates error logs

### Solution Required
Run WebP conversion on missing files:
```bash
ddev drush saho:webp-convert
```

## Problem 3: Hardcoded Old Paths in Body Content

### Evidence
```sql
-- Directory does not exist:
$ ls webroot/sites/default/files/oldsite_images/
(does not exist)

-- Not in file_managed:
SELECT COUNT(*) FROM file_managed WHERE uri LIKE 'public://oldsite_images/%'
0

-- But getting 404s:
/sites/default/files/oldsite_images/hertzog.webp (1,412 hits)
/sites/default/files/oldsite_images/ncape.webp (1,377 hits)
```

### Impact
- Unknown number of body fields reference old hardcoded paths
- These paths were never in file_managed (direct HTML references)
- No corresponding files or WebP versions exist

### Solution Required
Need to:
1. Search body fields for hardcoded old paths
2. Find where actual files are located
3. Update body content OR create redirects

## Recommended Action Plan

### Phase 1: Fix Database URIs (Highest Impact)
1. Query file_managed for entries where URI != actual file location
2. Verify actual files exist at new location
3. Update file_managed URIs to match reality
4. Clear caches
5. Test: Verify WebP requests now resolve correctly

### Phase 2: Generate Missing WebP Files
1. Run: `ddev drush saho:webp-convert`
2. Monitor conversion progress
3. Fix any double-extension issues
4. Verify 404s decrease

### Phase 3: Fix Hardcoded Paths
1. Search body fields for old directory references
2. Locate actual files
3. Update body content OR implement redirects

## Validation Tests

Before implementing, test with sample file:

```bash
# 1. Check database
ddev drush sqlq "SELECT fid, uri FROM file_managed WHERE fid = 431296"
# Returns: public://images/sowetouprising.png

# 2. Check actual file
ls webroot/sites/default/files/images/sowetouprising.png
# Returns: File not found

ls webroot/sites/default/files/images_new/sowetouprising.png
# Returns: File exists!

# 3. Check WebP
ls webroot/sites/default/files/images_new/sowetouprising.webp
# Returns: File exists!

# 4. Current 404:
curl -I https://local.sahistory-web.ddev.site/sites/default/files/images/sowetouprising.webp
# Returns: 404

# 5. After fix should work:
curl -I https://local.sahistory-web.ddev.site/sites/default/files/images_new/sowetouprising.webp
# Should return: 200
```

## Risk Assessment

**Low Risk**: Fixing file_managed URIs to match actual file locations
- Database already wrong, fixing it aligns with reality
- Can verify each file exists before updating
- Reversible if needed

**Low Risk**: Running WebP conversion
- Non-destructive operation
- Creates new files, doesn't modify originals

**Medium Risk**: Updating body content
- Changes user-visible content
- Need to verify each replacement
- Should backup first

## Next Steps

1. Review this analysis
2. Test solution on staging with sample files
3. Implement Phase 1 fix with proper validation
4. Monitor 404 logs to confirm reduction
5. Proceed to Phase 2 and 3 based on results
