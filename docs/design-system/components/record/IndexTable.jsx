import React from 'react';

const TYPE_COLOR = {
  article: 'var(--type-article)', biography: 'var(--type-biography)',
  place: 'var(--type-place)', archive: 'var(--type-archive)',
  event: 'var(--type-event)', topic: 'var(--type-topic)',
};

/**
 * SAHO IndexTable. The archive itself, pulled in as a ruled catalogue: rows are
 * records, columns are fields. The primary way to browse and query the data.
 * Sortable headers, a content-type swatch per row, mono accession refs.
 */
export function IndexTable({ columns = [], rows = [], sortKey, style, ...rest }) {
  const [sort, setSort] = React.useState(sortKey || null);
  return (
    <div style={{ border: 'var(--bw-hair) solid var(--border-default)', background: 'var(--surface-card)', ...style }} {...rest}>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontFamily: 'var(--font-sans)' }}>
        <thead>
          <tr>
            {columns.map((c, i) => (
              <th key={i} onClick={() => c.sortable && setSort(c.key)} style={{
                textAlign: c.align || 'left',
                fontFamily: 'var(--font-mono)', fontSize: '10px', fontWeight: 600,
                letterSpacing: '0.07em', textTransform: 'uppercase',
                color: sort === c.key ? 'var(--text-primary)' : 'var(--text-muted)',
                padding: '10px 14px', whiteSpace: 'nowrap',
                background: 'var(--surface-sunk)',
                borderBottom: 'var(--bw-rule) solid var(--border-strong)',
                cursor: c.sortable ? 'pointer' : 'default',
                width: c.width,
              }}>
                {c.label}{c.sortable && sort === c.key ? ' \u2193' : ''}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((r, ri) => (
            <tr key={ri} style={{ borderBottom: 'var(--bw-hair) solid var(--border-faint)', cursor: 'pointer' }}
              onMouseEnter={(e) => { e.currentTarget.style.background = 'var(--surface-sunk)'; }}
              onMouseLeave={(e) => { e.currentTarget.style.background = 'transparent'; }}>
              {columns.map((c, ci) => (
                <td key={ci} style={{
                  textAlign: c.align || 'left',
                  padding: '11px 14px', verticalAlign: 'baseline',
                  fontFamily: c.mono ? 'var(--font-mono)' : 'var(--font-sans)',
                  fontSize: c.mono ? '12px' : '14px',
                  color: c.muted ? 'var(--text-muted)' : 'var(--text-primary)',
                  fontWeight: c.key === 'title' ? 600 : 400,
                }}>
                  {c.key === 'type' ? (
                    <span style={{ display: 'inline-flex', alignItems: 'center', gap: '8px' }}>
                      <span style={{ width: '9px', height: '9px', background: TYPE_COLOR[r.type] || 'var(--type-article)' }} />
                      <span style={{ fontFamily: 'var(--font-mono)', fontSize: '11px', letterSpacing: '0.04em', textTransform: 'uppercase', color: 'var(--text-muted)' }}>{r.type}</span>
                    </span>
                  ) : c.render ? c.render(r) : r[c.key]}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
