import React from 'react';

const TYPE_COLOR = {
  article: 'var(--type-article)', biography: 'var(--type-biography)',
  place: 'var(--type-place)', archive: 'var(--type-archive)',
  event: 'var(--type-event)', topic: 'var(--type-topic)',
};

/**
 * SAHO RecordHeader. Frames any archive entity as a catalogue record: a typed
 * folder tab, an accession reference, the record title, and a ruled strip of
 * key fields. This is the data structure made visible: every page is a record.
 */
export function RecordHeader({
  type = 'article',
  reference,
  kicker,
  title,
  facts = [],
  status,
  actions,
  style,
  ...rest
}) {
  const color = TYPE_COLOR[type] || 'var(--type-article)';
  return (
    <header style={{ borderTop: `var(--bw-rule) solid ${color}`, ...style }} {...rest}>
      {/* Catalogue strip: type tab + reference + status */}
      <div style={{ display: 'flex', alignItems: 'stretch', borderBottom: 'var(--bw-hair) solid var(--border-default)' }}>
        <span style={{
          display: 'inline-flex', alignItems: 'center',
          background: color, color: '#fff',
          fontFamily: 'var(--font-sans)', fontSize: '11px', fontWeight: 700,
          letterSpacing: '0.08em', textTransform: 'uppercase',
          padding: '7px 12px',
        }}>{type}</span>
        {reference && (
          <span style={{
            display: 'inline-flex', alignItems: 'center', gap: '8px',
            fontFamily: 'var(--font-mono)', fontSize: '12px', color: 'var(--text-muted)',
            letterSpacing: '0.04em', padding: '7px 12px',
            borderLeft: 'var(--bw-hair) solid var(--border-default)',
          }}>REF <span style={{ color: 'var(--text-primary)', fontWeight: 600 }}>{reference}</span></span>
        )}
        <span style={{ flex: 1 }} />
        {status && (
          <span style={{
            display: 'inline-flex', alignItems: 'center', gap: '7px',
            fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.05em',
            textTransform: 'uppercase', color: 'var(--text-muted)', padding: '7px 12px',
            borderLeft: 'var(--bw-hair) solid var(--border-default)',
          }}><span style={{ width: '7px', height: '7px', borderRadius: 'var(--radius-full)', background: 'var(--saho-success)' }} />{status}</span>
        )}
      </div>

      {/* Title block */}
      <div style={{ padding: 'var(--space-5) 0 var(--space-4)' }}>
        {kicker && <p className="saho-eyebrow" style={{ margin: '0 0 10px' }}>{kicker}</p>}
        <h1 style={{ margin: 0 }}>{title}</h1>
      </div>

      {/* Key fields strip (ruled, tabular) */}
      {facts.length > 0 && (
        <dl style={{
          margin: 0, display: 'flex', flexWrap: 'wrap', gap: 0,
          borderTop: 'var(--bw-hair) solid var(--border-default)',
          borderBottom: 'var(--bw-hair) solid var(--border-default)',
        }}>
          {facts.map((f, i) => (
            <div key={i} style={{
              padding: '10px 18px 10px 0', marginRight: '18px',
              borderRight: i < facts.length - 1 ? 'var(--bw-hair) solid var(--border-faint)' : 'none',
            }}>
              <dt style={{ fontFamily: 'var(--font-mono)', fontSize: '10px', letterSpacing: '0.06em', textTransform: 'uppercase', color: 'var(--text-muted)' }}>{f.label}</dt>
              <dd style={{ margin: '3px 0 0', fontFamily: 'var(--font-sans)', fontSize: '14px', fontWeight: 600, color: 'var(--text-primary)' }}>{f.value}</dd>
            </div>
          ))}
        </dl>
      )}

      {actions && <div style={{ display: 'flex', gap: '10px', marginTop: 'var(--space-4)' }}>{actions}</div>}
    </header>
  );
}
