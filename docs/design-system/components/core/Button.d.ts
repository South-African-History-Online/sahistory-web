import React from 'react';

/**
 * Button props.
 * @startingPoint section="Core" subtitle="Buttons with SAHO variants" viewport="700x180"
 */
export interface ButtonProps {
  children?: React.ReactNode;
  /** Visual weight. @default "primary" */
  variant?: 'primary' | 'secondary' | 'outline' | 'quiet' | 'ghost';
  /** @default "md" */
  size?: 'sm' | 'md' | 'lg';
  as?: keyof JSX.IntrinsicElements;
  href?: string;
  iconBefore?: React.ReactNode;
  iconAfter?: React.ReactNode;
  fullWidth?: boolean;
  disabled?: boolean;
  style?: React.CSSProperties;
}

/**
 * SAHO action button — restrained institutional style, small radius (no pills).
 */
export function Button(props: ButtonProps): JSX.Element;
