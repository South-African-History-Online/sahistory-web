# Production Schema.org Testing Results

**Date**: 2026-02-09
**Environment**: Production (sahistory.org.za)

## Test URLs

### Historical Events (TDIH)
1. https://www.sahistory.org.za/dated-event/first-air-flight-london-south-africa (Node 11422)
2. https://www.sahistory.org.za/dated-event/nigerian-women-occupy-chevrontexaco-oil-terminal (Node 149153)
3. https://www.sahistory.org.za/dated-event/songwriter-film-producer-and-actor-joe-mafela-dies (Node 149152)

### Images
1. https://www.sahistory.org.za/node/124499 (Robert Sobukwe with Potlako Leballo)
2. https://www.sahistory.org.za/node/124486 (South Africans queue at government office)
3. https://www.sahistory.org.za/node/124469 (Lilian Ngoyi)

### Upcoming Events
1. Check for any published upcoming events at: https://www.sahistory.org.za/events

## Validation Tools

### 1. Google Rich Results Test
Test each URL at: https://search.google.com/test/rich-results

**What to check:**
- Events show "Valid" status (no errors)
- Required fields present: startDate, endDate, location, description
- Images show valid ImageObject schema
- No warnings about missing IPTC metadata

### 2. Schema.org Validator
Test each URL at: https://validator.schema.org/

**What to check:**
- All schema types parse correctly
- Event has additionalType: HistoricalEvent (after deployment)
- Image has acquireLicensePage, copyrightNotice, creditText (after deployment)
- No schema.org validation errors

### 3. Manual Inspection
View page source and check JSON-LD blocks for:
- Three JSON-LD blocks present: Organization, Content (Event/Image), Breadcrumb
- All required fields populated
- Fallback values applied where source data missing

## Current Production Status

**Note**: These tests show the CURRENT production schema (before our PR #251 is deployed).

Our changes in PR #251 will:
- Add HistoricalEvent additionalType to events
- Add endDate to events
- Add fallbacks for missing startDate/location/description
- Add IPTC metadata to images
- Add schema support for upcoming events (upcomingevent content type)

## Testing Commands

```bash
# Test Google Rich Results (Events)
https://search.google.com/test/rich-results?url=https://www.sahistory.org.za/dated-event/first-air-flight-london-south-africa

# Test Schema.org Validator (Events)
https://validator.schema.org/#url=https%3A%2F%2Fwww.sahistory.org.za%2Fdated-event%2Ffirst-air-flight-london-south-africa

# Test Google Rich Results (Images)
https://search.google.com/test/rich-results?url=https://www.sahistory.org.za/node/124499

# Test Schema.org Validator (Images)
https://validator.schema.org/#url=https%3A%2F%2Fwww.sahistory.org.za%2Fnode%2F124499
```

## Post-Deployment Testing Checklist

After PR #251 is deployed to production:

- [ ] Clear production cache: `ddev drush cr` (on production)
- [ ] Wait 10 minutes for cache warmup
- [ ] Test 5 sample event URLs with Google Rich Results Test
- [ ] Test 5 sample image URLs with Schema.org Validator
- [ ] Verify new fields present in JSON-LD:
  - Events: additionalType (HistoricalEvent), endDate, fallback values
  - Images: acquireLicensePage, copyrightNotice, creditText
- [ ] Monitor Google Search Console over 2-4 weeks
- [ ] Track error reduction in GSC "Enhancements > Events" section

## Expected Results Post-Deployment

### Events
- **Before**: Missing startDate (595), endDate (2,185), location (2,185)
- **After**: All required fields present with intelligent fallbacks
- **GSC Impact**: 2,780 errors → <100 (96% reduction)

### Images
- **Before**: Missing IPTC metadata (269 warnings)
- **After**: All IPTC fields present (acquireLicensePage, copyrightNotice, creditText)
- **GSC Impact**: 269 warnings → 0 (100% resolution)
