**Navigation components** — chronology and cross-reference are how this material wants to be navigated.

**Timeline** — first-class filterable chronology spine with dot markers and era/theme toggles.

```jsx
<Timeline title="Chronology of resistance" themes={['Legislation','Protest','Exile']}
  events={[
    { year: '1948', title: 'Apartheid legislated', theme: 'Legislation', href: '#' },
    { year: '1960', title: 'Sharpeville massacre', detail: '69 killed during a pass-law protest.', theme: 'Protest' },
  ]} />
```

**RelatedList** — the connective tissue; cross-references people/events/places/dates with a type dot + label.

```jsx
<RelatedList items={[
  { label: 'Walter Sisulu', type: 'biography', note: '1912–2003' },
  { label: 'Robben Island', type: 'place' },
]} />
```

**SearchField** — the prominent front-door search; pass `scopes` for content-type constraints.

```jsx
<SearchField scopes={['All','People','Events','Places','Archive']} />
```
