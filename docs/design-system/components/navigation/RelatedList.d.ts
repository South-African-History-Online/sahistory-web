import React from 'react';

export interface RelatedItem {
  label: string;
  type: 'article' | 'biography' | 'place' | 'archive' | 'event' | 'topic';
  note?: string;
  href?: string;
}

export interface RelatedListProps {
  /** @default "Related" */
  title?: string;
  items: RelatedItem[];
  style?: React.CSSProperties;
}

/** Cross-reference list — the connective tissue between people, events, places. */
export function RelatedList(props: RelatedListProps): JSX.Element;
