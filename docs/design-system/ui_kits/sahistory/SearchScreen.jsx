/* SAHO UI kit · Search. The front door = a query over the archive. Results pull
   in as a ruled catalogue index (IndexTable): rows are records, columns fields. */

function SearchScreen() {
  const rows = [
    { ref: 'B-0427', type: 'biography', title: 'Nelson Rolihlahla Mandela', dates: '1918–2013', sources: 14 },
    { ref: 'E-1190', type: 'event', title: 'The Sharpeville massacre', dates: '21 Mar 1960', sources: 11 },
    { ref: 'P-0042', type: 'place', title: 'Robben Island', dates: 'Western Cape', sources: 7 },
    { ref: 'A-2255', type: 'archive', title: 'The Freedom Charter', dates: '26 Jun 1955', sources: 9 },
    { ref: 'T-0318', type: 'topic', title: 'The Defiance Campaign', dates: '1952', sources: 9 },
    { ref: 'B-0512', type: 'biography', title: 'Walter Sisulu', dates: '1912–2003', sources: 6 },
    { ref: 'E-0904', type: 'event', title: 'Soweto Uprising', dates: '16 Jun 1976', sources: 13 },
  ];
  const columns = [
    { key: 'ref', label: 'Ref', mono: true, width: '92px' },
    { key: 'type', label: 'Type', sortable: true, width: '150px' },
    { key: 'title', label: 'Record', sortable: true,
      render: (r) => <a href="#" style={{ color: 'var(--text-primary)', textDecoration: 'none', fontWeight: 600 }}>{r.title}</a> },
    { key: 'dates', label: 'Dates', mono: true, muted: true, sortable: true, width: '140px' },
    { key: 'sources', label: 'Src', mono: true, align: 'right', sortable: true, width: '64px' },
  ];
  return (
    <main style={{ maxWidth: 'var(--container-standard)', margin: '0 auto', padding: 'var(--space-7) var(--gutter-page) 0' }}>
      <div style={{ marginBottom: 'var(--space-6)' }}>
        <span className="saho-eyebrow">Query the archive</span>
        <h1 style={{ fontSize: 'clamp(2rem, 1.5rem + 2vw, 3rem)', margin: '10px 0 18px' }}>Search the record</h1>
        <SearchField scopes={['All', 'People', 'Events', 'Places', 'Archive', 'Topics']} />
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'var(--rail-width) 1fr', gap: 'var(--rail-gap)', alignItems: 'start' }}>
        <aside>
          <div style={{ border: 'var(--bw-hair) solid var(--border-default)', background: 'var(--surface-card)' }}>
            <div style={{ fontFamily: 'var(--font-mono)', fontSize: '10px', letterSpacing: '0.07em', textTransform: 'uppercase', color: 'var(--text-muted)', padding: '9px 14px', background: 'var(--surface-sunk)', borderBottom: 'var(--bw-hair) solid var(--border-default)' }}>Refine · 1,284 records</div>
            <div style={{ padding: '14px' }}>
              {[['Content type', ['Biography', 'Event', 'Place', 'Archive', 'Topic']], ['Era', ['Pre-1948', '1948–1990', 'Post-1994']]].map(([group, opts], i) => (
                <div key={i} style={{ marginBottom: i === 0 ? '18px' : 0 }}>
                  <div style={{ fontFamily: 'var(--font-sans)', fontSize: '13px', fontWeight: 700, color: 'var(--text-primary)', marginBottom: '9px' }}>{group}</div>
                  {opts.map((o, j) => (
                    <label key={j} style={{ display: 'flex', alignItems: 'center', gap: '9px', padding: '4px 0', fontFamily: 'var(--font-sans)', fontSize: '13.5px', color: 'var(--text-secondary)', cursor: 'pointer' }}>
                      <input type="checkbox" defaultChecked={i === 0 && j === 0} style={{ accentColor: 'var(--saho-oxblood)', width: '15px', height: '15px', borderRadius: 0 }} /> {o}
                    </label>
                  ))}
                </div>
              ))}
            </div>
          </div>
        </aside>
        <section>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', marginBottom: 'var(--space-3)' }}>
            <span style={{ fontFamily: 'var(--font-mono)', fontSize: '12px', color: 'var(--text-muted)', letterSpacing: '0.04em' }}>RESULTS 1–7 OF 1,284</span>
            <span style={{ fontFamily: 'var(--font-sans)', fontSize: '13px', color: 'var(--text-secondary)' }}>Sort: <b style={{ color: 'var(--text-primary)' }}>Relevance</b></span>
          </div>
          <IndexTable columns={columns} rows={rows} sortKey="title" />
        </section>
      </div>
    </main>
  );
}

Object.assign(window, { SearchScreen });
