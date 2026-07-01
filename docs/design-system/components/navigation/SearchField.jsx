import React from 'react';

/**
 * SAHO SearchField · the front door. Fast, forgiving, prominent. A large
 * paper-sunk field with a serif placeholder voice and an oxblood submit.
 * Optional scope chips constrain the search across content types.
 */
export function SearchField({
  placeholder = 'Search people, events, places, dates…',
  size = 'lg',
  scopes = [],
  defaultScope,
  buttonLabel = 'Search',
  style,
  ...rest
}) {
  const [scope, setScope] = React.useState(defaultScope || (scopes[0] || null));
  const pad = size === 'lg' ? '16px 18px' : '11px 14px';
  const fs = size === 'lg' ? '17px' : '15px';

  return (
    <div style={{ ...style }} {...rest}>
      <form style={{
        display: 'flex', alignItems: 'stretch',
        background: 'var(--surface-card)',
        border: '2px solid var(--border-strong)',
        borderRadius: 'var(--radius-md)',
        overflow: 'hidden',
      }} onSubmit={(e) => e.preventDefault()}>
        <span style={{ display: 'flex', alignItems: 'center', paddingLeft: '16px', color: 'var(--text-muted)', fontSize: '18px' }} aria-hidden>⌕</span>
        <input
          type="search"
          placeholder={placeholder}
          style={{
            flex: 1, border: 'none', outline: 'none', background: 'transparent',
            padding: pad, fontFamily: 'var(--font-sans)', fontSize: fs,
            color: 'var(--text-primary)',
          }}
        />
        <button type="submit" style={{
          border: 'none', cursor: 'pointer',
          background: 'var(--accent)', color: 'var(--text-on-accent)',
          fontFamily: 'var(--font-sans)', fontSize: fs, fontWeight: 600,
          padding: '0 22px',
        }}>{buttonLabel}</button>
      </form>
      {scopes.length > 0 && (
        <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap', marginTop: '12px' }}>
          {scopes.map((s) => (
            <button key={s} onClick={() => setScope(s)} style={{
              fontFamily: 'var(--font-sans)', fontSize: '12.5px', fontWeight: 500,
              padding: '5px 11px', borderRadius: 'var(--radius-xs)', cursor: 'pointer',
              border: `1px solid ${scope === s ? 'var(--accent)' : 'var(--border-default)'}`,
              background: scope === s ? 'var(--accent)' : 'transparent',
              color: scope === s ? 'var(--text-on-accent)' : 'var(--text-secondary)',
            }}>{s}</button>
          ))}
        </div>
      )}
    </div>
  );
}
