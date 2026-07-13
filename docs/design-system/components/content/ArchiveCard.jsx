import React from 'react';
import { Badge } from '../core/Badge.jsx';

/**
 * SAHO ArchiveCard · the workhorse catalogue card for biographies, articles,
 * events and places. Square, content-type accent edge, optional duotone
 * thumbnail, accession ref + mono meta. Hover selects the record (no lift).
 */
export function ArchiveCard({
  type = 'article',
  title,
  href = '#',
  excerpt,
  image,
  meta,
  dates,
  reference,
  duotone = true,
  style,
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  return (
    <a
      href={href}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      style={{
        display: 'block',
        textDecoration: 'none',
        background: hover ? 'var(--surface-sunk)' : 'var(--surface-card)',
        border: 'var(--bw-hair) solid var(--border-default)',
        borderLeft: `var(--bw-accent) solid var(--type-${type})`,
        boxShadow: 'none',
        transition: 'background var(--t-fast), border-color var(--t-fast)',
        ...(hover ? { borderColor: 'var(--saho-ink)' } : null),
        ...style,
      }}
      {...rest}
    >
      {image && (
        <div style={{ position: 'relative', aspectRatio: '16 / 10', overflow: 'hidden', background: 'var(--surface-sunk)', borderBottom: 'var(--bw-hair) solid var(--border-default)' }}>
          <img src={image} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover', filter: duotone ? 'var(--img-duotone)' : 'none' }} />
          <span style={{ position: 'absolute', top: 0, left: 0 }}><Badge type={type} /></span>
        </div>
      )}
      <div style={{ padding: 'var(--space-4)' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '10px', marginBottom: '10px' }}>
          {!image ? <Badge type={type} /> : <span />}
          {reference && <span style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.04em', color: 'var(--text-muted)' }}>{reference}</span>}
        </div>
        {dates && (
          <p style={{ margin: '0 0 6px', fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.05em', color: 'var(--text-muted)' }}>{dates}</p>
        )}
        <h3 style={{
          margin: '0 0 8px',
          fontFamily: 'var(--font-display)',
          fontSize: 'var(--fs-lg)',
          fontWeight: 700,
          lineHeight: 1.18,
          color: hover ? 'var(--link-hover)' : 'var(--text-primary)',
          transition: 'color var(--t-fast)',
        }}>{title}</h3>
        {excerpt && (
          <p style={{ margin: 0, fontFamily: 'var(--font-serif)', fontSize: '14px', lineHeight: 1.55, color: 'var(--text-secondary)', maxWidth: 'none' }}>{excerpt}</p>
        )}
        {meta && (
          <p style={{ margin: '12px 0 0', fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.04em', textTransform: 'uppercase', color: 'var(--text-muted)', borderTop: 'var(--bw-hair) solid var(--border-faint)', paddingTop: '10px' }}>{meta}</p>
        )}
      </div>
    </a>
  );
}
