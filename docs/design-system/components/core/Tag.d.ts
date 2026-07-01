import React from 'react';

export interface TagProps {
  children?: React.ReactNode;
  href?: string;
  /** Selected/active filter state. */
  active?: boolean;
  /** Optional result count rendered in mono. */
  count?: number;
  style?: React.CSSProperties;
}

/** SAHO filter / cross-reference chip — quieter than a content-type Badge. */
export function Tag(props: TagProps): JSX.Element;
