import React from 'react';

export interface ImageCreditProps {
  src?: string;
  alt?: string;
  /** Photographer / rights holder. */
  credit?: string;
  /** Collection reference or origin. */
  source?: string;
  /** Descriptive caption (serif). */
  caption?: string;
  /** Apply the unifying archival duotone. */
  duotone?: boolean;
  /** CSS aspect-ratio. @default "4 / 3" */
  ratio?: string;
  style?: React.CSSProperties;
}

/** Archival figure with rigorous, always-visible credit + provenance. */
export function ImageCredit(props: ImageCreditProps): JSX.Element;
