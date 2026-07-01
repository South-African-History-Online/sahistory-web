import React from 'react';

/**
 * SAHO ImageCredit · a figure that treats credit and provenance as designed,
 * always-visible elements. Optional duotone unifies disparate archival sources.
 */
export function ImageCredit({
  src,
  alt = '',
  credit,
  source,
  caption,
  duotone = false,
  ratio = '4 / 3',
  style,
  ...rest
}) {
  return (
    <figure style={{ margin: 0, ...style }} {...rest}>
      <div style={{
        position: 'relative',
        aspectRatio: ratio,
        overflow: 'hidden',
        background: 'var(--surface-sunk)',
        border: '1px solid var(--border-default)',
        borderRadius: 'var(--radius-sm)',
      }}>
        {src && (
          <img
            src={src}
            alt={alt}
            style={{
              width: '100%',
              height: '100%',
              objectFit: 'cover',
              display: 'block',
              filter: duotone ? 'var(--img-duotone)' : 'none',
            }}
          />
        )}
      </div>
      <figcaption style={{ marginTop: '8px' }}>
        {caption && (
          <p style={{
            margin: '0 0 4px',
            fontFamily: 'var(--font-serif)',
            fontSize: '14px',
            lineHeight: 1.5,
            color: 'var(--text-secondary)',
            maxWidth: 'none',
          }}>{caption}</p>
        )}
        <p style={{
          margin: 0,
          fontFamily: 'var(--font-mono)',
          fontSize: '11px',
          lineHeight: 1.55,
          color: 'var(--text-muted)',
          maxWidth: 'none',
        }}>
          {credit && <span><span style={{ color: 'var(--text-secondary)', fontWeight: 600 }}>Credit · </span>{credit}</span>}
          {source && <span>{credit ? ' · ' : ''}{source}</span>}
        </p>
      </figcaption>
    </figure>
  );
}
