# TDIH Manual Intervention List

This document lists dateless events that require manual intervention before automated processing.

## 1. Duplicate Entries (Require Merging)

These entries appear multiple times with identical titles. They should be merged into a single entry before adding dates.

| Title | Count | Node IDs | Action Required |
|-------|-------|----------|-----------------|
| South Africans commemorated Nelson Mandela with a day of prayer | 6 | 122431,122432,122433,122434,122435,122445 | Merge to single entry |
| Alfred Beit (53), friend of Cecil John Rhodes... dies | 4 | 88308,89806,91180,91181 | Merge to single entry |
| Anglo-Boer War 2 - In the Free State, Lieutenant-General E.L. Elliot's drive ends... | 4 | 88306,89804,91176,91177 | Merge to single entry |
| Anglo-Boer War 2 Start of the 'first De Wet hunt'... | 4 | 88303,89801,91170,91171 | Merge to single entry |
| Anglo-Boer War 2: Lord Methuen reoccupies Rustenburg... | 4 | 88305,89803,91174,91175 | Merge to single entry |
| Europeans and Americans are reportedly fleeing Biafra... | 4 | 89817,88319,91198,91199 | Merge to single entry |
| Horatio Isaiah Budlwana (Bud) Mbelle... dies in Pretoria | 4 | 91190,91191,89812,88314 | Merge to single entry |
| Ivory Coast closes sea and airports to South Africa and Portugal | 4 | 91195,91196,88317,89815 | Merge to single entry |
| Judge Ivan Curlewis (42) dies... | 4 | 88313,89811,91188,91189 | Merge to single entry |
| President Jacob Zuma arrives in Moscow... 70th Anniversary | 4 | 123297,123298,123299,123300 | Merge to single entry |
| Roelf Meyer, former politician and NP negotiator, is born | 4 | 89813,88315,91192,91193 | Merge to single entry |
| Ronald Mylchreest, S.A sculptor, is born in Johannesburg | 4 | 91183,91184,89808,88310 | Merge to single entry |
| Soviet advisers are expelled from the Republic of Somali... | 4 | 89820,88322,91203,91204 | Merge to single entry |
| The Council of the New Zealand Softball Association... | 4 | 91200,91201,88320,89818 | Merge to single entry |
| The 'Committee of 81'... decides to end class boycotts | 4 | 89822,88324,91206,91207 | Merge to single entry |
| Three commandos under the newly appointed Combat General Viljoen... | 4 | 91172,91173,88304,89802 | Merge to single entry |
| A diesel train... derails and explodes in Swartruggens | 3 | 89834,88336,91220 | Merge to single entry |
| Allister Mackintosh, pilot and 'father' of SA aviation is born | 3 | 90823,90824,90863 | Merge to single entry |
| Aboubakar Jakoet... is born | 2 | 82083,82029 | Merge to single entry |
| Abraham Jacobus Kotzé de Klerk... is born in Garies | 2 | 82017,82071 | Merge to single entry |

**Total duplicates found: ~50+ entries with 2-6 copies each**

## 2. Conflicting Date Information

These entries mention different dates in their titles or descriptions:

| Node ID | Title | Issue |
|---------|-------|-------|
| 71424 | Adam Kok III (64), Griqua chief, dies in Griqualand East. Date is given as 31 December 1875 in another source. | Conflicting dates mentioned |
| 71456 | Adam Kok III, Griqua chief... dies near Kokstad. Date is given as 30 December 1875 in another source. | Same person, different dates |

**Action Required:** Research authoritative sources to determine correct date, then update entry.

## 3. Annual/Recurring Events (Not Single Historical Events)

These entries describe recurring events, not single historical occurrences:

| Node ID | Title | Issue |
|---------|-------|-------|
| 59829 | 1 May - International Workers' Day | Annual event, not a single date |

**Action Required:** Consider creating a different content type for recurring events, or assign the date of first observance in South Africa.

## 4. Approximate Dates

Entries that may need "circa" dates or date ranges:

| Pattern | Example | Action |
|---------|---------|--------|
| "born about [year]" | "Abraham Moletsane... born about 1788" | Use January 1 of year as approximation, note in body |
| "in the [decade]s" | Various | Use middle of decade |

## 5. Events Without Verifiable Dates

Some historical events may genuinely lack specific dates:

- Very early colonial period events
- Events where only year is known
- Oral history events

**Action Required:** For these, consider:
1. Using January 1 of the year as a placeholder
2. Adding a "date_approximate" field in future
3. Keeping them dateless but marking as "date unknown"

## 6. Non-South African Events

Some entries appear to be world events without direct SA connection:

| Example | Issue |
|---------|-------|
| Abbas Hilmi II, viceroy of Egypt (1892-1914), dies at the age of 70 | Egyptian history |
| Nigerian Federal troops begin a major offensive in Biafra | Nigerian history |

**Action Required:** Review whether these should remain in SAHO timeline or be archived.

## Processing Priority

1. **First:** Remove duplicates (reduces workload significantly)
2. **Second:** Resolve conflicting dates
3. **Third:** Process remaining entries through Claude research
4. **Fourth:** Manual review of flagged entries

## SQL Queries for Reference

### Find all duplicates:
```sql
SELECT n.title, COUNT(*) as cnt, GROUP_CONCAT(n.nid) as nids
FROM node_field_data n
LEFT JOIN node__field_event_date fed ON n.nid = fed.entity_id
WHERE n.type = 'event' AND n.status = 1 AND fed.field_event_date_value IS NULL
GROUP BY n.title
HAVING cnt > 1
ORDER BY cnt DESC;
```

### Find entries with "another source" mentioned:
```sql
SELECT nid, title FROM node_field_data
WHERE type = 'event' AND status = 1
AND title LIKE '%another source%';
```

### Find entries with "about" or "circa":
```sql
SELECT nid, title FROM node_field_data
WHERE type = 'event' AND status = 1
AND (title LIKE '%born about%' OR title LIKE '%circa%');
```
