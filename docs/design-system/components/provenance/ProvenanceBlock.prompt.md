**Provenance components** — SAHO's differentiators. The scholarly apparatus is the brand: make it visible and beautiful.

**ProvenanceBlock** — "How we know this." A note plus a numbered, linked source list. Put it inline in biographies, topics and events — never in a footer.

```jsx
<ProvenanceBlock
  note="Compiled from the Nelson Mandela Foundation archive and contemporary press records."
  sources={[
    { author: 'Truth & Reconciliation Commission', title: 'Final Report, vol. 2', detail: '1998', href: '#' },
    { author: 'Mandela, N.', title: 'Long Walk to Freedom', detail: 'Little, Brown, 1994' },
  ]}
  lastUpdated="2026-02-11"
/>
```

**Citation** — "Cite this entry" with selectable Chicago/APA/MLA, copyable, mono-set. Pass `formats` to override the worked default.

**ImageCredit** — archival `<figure>` with always-visible `credit` + `source`; set `duotone` to unify disparate images.

**ContentWarning** — sensitivity gate; wraps difficult `children` behind a calm opt-in notice.
