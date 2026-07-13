import React from 'react';

/**
 * SAHO Timeline · a first-class navigational surface. The recurring structural
 * motif: a chronology spine with dot markers, era/theme filter tags, and linked
 * events. Filterable by the `themes` toggles. Calm, still, finding-aid styling.
 */
export function Timeline({ events = [], themes = [], title = 'Chronology', style, ...rest }) {
  const [active, setActive] = React.useState('All');
  const filters = ['All', ...themes];
  const shown = active === 'All' ? events : events.filter((e) => e.theme === active);

  return (
    <section style={{ ...style }} {...rest}>
      <header style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', gap: '16px', flexWrap: 'wrap', marginBottom: '16px', borderBottom: '2px solid var(--border-strong)', paddingBottom: '10px' }}>
        <h3 style={{ margin: 0, fontFamily: 'var(--font-display)', fontSize: 'var(--fs-h3)', fontWeight: 700, color: 'var(--text-primary)' }}>{title}</h3>
        {filters.length > 1 && (
          <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
            {filters.map((f) => (
              <button key={f} onClick={() => setActive(f)} style={{
                fontFamily: 'var(--font-sans)', fontSize: '12px', fontWeight: 600,
                padding: '5px 11px', borderRadius: 'var(--radius-xs)', cursor: 'pointer',
                border: `1px solid ${active === f ? 'var(--accent)' : 'var(--border-default)'}`,
                background: active === f ? 'var(--accent)' : 'transparent',
                color: active === f ? 'var(--text-on-accent)' : 'var(--text-secondary)',
              }}>{f}</button>
            ))}
          </div>
        )}
      </header>

      <ol style={{ listStyle: 'none', margin: 0, padding: '0 0 0 24px', borderLeft: '2px solid var(--accent)' }}>
        {shown.map((e, i) => (
          <li key={i} style={{ position: 'relative', paddingBottom: i < shown.length - 1 ? 'var(--space-5)' : 0 }}>
            <span style={{ position: 'absolute', left: '-31px', top: '4px', width: '11px', height: '11px', background: 'var(--surface-page)', border: 'var(--bw-accent) solid var(--accent)' }} />
            <div style={{ fontFamily: 'var(--font-mono)', fontSize: '13px', fontWeight: 600, color: 'var(--accent)', letterSpacing: '0.04em' }}>{e.year}</div>
            <div style={{ marginTop: '2px' }}>
              {e.href
                ? <a href={e.href} style={{ fontFamily: 'var(--font-display)', fontSize: 'var(--fs-h5)', fontWeight: 700, color: 'var(--text-primary)', textDecoration: 'none' }}>{e.title}</a>
                : <span style={{ fontFamily: 'var(--font-display)', fontSize: 'var(--fs-h5)', fontWeight: 700, color: 'var(--text-primary)' }}>{e.title}</span>}
            </div>
            {e.detail && <p style={{ margin: '4px 0 0', fontFamily: 'var(--font-serif)', fontSize: '14px', lineHeight: 1.5, color: 'var(--text-secondary)', maxWidth: '60ch' }}>{e.detail}</p>}
            {e.theme && <span style={{ display: 'inline-block', marginTop: '7px', fontFamily: 'var(--font-mono)', fontSize: '10px', letterSpacing: '0.06em', textTransform: 'uppercase', color: 'var(--text-muted)' }}>{e.theme}</span>}
          </li>
        ))}
      </ol>
    </section>
  );
}
