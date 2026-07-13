import React from 'react';

/**
 * SAHO ContentWarning · a sensitivity affordance for difficult imagery and
 * topics. Obscures content behind a calm, dignified notice the reader chooses
 * to reveal. No sensationalism; dignity is the governing word.
 */
export function ContentWarning({
  reason = 'This page contains imagery and accounts of apartheid-era violence.',
  children,
  revealLabel = 'Show content',
  defaultRevealed = false,
  style,
  ...rest
}) {
  const [revealed, setRevealed] = React.useState(defaultRevealed);

  if (revealed) return <>{children}</>;

  return (
    <div
      style={{
        border: '1px solid var(--border-strong)',
        borderRadius: 'var(--radius-md)',
        background: 'var(--surface-sunk)',
        padding: 'var(--space-6)',
        textAlign: 'center',
        ...style,
      }}
      {...rest}
    >
      <p style={{
        margin: '0 0 6px',
        fontFamily: 'var(--font-sans)',
        fontSize: '11px',
        fontWeight: 700,
        letterSpacing: '0.08em',
        textTransform: 'uppercase',
        color: 'var(--saho-warning)',
      }}>Content sensitivity</p>
      <p style={{
        margin: '0 auto 16px',
        maxWidth: '46ch',
        fontFamily: 'var(--font-serif)',
        fontSize: '15px',
        lineHeight: 1.55,
        color: 'var(--text-secondary)',
      }}>{reason}</p>
      <button
        onClick={() => setRevealed(true)}
        style={{
          fontFamily: 'var(--font-sans)',
          fontSize: '14px',
          fontWeight: 600,
          color: 'var(--text-on-accent)',
          background: 'var(--accent)',
          border: '1px solid var(--accent)',
          borderRadius: 'var(--radius-sm)',
          padding: '9px 18px',
          cursor: 'pointer',
        }}
      >{revealLabel}</button>
    </div>
  );
}
