import React from 'react';

export interface TimelineEvent {
  year: string;
  title: React.ReactNode;
  detail?: string;
  theme?: string;
  href?: string;
}

/**
 * Filterable chronology — SAHO's first-class navigational surface.
 * @startingPoint section="Navigation" subtitle="Filterable chronology / timeline" viewport="700x460"
 */
export interface TimelineProps {
  events: TimelineEvent[];
  /** Theme names that become filter toggles (plus "All"). */
  themes?: string[];
  /** @default "Chronology" */
  title?: string;
  style?: React.CSSProperties;
}

/** Filterable chronology — SAHO's first-class navigational surface. */
export function Timeline(props: TimelineProps): JSX.Element;
