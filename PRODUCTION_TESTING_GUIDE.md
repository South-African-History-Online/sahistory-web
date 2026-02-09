# Production Schema Testing Guide

## ✅ What We Can Do NOW (Pre-Deployment)

Since the schema.org system is already live, we can test current production to establish a baseline.

### Step 1: Test Current Production Schema

**Event URLs to test:**
```
https://www.sahistory.org.za/dated-event/first-air-flight-london-south-africa
https://www.sahistory.org.za/dated-event/nigerian-women-occupy-chevrontexaco-oil-terminal
https://www.sahistory.org.za/dated-event/songwriter-film-producer-and-actor-joe-mafela-dies
```

**Image URLs to test:**
```
https://www.sahistory.org.za/node/124499
https://www.sahistory.org.za/node/124486
https://www.sahistory.org.za/node/124469
```

### Step 2: Google Rich Results Test

1. Go to: https://search.google.com/test/rich-results
2. Paste each URL above
3. Click "Test URL"
4. **Document current errors:**
   - Missing `endDate`?
   - Missing `location`?
   - Missing `description`?
   - Missing `startDate`?

**Expected current results (BEFORE PR #251):**
- ❌ Some events missing required fields
- ⚠️ Validation warnings

### Step 3: Schema.org Validator

1. Go to: https://validator.schema.org/
2. Select "Fetch URL" tab
3. Paste each URL
4. Click "Run Test"
5. **Document current warnings:**
   - Missing IPTC metadata for images?
   - Incomplete event schemas?

### Step 4: Manual JSON-LD Inspection

1. Open any event URL in browser
2. View Page Source (Ctrl+U)
3. Search for `application/ld+json`
4. Look at Event schema block

**Check current schema (BEFORE PR #251):**
```json
{
  "@context": "https://schema.org",
  "@type": "Event",
  "name": "...",
  "startDate": "...",
  "endDate": "...",  // ❌ Likely MISSING
  "location": "...", // ❌ May be MISSING
  "description": "...", // ❌ May be MISSING
  "organizer": { ... }, // ⚠️ Should be "publisher" for historical events
  "eventStatus": "...", // ⚠️ Not applicable to historical events
}
```

## ✅ What to Test AFTER PR #251 Deploys

### Immediate Testing (Within 1 Hour of Deployment)

1. **Clear production cache:**
   ```bash
   # On production server
   drush cr
   ```

2. **Re-test same URLs** with Rich Results Test:
   - Should now show ✅ Valid
   - All required fields present
   - No missing field errors

3. **Verify new fields in JSON-LD:**

**Events should now have:**
```json
{
  "@context": "https://schema.org",
  "@type": "Event",
  "additionalType": "https://schema.org/HistoricalEvent", // ✅ NEW
  "startDate": "...", // ✅ Always present (with fallback)
  "endDate": "...",   // ✅ NEW (same as startDate)
  "location": { ... }, // ✅ Always present (VirtualLocation fallback)
  "description": "...", // ✅ Always present (with fallback)
  "publisher": { ... }, // ✅ Changed from "organizer"
  // NO eventStatus/eventAttendanceMode ✅ Removed
}
```

**Images should now have:**
```json
{
  "@context": "https://schema.org",
  "@type": "ImageObject",
  "acquireLicensePage": "https://sahistory.org.za/about/copyright-licensing", // ✅ NEW
  "copyrightNotice": "© 2026 South African History Online. Licensed under CC BY-NC-SA 4.0.", // ✅ NEW
  "creditText": "Photo by [Creator] / SAHO", // ✅ NEW
  "license": "...",
  "creator": { ... }
}
```

### Google Search Console Monitoring (2-4 Weeks Post-Deployment)

1. **Track error reduction:**
   - Go to: GSC > Enhancements > Events
   - Monitor error count: Should drop from 2,780 → <100
   - Check warnings for images: Should drop from 269 → 0

2. **Document improvements:**
   - Screenshot GSC before deployment
   - Screenshot GSC 2 weeks after
   - Screenshot GSC 4 weeks after
   - Track impressions/clicks (should not decrease)

## Testing Checklist

### Pre-Deployment Baseline
- [ ] Test 3 event URLs with Google Rich Results Test
- [ ] Document current errors/warnings
- [ ] Test 3 image URLs with Schema.org Validator
- [ ] Screenshot current GSC error counts
- [ ] Save example of current JSON-LD

### Immediately After Deployment
- [ ] Clear production cache
- [ ] Re-test same URLs with Rich Results Test
- [ ] Verify new fields present in JSON-LD
- [ ] Confirm validation errors resolved
- [ ] Test upcoming event URLs (if any published)

### 2-4 Weeks Post-Deployment
- [ ] Check GSC Enhancements > Events
- [ ] Verify error reduction (2,780 → <100)
- [ ] Check GSC Enhancements > Images
- [ ] Verify warnings resolved (269 → 0)
- [ ] Monitor impressions (ensure no drop)
- [ ] Document success metrics

## Quick Test Commands

```bash
# Test a specific node's schema locally
ddev drush ev "
\$node = \Drupal\node\Entity\Node::load(11422);
\$schema = \Drupal::service('saho_tools.schema_org_service')->generateSchemaForNode(\$node);
echo json_encode(\$schema, JSON_PRETTY_PRINT);
"

# Run validation script
./scripts/validate_schemas.sh

# Check for schema generation errors in logs
ddev drush ws --tail --count=50 | grep -i schema
```

## Success Criteria

**After deployment, we should see:**
- ✅ All event URLs pass Google Rich Results Test
- ✅ All image URLs validate with no IPTC warnings
- ✅ JSON-LD contains all new required fields
- ✅ GSC errors reduce by 96% within 4 weeks
- ✅ No negative impact on search visibility

## Resources

- Google Rich Results Test: https://search.google.com/test/rich-results
- Schema.org Validator: https://validator.schema.org/
- Google Search Console: https://search.google.com/search-console
- Schema.org Event: https://schema.org/Event
- Schema.org HistoricalEvent: https://schema.org/HistoricalEvent
- Schema.org ImageObject: https://schema.org/ImageObject
