import React from 'react';

export interface RecordFact {
  label: string;
  value: React.ReactNode;
}

export interface RecordHeaderProps {
  type?: 'article' | 'biography' | 'place' | 'archive' | 'event' | 'topic';
  /** Accession reference, e.g. "B-0427". */
  reference?: string;
  /** Mono eyebrow, e.g. "Biography record". */
  kicker?: string;
  title: React.ReactNode;
  /** Ruled key-field strip. */
  facts?: RecordFact[];
  /** Verification/status label (shows a status dot). */
  status?: string;
  actions?: React.ReactNode;
  style?: React.CSSProperties;
}

/**
 * Catalogue record header: typed tab, accession ref, title and ruled key fields.
 * @startingPoint section="Record" subtitle="Catalogue record header with accession ref" viewport="760x300"
 */
export function RecordHeader(props: RecordHeaderProps): JSX.Element;
