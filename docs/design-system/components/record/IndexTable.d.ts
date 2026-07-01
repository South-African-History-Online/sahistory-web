import React from 'react';

export interface IndexColumn {
  key: string;
  label: string;
  align?: 'left' | 'right' | 'center';
  width?: string;
  sortable?: boolean;
  mono?: boolean;
  muted?: boolean;
  /** Custom cell renderer; receives the row. */
  render?: (row: any) => React.ReactNode;
}

export interface IndexTableProps {
  columns: IndexColumn[];
  rows: Array<Record<string, any>>;
  /** Initial sort column key. */
  sortKey?: string;
  style?: React.CSSProperties;
}

/**
 * The archive pulled in as a ruled catalogue table: rows are records, columns
 * are fields. A `type` column renders a content-type swatch + label.
 * @startingPoint section="Record" subtitle="Archive index / catalogue table" viewport="760x360"
 */
export function IndexTable(props: IndexTableProps): JSX.Element;
