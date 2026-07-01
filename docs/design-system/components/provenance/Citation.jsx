import React from 'react';

const FORMATS = ['Chicago', 'APA', 'MLA'];

/**
 * SAHO Citation · "Cite this entry." Selectable citation formats rendered in
 * mono. Makes attribution a first-class, copyable action on every entry.
 */
export function Citation({ formats, defaultFormat = 'Chicago', style, ...rest }) {
  const data = formats || {
    Chicago: 'South African History Online. "Nelson Rolihlahla Mandela." Last modified February 11, 2026. https://sahistory.org.za.',
    APA: 'South African History Online. (2026). Nelson Rolihlahla Mandela. Retrieved from https://sahistory.org.za',
    MLA: '"Nelson Rolihlahla Mandela." South African History Online, 11 Feb. 2026, sahistory.org.za.',
  };
  const [active, setActive] = React.useState(defaultFormat);

  return (
    <section
      style={{
        border: '1px solid var(--border-default)',
        borderRadius: 'var(--radius-md)',
        background: 'var(--surface-card)',
        overflow: 'hidden',
        ...style,
      }}
      {...rest}
    >
      <header style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '12px',
        padding: '12px 16px',
        borderBottom: '1px solid var(--border-faint)',
        background: 'var(--surface-sunk)',
      }}>
        <span style={{
          fontFamily: 'var(--font-sans)',
          fontSize: '13px',
          fontWeight: 700,
          letterSpacing: '0.04em',
          textTransform: 'uppercase',
          color: 'var(--text-primary)',
        }}>Cite this entry</span>
        <div style={{ display: 'flex', gap: '4px' }}>
          {FORMATS.map((f) => (
            <button
              key={f}
              onClick={() => setActive(f)}
              style={{
                fontFamily: 'var(--font-mono)',
                fontSize: '11px',
                fontWeight: 600,
                letterSpacing: '0.04em',
                padding: '4px 9px',
                borderRadius: 'var(--radius-xs)',
                cursor: 'pointer',
                border: `1px solid ${active === f ? 'var(--accent)' : 'var(--border-default)'}`,
                background: active === f ? 'var(--accent)' : 'transparent',
                color: active === f ? 'var(--text-on-accent)' : 'var(--text-secondary)',
              }}
            >{f}</button>
          ))}
        </div>
      </header>
      <div style={{ display: 'flex', gap: '14px', alignItems: 'flex-start', padding: '16px' }}>
        <p style={{
          margin: 0,
          flex: 1,
          fontFamily: 'var(--font-mono)',
          fontSize: '12.5px',
          lineHeight: 1.65,
          color: 'var(--text-primary)',
        }}>{data[active]}</p>
        <span style={{
          fontFamily: 'var(--font-sans)',
          fontSize: '12px',
          fontWeight: 600,
          color: 'var(--accent)',
          cursor: 'pointer',
          whiteSpace: 'nowrap',
          paddingTop: '2px',
        }}>Copy</span>
      </div>
    </section>
  );
}
