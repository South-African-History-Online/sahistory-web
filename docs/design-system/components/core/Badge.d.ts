import React from 'react';

export interface BadgeProps {
  /** Content type — drives the colour. @default "article" */
  type?: 'article' | 'biography' | 'place' | 'archive' | 'event' | 'topic';
  children?: React.ReactNode;
  /** Slightly transparent for placement over imagery. */
  onImage?: boolean;
  style?: React.CSSProperties;
}

/** SAHO content-type badge — names what a piece of content is. */
export function Badge(props: BadgeProps): JSX.Element;
