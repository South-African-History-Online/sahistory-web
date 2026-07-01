import React from 'react';

/**
 * The workhorse index card for archive content.
 * @startingPoint section="Content" subtitle="Archive index card (bio/article/event/place)" viewport="360x420"
 */
export interface ArchiveCardProps {
  type?: 'article' | 'biography' | 'place' | 'archive' | 'event' | 'topic';
  title: React.ReactNode;
  href?: string;
  excerpt?: string;
  image?: string;
  /** Mono meta line, e.g. "Article · 8 min read". */
  meta?: string;
  /** Date span, e.g. "1918–2013". */
  dates?: string;
  /** Accession reference, e.g. "B-0427". */
  reference?: string;
  /** Apply archival duotone to the thumbnail. @default true */
  duotone?: boolean;
  style?: React.CSSProperties;
}

/** The workhorse index card for archive content. */
export function ArchiveCard(props: ArchiveCardProps): JSX.Element;
