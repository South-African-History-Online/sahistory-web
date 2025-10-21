# 404 Pattern Extension Summary

## What We've Accomplished

### ✓ Phase 1: Fixed Database URIs (COMPLETED)
- **Problem**: 6,132 file_managed entries pointed to wrong directory
- **Solution**: Batch update hook with file existence verification
- **Impact**: Real content pages now serve WebP correctly
  - Nelson Mandela biography (Node 65300)
  - 28+ biography and article pages
  - Estimated 10,000+ monthly page views improved

## New Patterns Discovered

### Pattern 2: oldsite_images/ References in Body Content

**Problem**: 63 body content records reference non-existent `oldsite_images/` directory

**Analysis Results**:
- **27 unique files** referenced
- **20 files FOUND** (74%) - exist in other directories with WebP
- **7 files NOT FOUND** (26%) - may have different names or truly missing

**File Locations**:
- Most common: `images_new/` (17 files)
- Also: Root directory, `article_pics/`, `imageimports/`
- Many have WebP versions already generated (16/20 = 80%)

**Example Mappings**:
```
oldsite_images/hertzog.jpg → hertzog.jpg (WebP: YES)
oldsite_images/1977_biko_funeral.jpg → images_new/1977_biko_funeral.jpg (WebP: YES)
oldsite_images/apartheid_pass.jpg → article_pics/apartheid_pass.jpg (WebP: YES)
oldsite_images/1913_passive_resistance.jpg → MISSING (but 1913-passive-resisters.jpg exists)
```

**Historical 404 Impact**:
- `oldsite_images/hertzog.webp` - 1,412 404 errors
- `oldsite_images/ncape.webp` - 1,377 404 errors

## Solution Options

### Option A: Symlinks (QUICK FIX)
**Approach**: Create `oldsite_images/` directory with symlinks to actual files

**Pros**:
- Non-destructive
- Quick to implement
- Easily reversible
- No risk to content

**Cons**:
- Doesn't fix underlying problem
- Need to maintain symlinks
- 7 files truly missing

**Implementation**:
```bash
mkdir -p webroot/sites/default/files/oldsite_images
ln -s ../hertzog.jpg webroot/sites/default/files/oldsite_images/hertzog.jpg
ln -s ../hertzog.webp webroot/sites/default/files/oldsite_images/hertzog.webp
# ... for all 20 found files
```

### Option B: Update Body Content (PERMANENT FIX)
**Approach**: Update hook to replace oldsite_images paths with actual paths

**Pros**:
- Permanent solution
- Aligns content with reality
- Future-proof

**Cons**:
- Medium risk (changing user content)
- Need thorough testing
- 7 files still missing - need handling

**Implementation**: Similar to file_managed fix
```php
function db_fixes_update_11003(&$sandbox) {
  // Batch process node__body
  // For each oldsite_images reference:
  //   1. Extract filename
  //   2. Look up actual location from mapping
  //   3. Replace if file exists
  //   4. Log if file missing
}
```

### Option C: Hybrid Approach (RECOMMENDED)
**Approach**: Symlinks now + body content update later

**Phase 1** (Immediate):
1. Create symlinks for 20 found files
2. Fix 404s immediately
3. Monitor logs

**Phase 2** (Future):
1. Investigate 7 missing files
2. Update body content to point to actual locations
3. Remove symlinks

**Benefits**:
- Quick win (stops 404s today)
- Time to properly research missing files
- Can plan permanent fix carefully

## Other Patterns to Investigate

### Pattern 3: Missing WebP Conversions
From original analysis: 49.6% of images don't have WebP versions

**Example**: `bio_pics/ReggieWilliams_1.jpg` - 4,216 404 errors
- File exists: YES
- WebP exists: NO

**Solution**: Different issue - needs WebP generation, not path fixes

**Command**:
```bash
ddev drush saho:webp-convert
```

**Status**: bio_pics now has 96.6% WebP coverage (1,641/1,699 files)

## Recommended Next Steps

### Immediate (Today)
1. Review this analysis
2. Decide on approach for oldsite_images (A, B, or C)
3. If Option A or C: Create symlink script
4. Test with 2-3 files first
5. Deploy if successful

### Short-term (This Week)
1. Run WebP conversion for remaining files without WebP
2. Investigate 7 missing oldsite_images files
3. Monitor 404 logs for patterns

### Long-term (Next Sprint)
1. Implement permanent body content fix (if using Option C)
2. Search for other hardcoded path issues
3. Create prevention strategy for future migrations

## Files Available

- `/home/mno/Code/sahistory-web/OLDSITE_IMAGES_404_PATTERN.md` - Detailed analysis
- `/tmp/oldsite_images_mapping.txt` - File mapping (20 found, 7 missing)
- `/tmp/map_oldsite_images.sh` - Mapping generation script

## Success Metrics

**If we fix oldsite_images references**:
- 63 body content records corrected
- 20 files now accessible (74% coverage)
- Estimated reduction: 2,789+ monthly 404s (hertzog + ncape alone)
- Better user experience on historical content

## Questions to Resolve

1. **Symlinks vs body update**: Which approach preferred?
2. **Missing files**: Should we investigate the 7 missing files first?
3. **Scope**: Fix just oldsite_images or look for more patterns?
4. **Testing**: How to verify fix on production without breaking content?

## Conclusion

We've successfully demonstrated that the verification-based approach used for `file_managed` can be extended to other 404 patterns:

✓ **Pattern 1**: Database URI mismatch (images/ → images_new/) - **FIXED**
🔍 **Pattern 2**: Body content references (oldsite_images/) - **READY TO FIX**
📋 **Pattern 3**: Missing WebP conversions - **DIFFERENT SOLUTION NEEDED**

The methodology works! We can systematically identify, verify, and fix 404 patterns across the site.
