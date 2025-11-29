# Event Date Field Analysis Report

**Generated:** 2025-11-27
**Database:** sahistrg878_production.sql
**Purpose:** Consolidate `field_this_day_in_history_3` (date) and `field_this_day_in_history_date_2` (datetime) into single `field_event_date`

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| Total records in field_this_day_in_history_3 | 3,507 |
| Total records in field_this_day_in_history_date_2 | 3,449 |
| **Matching records** (dates are identical) | 3,443 |
| **Date mismatches** (dates differ) | 5 |
| **Only in field_3** (no corresponding field_2) | 59 |
| **Only in field_2** (no corresponding field_3) | 1 |

**Data Quality:** 98.2% of records are matching and require no manual intervention.

---

## CRITICAL: Date Mismatches (5 records)

These nodes have DIFFERENT dates in the two fields and need manual review to determine the correct date:

| NID | Title | field_3 (date) | field_2 (datetime) | Analysis |
|-----|-------|----------------|-------------------|----------|
| 11418 | Benjamin Magson Kies, political activist & lawyer, is born | **1917-12-12** | 1919-12-12 | 2-year discrepancy - verify birth year |
| 11888 | 13 year-old South African Native mineworker is published in a newspaper | **1960-03-07** | 1970-01-01 | field_2 looks like default/error (1970-01-01), use field_3 |
| 12176 | The first hijacking of a South African Airways plane takes place | **1972-05-24** | 1940-05-24 | 32-year discrepancy - verify actual date |
| 93949 | Rhodesian Prime Minister, Ian Douglas Smith, declares a state of emergency | **1965-11-05** | 1955-11-05 | 10-year discrepancy - verify actual date |
| 124768 | Jack Parow, the Afrikaans rap artist is born | 1982-02-22 | 1982-02-21T23:00:00 | **Timezone issue** - field_2 is UTC, use field_3 |

### Recommended Actions for Mismatches:

```sql
-- NID 11418: Verify correct birth year (1917 vs 1919)
-- Research needed - check historical sources

-- NID 11888: Use field_3 (1970-01-01 is clearly a default/error value)
-- Action: Use 1960-03-07

-- NID 12176: Verify hijacking date (1972 vs 1940)
-- Research needed - SAA hijacking history

-- NID 93949: Verify Smith emergency date (1965 vs 1955)
-- Research needed - Rhodesian history

-- NID 124768: Timezone issue - use field_3 value (1982-02-22)
-- This is a UTC conversion issue, field_3 is correct
```

---

## Records Only in field_3 (59 records)

These nodes have a date in `field_this_day_in_history_3` but NO corresponding value in `field_this_day_in_history_date_2`. Use field_3 value for migration.

| NID | Title | field_3 Date |
|-----|-------|--------------|
| 11186 | The second Anglo-Boer war, also known as the South African war, breaks out | 1899-10-11 |
| 11767 | Libya becomes independent | 1951-12-24 |
| 11809 | ANC President Albert Luthuli banned | 1954-07-12 |
| 11918 | Patrice Lumumba, Prime Minister of the Congo Republic, is assassinated | 1961-01-17 |
| 69380 | Thousands form a human chain in a symbolic protest against the Group Areas Act | 1989-11-11 |
| 76831 | The astronomical observatory at Sutherland in the Karoo is opened. | 1973-03-15 |
| 82577 | Worsie Visser (35), popular SA singer, is killed instantly when his plane crashes at Saldanha, West Coast. | 1998-05-04 |
| 83632 | National Education Minister Naledi Pandor launches the redesigned and updated South African History Online website. | 2007-05-21 |
| 84880 | Maria Visser (68), mother of SA singer Worsie Visser, dies in Barkley West, a month after her son was killed in an accident. | 1998-06-06 |
| 85906 | President P.W. Botha suspends Amichand Rajbansi, leader of the House of Delegates, from the Cabinet... | 1988-06-15 |
| 92040 | A State Presidential Proclamation widening the Group Areas Act preventing multi-racial sport | 1973-10-05 |
| 95044 | Grammy Award winner Miriam Makeba is born in Johannesburg | 1932-03-04 |
| 149576 | Jazz veteran Dorothy Masuka dies | 2019-09-23 |
| 149577 | Queen of Gospel Rebecca Malope is born | 1968-06-30 |
| 149579 | Hip-hop star Jabulani Tsambo known as HHP dies | 2018-10-24 |
| 149580 | Legendary photographer Sam Nzima dies | 2018-05-12 |
| 149583 | The father of South African Jazz Hugh Masekela dies | 2018-01-23 |
| 149585 | Legendary artist Johnny Clegg dies | 2019-07-16 |
| 149621 | President Cyril Ramaphosa appears before protesters against gender-based violence in South Africa | 2019-09-05 |
| 149631 | President Jacob Zuma steps down as President of South Africa | 2018-02-14 |
| 149633 | President Cyril Ramaphosa delivers the State of the Nation address | 2018-02-16 |
| 149639 | Death in detention of Imam Haron | 1969-09-27 |
| 149665 | Fake tickets lead to stampede in Johannesburg | 2017-07-29 |
| 149675 | Black Wednesday, the banning of 19 Black Consciousness Movement Organisations | 1977-10-19 |
| 149699 | Death in detention of Ahmed Timol | 1971-10-27 |
| 149700 | Ahmed Timol born on the 3rd Nov 1941 | 1941-11-03 |
| 149703 | AIDS activist stoned and stabbed to death by her neighbours | 1998-12-16 |
| 149717 | Dale Steyn retires from Test cricket | 2019-08-05 |
| 149718 | Hashim Amla retires from international cricket | 2019-08-08 |
| 149721 | The fall of the Berlin wall | 1989-11-09 |
| 149727 | Armistice day 11th Nov 1918 | 1918-11-11 |
| 149748 | Rocklands Community Hall declared a heritage site | 2019-09-20 |
| 149754 | Anton Fransch, ANC guerrilla, killed by the special forces of the apartheid regime | 1989-11-17 |
| 149779 | Slavery abolished in the Cape | 1834-12-01 |
| 149831 | Prime Circle released their album 'Hello Crazy World- 10th Anniversary Special' | 2013-09-01 |
| 149850 | The reburial of Flora | 1991-04-06 |
| 149880 | The founding member of United Democratic Front and struggle activist Johnny Issel dies | 2011-01-23 |
| 149902 | Vergelegen Wine Estate Owner Samuel Kerr Dies | 1905-04-25 |
| 149910 | Peter "Terror" Mathebula dies | 2020-01-18 |
| 150097 | Isidingo will air it's last episode | 2020-03-12 |
| 150157 | Thembisile Chris Hani is killed | 1993-04-10 |
| 150187 | President Cyril Ramaphosa calls for national lockdown for 21 days in South Africa | 2020-03-23 |
| 150202 | Khayelitsha becomes the first township in the Western Cape to confirm a COVID-19 case | 2020-03-29 |
| 150236 | Ladybrand Four story told at the TRC | 2000-10-12 |
| 150237 | Tryphina Mboxela Jokweni is honoured by the ANC | 2012-08-29 |
| 150240 | Ignatius Iggy Mthebule remembered | 2003-01-15 |
| 150266 | The Missing Persons Task Team (MPTT) to Investigate Apartheid Missing Persons Cases | 2018-04-20 |
| 150267 | The Reburial of Mapungubwe Human Remains | 2007-11-18 |
| 150491 | The South African Astronomical Observatory celebrates 200 years of its existence | 2020-10-20 |
| 150705 | The Afrikaner Weerstandsbeweging (AWB) invade the World Trade Centre... | 1993-06-25 |
| 151554 | President Cyril Ramaphosa appoints Justice Raymond Zondo as Chief Justice | 2022-03-11 |
| 151919 | Legendary South African Football Administrator Abdul Bhamjee dies | 2021-01-19 |
| 151931 | Former National Soccer League CEO Cyril Kobus dies | 2021-06-21 |
| 151932 | Former South African Football Association president Solomon 'Sticks' Morewa died | 2005-09-24 |
| 151948 | Former Bafana Bafana Legend John Moeti dies | 2023-02-06 |
| 151959 | Former South African football coach Clive Barker is born | 1944-06-23 |
| 151960 | South African Soccer legend Lucas Radebe was born | 1969-04-12 |
| 151997 | South African football legend Steve Mokone died | 2015-03-20 |
| 151998 | South African football legend Steve Mokone is born 23 March 1923 | 1923-03-23 |

---

## Records Only in field_2 (1 record)

This node has a datetime in `field_this_day_in_history_date_2` but NO corresponding value in `field_this_day_in_history_3`. Use field_2 value for migration.

| NID | Title | field_2 Datetime |
|-----|-------|------------------|
| 13474 | Egyptian women files divorce under new law | 2000-01-29T00:00:00 |

---

## Migration Strategy

### Phase 1: Automatic Migration (3,502 records - 99.8%)

Records that can be migrated automatically without manual review:

1. **Matching records (3,443):** Use `field_this_day_in_history_3` value
2. **Only in field_3 (59):** Use `field_this_day_in_history_3` value
3. **Only in field_2 (1):** Use date portion of `field_this_day_in_history_date_2`

```sql
-- Migration query for automatic records
INSERT INTO node__field_event_date (bundle, deleted, entity_id, revision_id, langcode, delta, field_event_date_value)
SELECT
    'event',
    0,
    COALESCE(t3.entity_id, t2.entity_id),
    COALESCE(t3.revision_id, t2.revision_id),
    COALESCE(t3.langcode, t2.langcode),
    COALESCE(t3.delta, t2.delta),
    COALESCE(t3.field_this_day_in_history_3_value, DATE(t2.field_this_day_in_history_date_2_value))
FROM node__field_this_day_in_history_3 t3
FULL OUTER JOIN node__field_this_day_in_history_date_2 t2
    ON t3.entity_id = t2.entity_id AND t3.delta = t2.delta
WHERE (t3.deleted = 0 OR t3.deleted IS NULL)
    AND (t2.deleted = 0 OR t2.deleted IS NULL)
    AND COALESCE(t3.entity_id, t2.entity_id) NOT IN (11418, 11888, 12176, 93949, 124768);
```

### Phase 2: Manual Review (5 records - 0.2%)

The 5 date mismatches require manual verification before migration:

| NID | Recommended Action | Confidence |
|-----|-------------------|------------|
| 11418 | Research required | Low |
| 11888 | Use 1960-03-07 from field_3 | High (field_2 has default value) |
| 12176 | Research required | Low |
| 93949 | Research required | Low |
| 124768 | Use 1982-02-22 from field_3 | High (timezone issue) |

---

## Data Quality Notes

1. **No malformed dates detected** - All dates follow proper YYYY-MM-DD format
2. **No impossible dates detected** - No Feb 30, etc.
3. **No multi-value records** - Each node has only one date value (delta=0)
4. **Timezone consideration:** field_2 (datetime) stores times as T00:00:00, suggesting date-only intent

---

## SQL Scripts for Fixes

### Fix NID 11888 (clear default date issue):
```sql
-- Use field_3 value (1960-03-07) - field_2 has 1970-01-01 default
UPDATE node__field_event_date
SET field_event_date_value = '1960-03-07'
WHERE entity_id = 11888;
```

### Fix NID 124768 (timezone issue):
```sql
-- Use field_3 value (1982-02-22) - field_2 shows UTC offset
UPDATE node__field_event_date
SET field_event_date_value = '1982-02-22'
WHERE entity_id = 124768;
```

### Records requiring historical research:
```sql
-- After verifying correct dates, update:
-- NID 11418: Benjamin Magson Kies birth year (1917 or 1919?)
-- NID 12176: SAA hijacking date (1972 or 1940?)
-- NID 93949: Ian Smith emergency declaration (1965 or 1955?)
```

---

## Verification Queries

After migration, run these to verify data integrity:

```sql
-- Count migrated records
SELECT COUNT(*) FROM node__field_event_date WHERE bundle = 'event';

-- Verify all event nodes have a date
SELECT nfd.nid, nfd.title
FROM node_field_data nfd
LEFT JOIN node__field_event_date fed ON nfd.nid = fed.entity_id
WHERE nfd.type = 'event' AND fed.entity_id IS NULL;

-- Compare with original field counts
SELECT
    (SELECT COUNT(DISTINCT entity_id) FROM node__field_this_day_in_history_3 WHERE deleted = 0) as original_count,
    (SELECT COUNT(DISTINCT entity_id) FROM node__field_event_date WHERE bundle = 'event') as migrated_count;
```
