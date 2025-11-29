# Event Date Field Audit Report

Generated: 2024-11-27

## Summary

| Category | Count |
|----------|-------|
| Total Event nodes | 17,656 |
| Events WITH dates | 3,508 |
| Events WITHOUT dates | 14,148 |

## Date Range of Events with Dates

- Earliest: 1304-02-24 (Ibn Battuta's birth)
- Latest: 2023-02-06

## Files Generated

1. **dateless_events.tsv** - All 14,148 events without dates
   - Columns: nid, title, created_date
   - To edit: https://sahistory.org.za/node/{nid}/edit

## Action Required

The 14,148 dateless events need manual review to:
1. Add the historical date if known
2. Or mark as "date unknown" if the event date cannot be determined

### Quick Edit URLs

To edit an event, use: `https://sahistory.org.za/node/{NID}/edit`

Or in DDEV local: `https://sahistory-web.ddev.site/node/{NID}/edit`

### Bulk Operations

You can use Views Bulk Operations (VBO) to:
1. Create a view of dateless events
2. Bulk edit to add dates

### Sample of Recent Dateless Events (need dates added)

These events have titles that suggest specific dates but are missing the Event Date field:

- "Nelson Mandela is inaugurated as South Africa's first democratic President" (should be 1994-05-10)
- "South Africa's first democratic elections" (should be 1994-04-27)
- "Germany signs an unconditional surrender which ends World War II" (should be 1945-05-08)
- "Jan van Riebeeck is born" (should be 1619-04-21)

## Technical Notes

- The Event Date field (`field_event_date`) uses Drupal's datetime field type
- Dates are stored as YYYY-MM-DD format
- Year range for form input: 1000-2100 (configurable in saho_utils.module)
- For prehistoric dates, a separate text field would be needed
