import React from 'react';

export interface ContentWarningProps {
  /** What the reader is about to see, stated plainly. */
  reason?: string;
  /** The protected content, revealed on opt-in. */
  children?: React.ReactNode;
  /** @default "Show content" */
  revealLabel?: string;
  defaultRevealed?: boolean;
  style?: React.CSSProperties;
}

/** Sensitivity gate for difficult imagery/topics — calm, dignified opt-in. */
export function ContentWarning(props: ContentWarningProps): JSX.Element;
