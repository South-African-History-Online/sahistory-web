import React from 'react';

export interface CitationProps {
  /** Map of format name → citation string. Defaults to a worked example. */
  formats?: Record<string, string>;
  /** @default "Chicago" */
  defaultFormat?: string;
  style?: React.CSSProperties;
}

/** "Cite this entry" — selectable citation formats, copyable, mono-set. */
export function Citation(props: CitationProps): JSX.Element;
