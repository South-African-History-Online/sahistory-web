import React from 'react';

/**
 * SAHO Tag · a small bordered chip for filters, eras, themes and cross-references.
 * Quieter than a Badge: used for navigation, not content-type identity.
 */
export function Tag({ children, href, active = false, count, style, ...rest }) {
  const Tag = href ? 'a' : 'span';
  return (
    <Tag
      href={href}
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        gap: '7px',
        fontFamily: 'var(--font-sans)',
        fontSize: '13px',
        fontWeight: 500,
        color: active ? 'var(--text-on-accent)' : 'var(--text-primary)',
        background: active ? 'var(--accent)' : 'var(--surface-card)',
        border: `1px solid ${active ? 'var(--accent)' : 'var(--border-default)'}`,
        padding: '5px 11px',
        borderRadius: 'var(--radius-xs)',
        textDecoration: 'none',
        cursor: href ? 'pointer' : 'default',
        transition: 'background var(--t-fast), border-color var(--t-fast)',
        ...style,
      }}
      {...rest}
    >
      {children}
      {count != null && (
        <span style={{
          fontFamily: 'var(--font-mono)',
          fontSize: '11px',
          color: active ? 'rgba(255,255,255,0.8)' : 'var(--text-muted)',
        }}>{count}</span>
      )}
    </Tag>
  );
}
