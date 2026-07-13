import React from 'react';

const TYPE_COLOR = {
  article: 'var(--type-article)', biography: 'var(--type-biography)',
  place: 'var(--type-place)', archive: 'var(--type-archive)',
  event: 'var(--type-event)', topic: 'var(--type-topic)',
};

/**
 * SAHO RelatedList · the connective tissue. Knits people, events, places and
 * dates together as cross-references. A dot marks each item's content type so
 * colour is never the sole carrier of meaning (the type is also labelled).
 */
export function RelatedList({ title = 'Related', items = [], style, ...rest }) {
  return (
    <section style={{ ...style }} {...rest}>
      <h4 style={{
        margin: '0 0 4px', fontFamily: 'var(--font-mono)', fontSize: '11px',
        fontWeight: 600, letterSpacing: '0.06em', textTransform: 'uppercase',
        color: 'var(--text-muted)', borderBottom: '1px solid var(--border-default)', paddingBottom: '8px',
      }}>{title}</h4>
      <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
        {items.map((it, i) => (
          <li key={i} style={{ borderBottom: '1px solid var(--border-faint)' }}>
            <a href={it.href || '#'} style={{
              display: 'flex', alignItems: 'baseline', gap: '10px',
              padding: '11px 0', textDecoration: 'none',
            }}>
              <span style={{ width: '9px', height: '9px', flex: 'none', background: TYPE_COLOR[it.type] || 'var(--type-article)', transform: 'translateY(1px)' }} />
              <span style={{ flex: 1 }}>
                <span style={{ display: 'block', fontFamily: 'var(--font-sans)', fontSize: '14px', fontWeight: 600, color: 'var(--text-primary)' }}>{it.label}</span>
                {it.note && <span style={{ display: 'block', fontFamily: 'var(--font-sans)', fontSize: '12.5px', color: 'var(--text-muted)' }}>{it.note}</span>}
              </span>
              <span style={{ fontFamily: 'var(--font-mono)', fontSize: '10px', letterSpacing: '0.05em', textTransform: 'uppercase', color: 'var(--text-muted)' }}>{it.type}</span>
            </a>
          </li>
        ))}
      </ul>
    </section>
  );
}
