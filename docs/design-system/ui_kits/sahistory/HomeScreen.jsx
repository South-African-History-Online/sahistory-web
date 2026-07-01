/* SAHO UI kit · Home. Not a splash: the catalogue front page. A status line of
   record counts, the current editorial feature, a browse index, and the most
   recent additions pulled in as a catalogue table. */

function ArchiveStatusBar() {
  const stats = [['Records', '21,684'], ['Biographies', '4,210'], ['Events', '2,140'], ['Places', '930'], ['Documents', '12,400'], ['Sources cited', '58,902']];
  return (
    <div style={{ display: 'flex', flexWrap: 'wrap', borderTop: 'var(--bw-rule) solid var(--saho-ink)', borderBottom: 'var(--bw-hair) solid var(--border-default)' }}>
      {stats.map(([k, v], i) => (
        <div key={i} style={{ padding: '10px 18px 10px 0', marginRight: '18px', borderRight: i < stats.length - 1 ? 'var(--bw-hair) solid var(--border-faint)' : 'none' }}>
          <div style={{ fontFamily: 'var(--font-mono)', fontSize: '10px', letterSpacing: '0.06em', textTransform: 'uppercase', color: 'var(--text-muted)' }}>{k}</div>
          <div style={{ fontFamily: 'var(--font-mono)', fontSize: '17px', fontWeight: 600, color: 'var(--text-primary)', marginTop: '2px' }}>{v}</div>
        </div>
      ))}
    </div>
  );
}

function HomeFeature() {
  return (
    <section style={{ display: 'grid', gridTemplateColumns: '1.55fr 1fr', gap: 'var(--space-7)', alignItems: 'stretch', margin: 'var(--space-7) 0', borderBottom: 'var(--bw-hair) solid var(--border-default)', paddingBottom: 'var(--space-7)' }}>
      <article style={{ background: 'var(--saho-ink)', color: 'var(--text-on-dark)', padding: 'var(--space-6)', borderTop: 'var(--bw-accent) solid var(--saho-oxblood)' }}>
        <span style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.07em', textTransform: 'uppercase', color: 'var(--saho-ochre)' }}>Current feature · Commemoration</span>
        <h1 className="saho-editorial-title" style={{ fontSize: 'clamp(2.4rem, 1.5rem + 3.4vw, 4.2rem)', margin: '14px 0 16px', color: 'var(--text-on-dark)' }}>The children of the Soweto Uprising</h1>
        <p style={{ fontFamily: 'var(--font-serif)', fontSize: 'var(--fs-md)', lineHeight: 1.6, color: '#c8c2b2', maxWidth: '52ch' }}>
          On 16 June 1976, thousands of Soweto students protested the imposition of Afrikaans as a language of instruction. The state's response reshaped the struggle. Forty-nine years on, we revisit the record: the names, the photographs, and how we know what happened.
        </p>
        <div style={{ display: 'flex', gap: '12px', marginTop: '22px', flexWrap: 'wrap' }}>
          <Button variant="primary" size="lg">Open the feature</Button>
          <Button variant="ghost" size="lg" style={{ color: 'var(--saho-ochre)', borderColor: 'rgba(255,255,255,0.25)' }}>View chronology</Button>
        </div>
        <p style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.05em', color: '#9a937f', marginTop: '20px' }}>EDITORIAL REGISTER · 14 LINKED RECORDS</p>
      </article>
      <aside style={{ border: 'var(--bw-hair) solid var(--border-default)', display: 'flex', flexDirection: 'column' }}>
        <div style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.06em', textTransform: 'uppercase', color: 'var(--text-muted)', padding: '11px 16px', background: 'var(--surface-sunk)', borderBottom: 'var(--bw-hair) solid var(--border-default)' }}>This day · 16 June</div>
        <div style={{ padding: '6px 16px 16px', flex: 1 }}>
          {[['1976', 'Soweto Uprising begins', 'event'], ['1980', 'Cape Town schools boycott', 'event'], ['1913', 'Natives Land Act protests', 'topic']].map(([y, t, type], i) => (
            <a key={i} href="#" style={{ display: 'grid', gridTemplateColumns: '9px 1fr', gap: '12px', padding: '12px 0', borderBottom: i < 2 ? 'var(--bw-hair) solid var(--border-faint)' : 'none', textDecoration: 'none', alignItems: 'baseline' }}>
              <span style={{ width: '9px', height: '9px', background: `var(--type-${type})`, transform: 'translateY(3px)' }} />
              <span>
                <span style={{ display: 'block', fontFamily: 'var(--font-mono)', fontSize: '12px', color: 'var(--accent)', fontWeight: 600 }}>{y}</span>
                <span style={{ display: 'block', fontFamily: 'var(--font-display)', fontSize: '17px', fontWeight: 700, color: 'var(--text-primary)', lineHeight: 1.2 }}>{t}</span>
              </span>
            </a>
          ))}
          <a href="#timeline" data-nav="timeline" style={{ fontFamily: 'var(--font-sans)', fontSize: '13px', fontWeight: 600, display: 'inline-block', marginTop: '8px' }}>Open the full timeline →</a>
        </div>
      </aside>
    </section>
  );
}

function BrowseIndex() {
  const items = [
    ['Biographies', 'biography', '4,210'], ['Topics', 'topic', '1,860'], ['Places', 'place', '930'],
    ['Events', 'event', '2,140'], ['Archive', 'archive', '12,400'], ['Classroom', 'article', '320'],
  ];
  return (
    <section style={{ marginBottom: 'var(--space-7)' }}>
      <h2 style={{ fontSize: 'var(--fs-h3)', marginBottom: '16px' }}>Browse the archive</h2>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(210px, 1fr))', gap: 0, borderTop: 'var(--bw-hair) solid var(--border-default)', borderLeft: 'var(--bw-hair) solid var(--border-default)' }}>
        {items.map(([label, type, count], i) => (
          <a key={i} href="#" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '12px', background: 'var(--surface-card)', borderLeft: `var(--bw-accent) solid var(--type-${type})`, borderRight: 'var(--bw-hair) solid var(--border-default)', borderBottom: 'var(--bw-hair) solid var(--border-default)', padding: '15px 16px', textDecoration: 'none' }}>
            <span style={{ fontFamily: 'var(--font-display)', fontSize: '19px', fontWeight: 700, color: 'var(--text-primary)' }}>{label}</span>
            <span style={{ fontFamily: 'var(--font-mono)', fontSize: '12px', color: 'var(--text-muted)' }}>{count}</span>
          </a>
        ))}
      </div>
    </section>
  );
}

function RecentRecords() {
  const rows = [
    { ref: 'B-0427', type: 'biography', title: 'Nelson Rolihlahla Mandela', dates: '1918–2013', status: 'Revised' },
    { ref: 'E-1190', type: 'event', title: 'The Sharpeville massacre', dates: '21 Mar 1960', status: 'Verified' },
    { ref: 'P-0042', type: 'place', title: 'Robben Island', dates: 'Western Cape', status: 'Verified' },
    { ref: 'A-2255', type: 'archive', title: 'The Freedom Charter', dates: '26 Jun 1955', status: 'New' },
    { ref: 'T-0318', type: 'topic', title: 'The Defiance Campaign', dates: '1952', status: 'Revised' },
  ];
  const columns = [
    { key: 'ref', label: 'Ref', mono: true, width: '92px' },
    { key: 'type', label: 'Type', sortable: true, width: '150px' },
    { key: 'title', label: 'Record', sortable: true,
      render: (r) => <a href="#biography" data-nav="biography" style={{ color: 'var(--text-primary)', textDecoration: 'none', fontWeight: 600 }}>{r.title}</a> },
    { key: 'dates', label: 'Dates', mono: true, muted: true, width: '150px' },
    { key: 'status', label: 'Status', mono: true, muted: true, width: '110px' },
  ];
  return (
    <section style={{ paddingBottom: 'var(--space-7)' }}>
      <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', marginBottom: '16px' }}>
        <h2 style={{ fontSize: 'var(--fs-h3)', margin: 0 }}>Recently added to the archive</h2>
        <a href="#search" data-nav="search" style={{ fontFamily: 'var(--font-sans)', fontSize: '14px', fontWeight: 600 }}>All updates →</a>
      </div>
      <IndexTable columns={columns} rows={rows} sortKey="title" />
    </section>
  );
}

function HomeScreen() {
  return (
    <main style={{ maxWidth: 'var(--container-standard)', margin: '0 auto', padding: 'var(--space-5) var(--gutter-page) 0' }}>
      <div style={{ marginBottom: 'var(--space-5)' }}>
        <SearchField scopes={['All', 'People', 'Events', 'Places', 'Archive']} />
      </div>
      <ArchiveStatusBar />
      <HomeFeature />
      <BrowseIndex />
      <RecentRecords />
    </main>
  );
}

Object.assign(window, { HomeScreen });
