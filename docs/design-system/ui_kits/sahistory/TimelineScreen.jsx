/* SAHO UI kit · Timeline. A first-class navigational surface, filterable. */

function TimelineScreen() {
  return (
    <main style={{ maxWidth: 'var(--container-standard)', margin: '0 auto', padding: 'var(--space-7) var(--gutter-page) 0' }}>
      <header style={{ marginBottom: 'var(--space-6)' }}>
        <span className="saho-eyebrow">Navigational surface</span>
        <h1 style={{ fontSize: 'clamp(2.4rem, 1.7rem + 3vw, 4rem)', margin: '10px 0 14px' }}>A timeline of South African history</h1>
        <p style={{ fontFamily: 'var(--font-serif)', fontSize: 'var(--fs-md)', lineHeight: 1.55, color: 'var(--text-secondary)', maxWidth: '60ch' }}>
          Chronology is how this material wants to be navigated. Filter the record by theme to trace a single thread through four centuries.
        </p>
      </header>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr var(--rail-width)', gap: 'var(--rail-gap)', alignItems: 'start' }}>
        <Timeline title="The record" themes={['Legislation', 'Protest', 'Trials', 'Transition']} events={[
          { year: '1652', title: 'Dutch settlement at the Cape', detail: 'The Dutch East India Company establishes a refreshment station, beginning colonial settlement.', theme: 'Legislation' },
          { year: '1910', title: 'Union of South Africa', detail: 'Four colonies unite; political rights are restricted along racial lines.', theme: 'Legislation', href: '#' },
          { year: '1948', title: 'Apartheid legislated', detail: 'The National Party comes to power and codifies racial segregation into law.', theme: 'Legislation', href: '#' },
          { year: '1955', title: 'The Freedom Charter', detail: 'Adopted at the Congress of the People in Kliptown.', theme: 'Protest' },
          { year: '1960', title: 'Sharpeville massacre', detail: '69 killed during a pass-law protest; the ANC and PAC are banned.', theme: 'Protest', href: '#' },
          { year: '1964', title: 'Rivonia Trial', detail: 'Mandela and others sentenced to life imprisonment.', theme: 'Trials' },
          { year: '1976', title: 'Soweto Uprising', detail: 'Students protest Afrikaans-medium instruction; the state responds with force.', theme: 'Protest', href: '#' },
          { year: '1990', title: 'Mandela released; bans lifted', detail: 'Negotiations to end apartheid begin.', theme: 'Transition' },
          { year: '1994', title: 'First democratic election', detail: 'Universal franchise; Mandela becomes president.', theme: 'Transition', href: '#' },
        ]} />
        <aside style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-6)' }}>
          <div style={{ background: 'var(--surface-card)', border: '1px solid var(--border-default)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)' }}>
            <div style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.06em', textTransform: 'uppercase', color: 'var(--text-muted)', marginBottom: '12px' }}>Filter by era</div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '7px' }}>
              {['Pre-1910', 'Union era', 'Apartheid 1948–1990', 'Transition', 'Democratic'].map((e, i) => <Tag key={i} href="#" active={i === 2}>{e}</Tag>)}
            </div>
          </div>
          <div style={{ background: 'var(--surface-card)', border: '1px solid var(--border-default)', borderRadius: 'var(--radius-md)', padding: 'var(--space-5)' }}>
            <div style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.06em', textTransform: 'uppercase', color: 'var(--text-muted)', marginBottom: '12px' }}>Filter by place</div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '7px' }}>
              {['Cape', 'Gauteng', 'KwaZulu-Natal', 'Eastern Cape'].map((e, i) => <Tag key={i} href="#" count={[210, 184, 96, 72][i]}>{e}</Tag>)}
            </div>
          </div>
        </aside>
      </div>
    </main>
  );
}

Object.assign(window, { TimelineScreen });
