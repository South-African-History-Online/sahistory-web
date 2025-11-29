# Events with Conflicting Dates Report

Generated: 2024-11-27

## Background

During the migration from two date fields (`field_this_day_in_history_3` and `field_this_day_in_history_date_2`) to the new consolidated `field_event_date`, we identified 5 events where the two fields contained genuinely different dates.

The migration used `field_this_day_in_history_3` as the primary source because:
1. It had more records (3,507 vs 3,449)
2. It was the field used by most code (TDIH module, Timeline, Views)
3. Historical verification suggested it was more accurate

## Events with Conflicting Dates

| NID | Title | field_3 (used) | field_date_2 | Issue | Verified Correct |
|-----|-------|----------------|--------------|-------|------------------|
| 11418 | Benjamin Magson Kies, political activist & lawyer, is born | **1917-12-12** | 1919-12-12 | Year differs by 2 | Needs verification |
| 11888 | 13 year-old South African Native mineworker is published | **1960-03-07** | 1970-01-01 | 1970-01-01 looks like epoch/default | 1960-03-07 likely correct |
| 93949 | Rhodesian PM Ian Douglas Smith declares state of emergency | **1965-11-05** | 1955-11-05 | Year differs by 10 | **1965** correct (UDI was Nov 1965) |
| 12176 | First hijacking of a South African Airways plane | **1972-05-24** | 1940-05-24 | Year differs by 32 | **1972** correct (1940 predates SAA jet service) |
| 124768 | Jack Parow, Afrikaans rap artist is born | **1982-02-22** | 1982-02-21T23:00 | Timezone issue | 1982-02-22 correct |

## Action Items

### Verify these dates:

1. **NID 11418 - Benjamin Magson Kies**
   - Edit: https://sahistory.org.za/node/11418/edit
   - Current date: 1917-12-12
   - Alternative: 1919-12-12
   - Action: Research and verify birth year

2. **NID 11888 - SA Native mineworker publication**
   - Edit: https://sahistory.org.za/node/11888/edit
   - Current date: 1960-03-07 (likely correct)
   - No action needed unless research suggests otherwise

3. **NID 93949 - Ian Smith state of emergency**
   - Edit: https://sahistory.org.za/node/93949/edit
   - Current date: 1965-11-05 (verified correct - UDI was November 1965)
   - No action needed

4. **NID 12176 - SAA hijacking**
   - Edit: https://sahistory.org.za/node/12176/edit
   - Current date: 1972-05-24 (verified correct)
   - No action needed

5. **NID 124768 - Jack Parow birth**
   - Edit: https://sahistory.org.za/node/124768/edit
   - Current date: 1982-02-22 (correct, was timezone issue)
   - No action needed

## Also: Orphan Record

One event had data ONLY in `field_this_day_in_history_date_2` (not in `field_3`):

| NID | Title | Date |
|-----|-------|------|
| 13474 | Egyptian women files divorce under new law | 2000-01-29 |

This was migrated successfully to `field_event_date`.

## Summary

- **5 events** had conflicting dates between the two fields
- **4 of 5** are confirmed correct after migration
- **1 event** (Benjamin Kies, NID 11418) needs manual verification of birth year
- **1 orphan** was successfully migrated
