/* SAHO UI kit · Biography. A catalogue record view: record header with accession
   ref, ruled field tables, the sourced article, provenance, and typed relations. */

function BioRecordHeader() {
  return (
    <RecordHeader
      type="biography"
      reference="B-0427"
      kicker="Biography record"
      title="Nelson Rolihlahla Mandela"
      status="Verified"
      facts={[
        { label: 'Born', value: '18 Jul 1918' },
        { label: 'Died', value: '5 Dec 2013' },
        { label: 'Lived', value: '95 years' },
        { label: 'Sources', value: '14' },
        { label: 'Updated', value: '2026-02-11' },
      ]}
      actions={[
        <Button key="1" variant="primary" size="sm">Cite this record</Button>,
        <Button key="2" variant="quiet" size="sm">Download sources</Button>,
      ]}
    />
  );
}

function BioArticle() {
  return (
    <article style={{ maxWidth: 'var(--measure)' }}>
      <p style={{ fontFamily: 'var(--font-serif)', fontSize: 'var(--fs-base)', lineHeight: 1.62, color: 'var(--text-primary)' }}>
        Nelson Mandela was born in <a href="#">Mvezo</a>, in the <a href="#">Eastern Cape</a>, on 18 July 1918. Trained as a lawyer, he joined the <a href="#">African National Congress</a> in 1943 and rose to prominence in the <a href="#">Defiance Campaign</a> of 1952, organising mass resistance to the apartheid state's pass laws and racial legislation.
      </p>
      <p style={{ fontFamily: 'var(--font-serif)', fontSize: 'var(--fs-base)', lineHeight: 1.62, color: 'var(--text-primary)' }}>
        Following the <a href="#">Sharpeville massacre</a> in 1960 and the banning of the ANC, Mandela went underground and helped found Umkhonto we Sizwe. He was arrested in 1962 and, at the <a href="#">Rivonia Trial</a>, sentenced to life imprisonment on <a href="#">Robben Island</a>.
      </p>
      <blockquote>
        "I have cherished the ideal of a democratic and free society. It is an ideal which I hope to live for and to achieve. But if needs be, it is an ideal for which I am prepared to die."
      </blockquote>
      <p style={{ fontFamily: 'var(--font-serif)', fontSize: 'var(--fs-base)', lineHeight: 1.62, color: 'var(--text-primary)' }}>
        Released in 1990, Mandela led negotiations to end apartheid. In 1994 he became president in the country's first fully representative election, presiding over the <a href="#">Truth and Reconciliation Commission</a> and the transition to constitutional democracy.
      </p>
      <div style={{ marginTop: 'var(--space-7)' }}>
        <ProvenanceBlock
          note="This record is compiled from the Nelson Mandela Foundation archive, contemporary press records, and court transcripts, and is cross-checked against the Truth and Reconciliation Commission report (1998)."
          sources={[
            { author: 'Truth and Reconciliation Commission', title: 'Final Report, vol. 2', detail: 'Cape Town, 1998', href: '#' },
            { author: 'Mandela, N.', title: 'Long Walk to Freedom', detail: 'Little, Brown, 1994' },
            { author: 'Sampson, A.', title: 'Mandela: The Authorised Biography', detail: 'HarperCollins, 1999' },
            { title: 'SAHO Biography Project, ref. B-0427', href: '#' },
          ]}
          lastUpdated="2026-02-11" />
      </div>
      <div style={{ marginTop: 'var(--space-5)' }}>
        <Citation />
      </div>
    </article>
  );
}

function BioRail() {
  return (
    <aside style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-6)' }}>
      <ImageCredit src="../../assets/default-portrait.svg" duotone ratio="3 / 4"
        credit="Photographer unknown, c.1961" source="SAHO Collection, ref. P-1182" />
      <MetadataBlock accent="biography" title="Record fields" items={[
        { label: 'Full name', value: 'Nelson Rolihlahla Mandela' },
        { label: 'Born', value: '18 July 1918, Mvezo' },
        { label: 'Died', value: '5 Dec 2013, Johannesburg' },
        { label: 'Roles', value: 'Activist, Statesman' },
        { label: 'Office', value: 'President, 1994–1999' },
        { label: 'Affiliation', value: 'ANC', href: '#' },
      ]} />
      <Timeline title="Life chronology" events={[
        { year: '1944', title: 'Joins the ANC Youth League' },
        { year: '1952', title: 'Defiance Campaign', href: '#' },
        { year: '1964', title: 'Rivonia Trial, life sentence' },
        { year: '1990', title: 'Released from prison', href: '#' },
        { year: '1994', title: 'Elected president' },
      ]} />
      <RelatedList title="Linked records" items={[
        { label: 'Walter Sisulu', type: 'biography', note: '1912–2003' },
        { label: 'Robben Island', type: 'place', note: 'Western Cape' },
        { label: 'Rivonia Trial', type: 'event', note: '1963–1964' },
        { label: 'Freedom Charter', type: 'archive', note: '1955' },
      ]} />
    </aside>
  );
}

function BiographyScreen() {
  return (
    <main style={{ maxWidth: 'var(--container-standard)', margin: '0 auto', padding: 'var(--space-5) var(--gutter-page) 0' }}>
      <nav style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.05em', color: 'var(--text-muted)', marginBottom: 'var(--space-4)' }}>
        <a href="#home" data-nav="home" style={{ color: 'var(--text-muted)' }}>ARCHIVE</a> / <a href="#" style={{ color: 'var(--text-muted)' }}>BIOGRAPHIES</a> / <span style={{ color: 'var(--saho-oxblood)' }}>B-0427</span>
      </nav>
      <BioRecordHeader />
      <div style={{ display: 'grid', gridTemplateColumns: '1fr var(--rail-width)', gap: 'var(--rail-gap)', alignItems: 'start', marginTop: 'var(--space-6)' }}>
        <BioArticle />
        <BioRail />
      </div>
    </main>
  );
}

Object.assign(window, { BiographyScreen });
