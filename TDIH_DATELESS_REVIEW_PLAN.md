# TDIH Dateless Content Review Plan

## Overview

The SAHO Timeline has **14,141 events** without proper historical dates (`field_event_date`). These events cannot appear in the "This Day in History" feature or be properly placed on the timeline until dates are added.

## Event Categories

| Category | Count | Notes |
|----------|-------|-------|
| **Births** ("is born") | 1,479 | Need birth dates researched |
| **Deaths** ("dies/died/passes away") | 1,131 | Need death dates researched |
| **Events with year in title** | 837 | May have partial date info extractable |
| **Other historical events** | ~10,694 | Need full date research |
| **TOTAL** | 14,141 | |

## Approach

### Phase 1: AI-Assisted Research (Claude)
For each dateless event, Claude can:
1. Research the specific date (day, month, year)
2. Verify the information from reliable sources
3. Generate/improve body text with proper context
4. Provide references (Wikipedia, Britannica, academic sources)
5. Flag entries that need manual intervention

### Phase 2: Batch Processing
Create a workflow to:
1. Export dateless events in batches (50-100 at a time)
2. Process through Claude for date research
3. Generate update scripts or CSV imports
4. Review and apply updates via Drupal

### Phase 3: Manual Intervention
Some entries will require manual research:
- Events with conflicting date information
- Obscure historical events with limited sources
- Events where dates are genuinely unknown
- Duplicate entries that need merging

## Data Format for Claude Research

For each event, provide to Claude:
```
Node ID: [NID]
Title: [Event title]
Current Body: [If any]
```

Expected output from Claude:
```
Node ID: [NID]
Date: [YYYY-MM-DD] or "UNKNOWN - [reason]"
Verified: [Yes/No/Partial]
Body Text: [Improved description]
References:
- [Source 1]
- [Source 2]
Manual Review Required: [Yes/No]
Reason for Manual Review: [If applicable]
```

## Events Requiring Manual Intervention

The following types of events are flagged for manual review:

### 1. Conflicting Dates in Title
Events where the title mentions different dates:
- Example: "Adam Kok III (64), Griqua chief, dies near Kokstad. Date is given as 30 December 1875 in another source."

### 2. Approximate/Uncertain Dates
Events with "about", "circa", "around":
- Need to decide on best date representation

### 3. Duplicates
Multiple entries for the same event:
- "Aboubakar Jakoet... is born" appears twice (NIDs: 82083, 82029)
- "Abraham Jacobus Kotzé de Klerk... is born" appears twice (NIDs: 82017, 82071)

### 4. Events Without Specific Dates
Some events may genuinely lack specific dates:
- "International Workers' Day" (annual, not a single historical event)
- Ongoing processes without specific start dates

### 5. Events Requiring Subject Matter Expertise
- Complex political events
- Events with disputed historiography

## Implementation Steps

### Step 1: Create Export Script
Create a Drush command to export dateless events in batches:
```bash
ddev drush tdih:export-dateless --batch=1 --size=100 --format=json
```

### Step 2: Create Import Script
Create a Drush command to import researched dates:
```bash
ddev drush tdih:import-dates --file=batch_1_researched.json
```

### Step 3: Create Review Interface
Add a review interface to `/saho-timeline/` for:
- Viewing researched results before import
- Flagging entries for manual review
- Tracking progress

## Progress Tracking

| Batch | Events | Status | Researched | Applied | Manual Review |
|-------|--------|--------|------------|---------|---------------|
| 1 | 100 | Pending | 0 | 0 | 0 |
| 2 | 100 | Pending | 0 | 0 | 0 |
| ... | ... | ... | ... | ... | ... |

## Sample Entries Already Researched (from Claude chat)

### Confirmed Dates:
1. **Winnie Madikizela-Mandela dies** - Date: 2 April 2018
2. **Sharlto Copley is born** - Date: 27 November 1973

### Needs Further Research:
- Mahlaste Chiliboy Ralepele becomes the first Black Springboks captain

## Priority Order

1. **High-profile births/deaths** - Famous South Africans (Mandela family, politicians, cultural figures)
2. **Recent events** (2000-present) - Easier to verify
3. **Major historical events** - Apartheid era, colonial period
4. **Births** - Usually well-documented
5. **Deaths** - Usually well-documented
6. **Other events** - May require more research

## Notes

- The `field_event_date` format is `YYYY-MM-DD` (date only, no time)
- Events appear on timeline based on this field
- TDIH feature shows events matching current day/month
- Some events may need to remain "dateless" if date is genuinely unknown (consider archiving or flagging)
