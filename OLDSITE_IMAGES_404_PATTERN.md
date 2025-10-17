# oldsite_images 404 Pattern Analysis

## Problem Summary

**63 body content references** point to non-existent `oldsite_images/` directory, but files actually exist in other locations with WebP versions already generated.

## Evidence

### Directory Does Not Exist
```bash
$ ls webroot/sites/default/files/oldsite_images/
ls: cannot access 'webroot/sites/default/files/oldsite_images/': No such file or directory
```

### Files Referenced in Body Content
```sql
SELECT COUNT(*) FROM node__body
WHERE body_value LIKE '%/sites/default/files/oldsite_images/%'
-- Result: 63 records
```

### Sample References Found
- `oldsite_images/1912_sannc_leaders.jpg`
- `oldsite_images/hertzog.jpg`
- `oldsite_images/ncape.gif`
- `oldsite_images/1960_sharpeville.jpg`
- `oldsite_images/apartheid_pass.jpg`
- Plus 22 more files

### Where Files Actually Exist

| Referenced As | Actually Located At | WebP Available |
|--------------|---------------------|----------------|
| oldsite_images/1912_sannc_leaders.jpg | u7/1912_sannc_leaders.jpg | ✓ Yes |
| oldsite_images/hertzog.jpg | images_new/hertzog.jpg | ✓ Yes |
| oldsite_images/ncape.gif | images_new/ncape.gif | ✓ No (GIF) |
| oldsite_images/apartheid_pass.jpg | article_pics/apartheid_pass.jpg | ✓ Yes |

**Pattern**: Files exist in multiple locations, most commonly in `images_new/`

## Root Cause

During Drupal 7→8 migration:
1. Physical files moved from `oldsite_images/` to various new directories
2. Body content HTML was NOT updated (still has hardcoded `<img src="/sites/default/files/oldsite_images/...">`)
3. WebP versions generated next to actual files
4. Browsers request WebP at old path → 404

## Current Impact

### Example 404s (from historical analysis)
- `/sites/default/files/oldsite_images/hertzog.webp` - 1,412 404 hits
- `/sites/default/files/oldsite_images/ncape.webp` - 1,377 404 hits
- Many others...

### Affected Content
63 nodes have inline `<img>` tags pointing to non-existent oldsite_images directory

## Solution Strategy

### Option 1: Update Body Content (COMPLEX)

Challenge: Files migrated to DIFFERENT directories based on context
- Some → `images_new/`
- Some → `u7/`
- Some → `article_pics/`
- Some → root directory

Would need to:
1. Extract each filename from body content
2. Find actual location for EACH file
3. Update body with correct path
4. Test thoroughly

**Risk**: Medium - changing user content requires careful verification

### Option 2: Create File Mapping & Batch Update (SAFER)

1. Build a mapping table:
   ```
   oldsite_images/hertzog.jpg → images_new/hertzog.jpg
   oldsite_images/ncape.gif → images_new/ncape.gif
   oldsite_images/1912_sannc_leaders.jpg → u7/1912_sannc_leaders.jpg
   ```

2. Create update hook with Batch API:
   - Process each body field
   - Look up correct path for each oldsite_images reference
   - Replace with correct path
   - Only update if file verified to exist

**Risk**: Low - can verify each replacement before applying

### Option 3: Create Redirects/Symlinks (QUICKEST)

Create symbolic links:
```bash
mkdir -p webroot/sites/default/files/oldsite_images
ln -s ../images_new/hertzog.jpg webroot/sites/default/files/oldsite_images/hertzog.jpg
ln -s ../u7/1912_sannc_leaders.jpg webroot/sites/default/files/oldsite_images/1912_sannc_leaders.jpg
# etc...
```

**Risk**: Very Low - non-destructive, easily reversible

**Problem**: Would need to manually create WebP versions OR symlink those too

## Recommended Approach

**Phase 1: Quick Fix with Symlinks**
1. Extract all 27 unique filenames from oldsite_images references
2. Find actual location of each file
3. Create symlinks from oldsite_images/ to actual locations
4. Create corresponding WebP symlinks
5. Test that 404s are resolved

**Phase 2: Permanent Fix (Future)**
1. Update body content to reference actual file locations
2. Remove symlinks
3. Ensures long-term maintainability

## Next Steps

1. Extract complete list of all 27 oldsite_images filenames
2. Create mapping script to find actual locations
3. Generate symlink commands
4. Test on one file first
5. If successful, apply to all 27 files
6. Monitor 404 logs for reduction

## Verification Plan

Before fix:
```bash
curl -I https://local.sahistory-web.ddev.site/sites/default/files/oldsite_images/hertzog.webp
# Returns: 404
```

After fix:
```bash
curl -I https://local.sahistory-web.ddev.site/sites/default/files/oldsite_images/hertzog.webp
# Should return: 200
```

## Files Affected

All 27 unique files referenced in 63 body content records:
- 1912_sannc_leaders.jpg
- 1913_passive_resistance.jpg
- 1919_icu.jpg
- 1919_mineworkers_pass_strike.jpg
- 1959_cato_manor.jpg
- 1960_sharpeville.jpg
- 1963_rivonia_winnie.jpg
- 1977_biko_funeral.jpg
- 1985_mineworkers.jpg
- apartheid_pass.jpg
- architects.jpg
- eastcape-map.gif
- Gold.jpg
- hertzog.jpg
- hosteldwellers.jpg
- Lansing_Botha.jpg
- maxeke-c.jpg
- ncape.jpg
- Oldsa.jpg
- pass-check.jpg
- pl_passbook.jpg
- RDM_witsandworkers.jpg
- SADF_patrols.jpg
- separate_amenities.jpg
- tsafendas-d.jpg
- za-orange.jpg
- za-t1857.jpg
