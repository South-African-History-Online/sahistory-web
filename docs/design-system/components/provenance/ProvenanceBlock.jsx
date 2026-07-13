import React from 'react';

/**
 * SAHO ProvenanceBlock · "How we know this." The scholarly apparatus made
 * beautiful and obvious. A titled panel holding an explanatory note and a
 * numbered, linked source list. Provenance is part of the visual language,
 * never buried in a footer.
 */
export function ProvenanceBlock({
  title = 'How we know this',
  note,
  sources = [],
  lastUpdated,
  style,
  ...rest
}) {
  return (
    <section
      style={{
        background: 'var(--surface-card)',
        border: '1px solid var(--border-default)',
        borderLeft: '3px solid var(--accent)',
        borderRadius: 'var(--radius-md)',
        padding: 'var(--space-5)',
        ...style,
      }}
      {...rest}
    >
      <header style={{ display: 'flex', alignItems: 'baseline', gap: '10px', marginBottom: '12px' }}>
        <h3 style={{
          margin: 0,
          fontFamily: 'var(--font-display)',
          fontSize: 'var(--fs-h4)',
          fontWeight: 700,
          color: 'var(--text-primary)',
        }}>{title}</h3>
      </header>

      {note && (
        <p style={{
          margin: '0 0 16px',
          fontFamily: 'var(--font-serif)',
          fontSize: '15px',
          lineHeight: 1.6,
          color: 'var(--text-secondary)',
          maxWidth: '62ch',
        }}>{note}</p>
      )}

      {sources.length > 0 && (
        <ol style={{
          listStyle: 'none',
          counterReset: 'src',
          margin: 0,
          padding: 0,
          borderTop: '1px solid var(--border-faint)',
        }}>
          {sources.map((s, i) => (
            <li key={i} style={{
              counterIncrement: 'src',
              display: 'grid',
              gridTemplateColumns: '28px 1fr',
              gap: '12px',
              padding: '11px 0',
              borderBottom: '1px solid var(--border-faint)',
              alignItems: 'start',
            }}>
              <span style={{
                fontFamily: 'var(--font-mono)',
                fontSize: '12px',
                fontWeight: 600,
                color: 'var(--accent)',
                paddingTop: '2px',
              }}>{String(i + 1).padStart(2, '0')}</span>
              <span style={{ fontFamily: 'var(--font-sans)', fontSize: '13.5px', lineHeight: 1.5, color: 'var(--text-primary)' }}>
                {s.author && <span style={{ fontWeight: 600 }}>{s.author}. </span>}
                {s.href ? <a href={s.href} style={{ color: 'var(--link-rest)' }}>{s.title}</a> : <span style={{ fontStyle: 'italic' }}>{s.title}</span>}
                {s.detail && <span style={{ color: 'var(--text-muted)' }}>. {s.detail}</span>}
              </span>
            </li>
          ))}
        </ol>
      )}

      {lastUpdated && (
        <p style={{
          margin: '12px 0 0',
          fontFamily: 'var(--font-mono)',
          fontSize: '11px',
          letterSpacing: '0.05em',
          textTransform: 'uppercase',
          color: 'var(--text-muted)',
        }}>Last updated · {lastUpdated}</p>
      )}
    </section>
  );
}
