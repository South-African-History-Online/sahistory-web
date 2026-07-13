import React from 'react';

export interface ProvenanceSource {
  author?: string;
  title: string;
  detail?: string;
  href?: string;
}

/**
 * "How we know this" — SAHO's signature provenance panel.
 * @startingPoint section="Provenance" subtitle="How we know this + source list" viewport="700x380"
 */
export interface ProvenanceBlockProps {
  /** @default "How we know this" */
  title?: string;
  /** Plain-language explanation of sourcing. */
  note?: string;
  sources?: ProvenanceSource[];
  lastUpdated?: string;
  style?: React.CSSProperties;
}

/**
 * "How we know this" — the scholarly apparatus made visible and beautiful.
 */
export function ProvenanceBlock(props: ProvenanceBlockProps): JSX.Element;
