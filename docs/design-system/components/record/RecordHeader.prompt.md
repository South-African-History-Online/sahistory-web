**Record components** — the data structure made visible. SAHO is a record store; these express it directly.

**RecordHeader** — frames any entity (biography, event, place, article, archive document) as a catalogue record: a typed folder tab, an accession reference, the title, and a ruled key-field strip.

```jsx
<RecordHeader type="biography" reference="B-0427" kicker="Biography record"
  title="Nelson Rolihlahla Mandela" status="Verified"
  facts={[
    { label: 'Born', value: '18 Jul 1918' },
    { label: 'Died', value: '5 Dec 2013' },
    { label: 'Sources', value: '14' },
  ]} />
```

**IndexTable** — the archive pulled in as a ruled catalogue: rows are records, columns are fields. The primary browse/query surface. A `type` column renders a content-type swatch + label; mark columns `sortable`, `mono`, `muted`, `align`, or give a custom `render`.

```jsx
<IndexTable
  columns={[
    { key: 'ref', label: 'Ref', mono: true, width: '90px' },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'title', label: 'Record', sortable: true },
    { key: 'dates', label: 'Dates', mono: true, muted: true },
    { key: 'sources', label: 'Src', mono: true, align: 'right' },
  ]}
  rows={[{ ref: 'B-0427', type: 'biography', title: 'Nelson Mandela', dates: '1918–2013', sources: 14 }]} />
```
