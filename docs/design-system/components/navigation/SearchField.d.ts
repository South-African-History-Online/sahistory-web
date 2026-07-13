import React from 'react';

/**
 * The front-door search — fast, forgiving, prominent.
 * @startingPoint section="Navigation" subtitle="Prominent archive search field" viewport="700x150"
 */
export interface SearchFieldProps {
  placeholder?: string;
  /** @default "lg" */
  size?: 'md' | 'lg';
  /** Scope chips that constrain the search (e.g. content types). */
  scopes?: string[];
  defaultScope?: string;
  /** @default "Search" */
  buttonLabel?: string;
  style?: React.CSSProperties;
}

/** The front-door search — fast, forgiving, prominent. */
export function SearchField(props: SearchFieldProps): JSX.Element;
