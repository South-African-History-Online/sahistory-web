import React from 'react';

/**
 * SAHO MetadataBlock · a consistent, tabular key/value block usable on people,
 * events, places and objects. Mono labels, designed like a finding aid · not an
 * afterthought. Optional content-type accent rule down the left edge.
 */
export function MetadataBlock({ items = [], accent, title, style, ...rest }) {
  const accentColor = accent ? `var(--type-${accent})` : 'var(--border-strong)';
  return (
    <section
      style={{
        border: 'var(--bw-hair) solid var(--border-default)',
        borderTop: `var(--bw-rule) solid ${accentColor}`,
        background: 'var(--surface-card)',
        ...style,
      }}
      {...rest}
    >
      {title && (
        <h4 style={{
          margin: 0,
          fontFamily: 'var(--font-mono)',
          fontSize: '10px',
          fontWeight: 600,
          letterSpacing: '0.07em',
          textTransform: 'uppercase',
          color: 'var(--text-muted)',
          padding: '9px 14px',
          background: 'var(--surface-sunk)',
          borderBottom: 'var(--bw-hair) solid var(--border-default)',
        }}>{title}</h4>
      )}
      <dl style={{
        margin: 0,
        display: 'grid',
        gridTemplateColumns: 'minmax(92px, max-content) 1fr',
        gap: 0,
      }}>
        {items.map((it, i) => (
          <React.Fragment key={i}>
            <dt style={{
              fontFamily: 'var(--font-mono)',
              fontSize: '11px',
              letterSpacing: '0.05em',
              textTransform: 'uppercase',
              color: 'var(--text-muted)',
              padding: '9px 14px',
              background: 'var(--surface-sunk)',
              borderBottom: i < items.length - 1 ? 'var(--bw-hair) solid var(--border-faint)' : 'none',
              borderRight: 'var(--bw-hair) solid var(--border-default)',
            }}>{it.label}</dt>
            <dd style={{
              margin: 0,
              fontFamily: 'var(--font-sans)',
              fontSize: '14px',
              lineHeight: 1.45,
              color: 'var(--text-primary)',
              padding: '9px 14px',
              borderBottom: i < items.length - 1 ? 'var(--bw-hair) solid var(--border-faint)' : 'none',
            }}>
              {it.href ? <a href={it.href} style={{ color: 'var(--link-rest)' }}>{it.value}</a> : it.value}
            </dd>
          </React.Fragment>
        ))}
      </dl>
    </section>
  );
}
