/* SAHO UI kit · shared site chrome (header, footer, accent rule).
   Uses global React (loaded via UMD) and the compiled DS bundle. */

const { useState } = React;

function AccentBar() {
  return <div style={{ height: '3px', background: 'linear-gradient(90deg, var(--saho-oxblood) 0%, var(--saho-oxblood) 62%, var(--saho-ochre) 62%, var(--saho-ochre) 100%)' }} />;
}

function Wordmark({ small }) {
  return (
    <a href="#home" data-nav="home" style={{ display: 'flex', alignItems: 'center', gap: '12px', textDecoration: 'none' }}>
      <img src="../../assets/saho-logo.svg" alt="SAHO" style={{ width: small ? '36px' : '46px', height: small ? '36px' : '46px' }} />
      <span style={{ lineHeight: 1.04 }}>
        <span style={{ display: 'block', fontFamily: 'var(--font-display)', fontWeight: 800, fontSize: small ? '17px' : '20px', color: 'var(--text-primary)', letterSpacing: '-0.015em' }}>South African History Online</span>
        <span style={{ display: 'block', fontFamily: 'var(--font-mono)', fontSize: '10.5px', letterSpacing: '0.08em', textTransform: 'uppercase', color: 'var(--saho-oxblood)', marginTop: '2px' }}>The open record · est. 2000</span>
      </span>
    </a>
  );
}

function SiteHeader({ active, onNav }) {
  const items = [
    ['Biographies', 'biography'], ['Timeline', 'timeline'], ['Topics', 'home'],
    ['Places', 'home'], ['Archive', 'search'], ['Classroom', 'home'],
  ];
  return (
    <header style={{ position: 'sticky', top: 0, zIndex: 1030, background: 'var(--saho-paper-raised)', borderBottom: '1px solid var(--border-default)' }}>
      <AccentBar />
      <div style={{ maxWidth: 'var(--container-wide)', margin: '0 auto', padding: '14px var(--gutter-page)', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '24px' }}>
        <Wordmark />
        <div style={{ display: 'flex', alignItems: 'center', gap: '18px' }}>
          <nav style={{ display: 'flex', gap: '4px' }}>
            {items.map(([label, key], i) => (
              <a key={i} href={'#' + key} data-nav={key} onClick={() => onNav && onNav(key)} style={{
                fontFamily: 'var(--font-sans)', fontSize: '14px', fontWeight: 600,
                color: active === key ? 'var(--saho-oxblood)' : 'var(--text-primary)',
                textDecoration: 'none', padding: '8px 11px', borderRadius: 'var(--radius-xs)',
                borderBottom: active === key ? '2px solid var(--saho-oxblood)' : '2px solid transparent',
              }}>{label}</a>
            ))}
          </nav>
          <a href="#search" data-nav="search" onClick={() => onNav && onNav('search')} aria-label="Search" style={{
            display: 'flex', alignItems: 'center', justifyContent: 'center', width: '40px', height: '40px',
            background: 'var(--accent)', color: 'var(--text-on-accent)', borderRadius: 'var(--radius-sm)',
            textDecoration: 'none', fontSize: '19px',
          }}>⌕</a>
        </div>
      </div>
    </header>
  );
}

function SiteFooter() {
  const cols = [
    ['Browse', ['Biographies', 'Topics', 'Places', 'Timeline', 'Archive']],
    ['Programmes', ['Classroom', 'Commemorations', 'Features', 'Educator resources']],
    ['About SAHO', ['Our mission', 'How we source', 'Contribute', 'Contact']],
  ];
  return (
    <footer style={{ background: 'var(--saho-ink)', color: 'var(--text-on-dark)', marginTop: 'var(--space-9)' }}>
      <div style={{ height: '3px', background: 'linear-gradient(90deg, var(--saho-oxblood) 0%, var(--saho-oxblood) 62%, var(--saho-ochre) 62%, var(--saho-ochre) 100%)' }} />
      <div style={{ maxWidth: 'var(--container-wide)', margin: '0 auto', padding: 'var(--space-8) var(--gutter-page) var(--space-6)', display: 'grid', gridTemplateColumns: '1.4fr 1fr 1fr 1fr', gap: '40px' }}>
        <div>
          <div style={{ fontFamily: 'var(--font-display)', fontWeight: 800, fontSize: '20px', letterSpacing: '-0.015em' }}>South African History Online</div>
          <p style={{ fontFamily: 'var(--font-serif)', fontSize: '14px', lineHeight: 1.6, color: '#c8bba4', maxWidth: '34ch', marginTop: '10px' }}>The largest independent, non-partisan history archive in South Africa. Anti-paywall by principle · knowledge here is free, sourced and accessible.</p>
        </div>
        {cols.map(([title, links], i) => (
          <div key={i}>
            <div style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.06em', textTransform: 'uppercase', color: 'var(--saho-ochre)', marginBottom: '12px' }}>{title}</div>
            {links.map((l, j) => <a key={j} href="#" style={{ display: 'block', fontFamily: 'var(--font-sans)', fontSize: '13.5px', color: '#d8cdb9', textDecoration: 'none', padding: '5px 0' }}>{l}</a>)}
          </div>
        ))}
      </div>
      <div style={{ maxWidth: 'var(--container-wide)', margin: '0 auto', padding: '16px var(--gutter-page)', borderTop: '1px solid rgba(255,255,255,0.12)', display: 'flex', justifyContent: 'space-between', gap: '16px', flexWrap: 'wrap' }}>
        <span style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', color: '#a89a82' }}>© 2000–2026 SAHO · Content licensed CC BY-NC-SA 4.0</span>
        <span style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', color: '#a89a82' }}>Self-hosted · WCAG 2.2 AA</span>
      </div>
    </footer>
  );
}

Object.assign(window, { AccentBar, Wordmark, SiteHeader, SiteFooter });
