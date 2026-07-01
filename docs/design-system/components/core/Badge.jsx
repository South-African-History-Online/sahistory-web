import React from 'react';

const TYPE_COLOR = {
  article: 'var(--type-article)',
  biography: 'var(--type-biography)',
  place: 'var(--type-place)',
  archive: 'var(--type-archive)',
  event: 'var(--type-event)',
  topic: 'var(--type-topic)',
};

/**
 * SAHO content-type badge. Small, uppercase Plex Sans label that names what a
 * piece of content is. Solid (on paper) or onImage (90% opacity over photos).
 */
export function Badge({ type = 'article', children, onImage = false, style, ...rest }) {
  const color = TYPE_COLOR[type] || 'var(--type-article)';
  const label = children || type.charAt(0).toUpperCase() + type.slice(1);
  return (
    <span
      style={{
        display: 'inline-block',
        fontFamily: 'var(--font-sans)',
        fontSize: '11px',
        fontWeight: 600,
        letterSpacing: '0.05em',
        textTransform: 'uppercase',
        color: '#fff',
        background: color,
        padding: '4px 9px',
        borderRadius: 'var(--radius-xs)',
        opacity: onImage ? 0.92 : 1,
        ...style,
      }}
      {...rest}
    >
      {label}
    </span>
  );
}
