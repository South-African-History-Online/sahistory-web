import React from 'react';

/**
 * SAHO Button · restrained, institutional. Small radius (no pills), Archivo,
 * oxblood primary. Calm hover (darken + subtle lift), visible focus ring.
 */
export function Button({
  children,
  variant = 'primary',
  size = 'md',
  as = 'button',
  href,
  iconBefore,
  iconAfter,
  fullWidth = false,
  disabled = false,
  style,
  ...rest
}) {
  const sizes = {
    sm: { padding: '6px 12px', fontSize: '13px', gap: '6px' },
    md: { padding: '9px 18px', fontSize: '14px', gap: '8px' },
    lg: { padding: '13px 24px', fontSize: '15px', gap: '10px' },
  };

  const variants = {
    primary: {
      background: 'var(--accent)',
      color: 'var(--text-on-accent)',
      border: '1px solid var(--accent)',
    },
    secondary: {
      background: 'var(--saho-slate)',
      color: '#f3ecdd',
      border: '1px solid var(--saho-slate)',
    },
    outline: {
      background: 'transparent',
      color: 'var(--accent)',
      border: '1px solid var(--accent)',
    },
    quiet: {
      background: 'transparent',
      color: 'var(--text-primary)',
      border: '1px solid var(--border-default)',
    },
    ghost: {
      background: 'transparent',
      color: 'var(--text-secondary)',
      border: '1px solid transparent',
    },
  };

  const base = {
    display: fullWidth ? 'flex' : 'inline-flex',
    width: fullWidth ? '100%' : 'auto',
    alignItems: 'center',
    justifyContent: 'center',
    gap: sizes[size].gap,
    fontFamily: 'var(--font-sans)',
    fontWeight: 600,
    lineHeight: 1.1,
    letterSpacing: '0.01em',
    padding: sizes[size].padding,
    fontSize: sizes[size].fontSize,
    borderRadius: 'var(--radius-sm)',
    cursor: disabled ? 'not-allowed' : 'pointer',
    opacity: disabled ? 0.5 : 1,
    textDecoration: 'none',
    transition: 'background var(--t-fast), color var(--t-fast), box-shadow var(--t-fast)',
    ...variants[variant],
    ...style,
  };

  const Tag = href ? 'a' : as;
  return (
    <Tag href={href} style={base} aria-disabled={disabled || undefined} {...rest}>
      {iconBefore}
      <span>{children}</span>
      {iconAfter}
    </Tag>
  );
}
