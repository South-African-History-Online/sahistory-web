**MetadataBlock** — consistent tabular key/value block for people, events, places, objects. Mono labels, finding-aid styling, optional content-type accent rule.

```jsx
<MetadataBlock
  accent="biography"
  title="Vital record"
  items={[
    { label: 'Born', value: '18 July 1918 · Mvezo' },
    { label: 'Died', value: '5 December 2013 · Johannesburg' },
    { label: 'Roles', value: 'Activist · Statesman · President' },
    { label: 'Place', value: 'Eastern Cape', href: '#' },
  ]}
/>
```

**ArchiveCard** — the workhorse index card. Content-type accent edge, optional duotone thumbnail, mono meta, serif excerpt, calm hover. Use across grids, search results and "related" rails.

```jsx
<ArchiveCard type="biography" title="Nelson Mandela" dates="1918–2013"
  excerpt="Activist, statesman and first democratically elected president." meta="Biography · 12 min read" />
```
