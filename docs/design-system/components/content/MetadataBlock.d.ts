import React from 'react';

export interface MetadataItem {
  label: string;
  value: React.ReactNode;
  href?: string;
}

/**
 * Consistent tabular metadata block for people, events, places and objects.
 * @startingPoint section="Content" subtitle="Tabular metadata / finding-aid block" viewport="560x320"
 */
export interface MetadataBlockProps {
  items: MetadataItem[];
  /** Optional content-type accent for the top rule. */
  accent?: 'article' | 'biography' | 'place' | 'archive' | 'event' | 'topic';
  /** Mono section label. */
  title?: string;
  style?: React.CSSProperties;
}

/** Consistent tabular metadata block for people, events, places and objects. */
export function MetadataBlock(props: MetadataBlockProps): JSX.Element;
