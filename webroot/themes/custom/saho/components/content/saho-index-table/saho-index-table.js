/**
 * @file
 * SAHO Index Table SDC - client-side column sorting.
 *
 * Progressive enhancement: the server-rendered row order is authoritative
 * and remains the initial state. Nothing reorders until the user interacts
 * with a sortable header (th[data-sort-key]).
 */

((Drupal, once) => {
  /**
   * Parses a value as a plain number, with commas stripped.
   *
   * Accepts leading zeros and thousands separators ("1,301"). Returns NaN
   * for anything that is not purely numeric so mixed refs like "B-0007970"
   * fall through to the locale-aware string compare.
   */
  function toNumber(value) {
    const cleaned = value.replace(/,/g, '').trim();
    if (cleaned === '' || !/^-?\d*\.?\d+$/.test(cleaned)) {
      return Number.NaN;
    }
    return Number.parseFloat(cleaned);
  }

  /**
   * Returns the sort value for a row at the given column index.
   *
   * Prefers an explicit data-sort-value attribute on the cell, otherwise
   * uses the trimmed text content.
   */
  function cellValue(row, index) {
    const cell = row.cells[index];
    if (!cell) {
      return '';
    }
    const explicit = cell.getAttribute('data-sort-value');
    return explicit !== null ? explicit : cell.textContent.trim();
  }

  /**
   * Numeric-aware comparator; falls back to localeCompare.
   */
  function compareValues(a, b) {
    const numA = toNumber(a);
    const numB = toNumber(b);
    if (!Number.isNaN(numA) && !Number.isNaN(numB)) {
      return numA - numB;
    }
    return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
  }

  /**
   * Sorts the table body by the clicked header's column.
   */
  function sortByHeader(wrapper, th) {
    const table = wrapper.querySelector('table');
    const tbody = table ? table.tBodies[0] : null;
    if (!tbody) {
      return;
    }

    // Cycle: none -> ascending -> descending -> ascending.
    const direction = th.getAttribute('aria-sort') === 'ascending' ? 'descending' : 'ascending';

    // Reset every sortable header, then mark the active one.
    wrapper.querySelectorAll('th[data-sort-key]').forEach((header) => {
      header.setAttribute('aria-sort', header === th ? direction : 'none');
    });

    const index = th.cellIndex;
    const factor = direction === 'descending' ? -1 : 1;
    const rows = Array.from(tbody.rows);

    rows.sort(
      (rowA, rowB) => factor * compareValues(cellValue(rowA, index), cellValue(rowB, index))
    );

    const fragment = document.createDocumentFragment();
    rows.forEach((row) => {
      fragment.appendChild(row);
    });
    tbody.appendChild(fragment);
  }

  /**
   * Wires click and keyboard sorting on one sortable table wrapper.
   */
  function initSortable(wrapper) {
    wrapper.querySelectorAll('th[data-sort-key]').forEach((th) => {
      th.addEventListener('click', () => {
        sortByHeader(wrapper, th);
      });
      th.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          sortByHeader(wrapper, th);
        }
      });
    });
  }

  Drupal.behaviors.sahoIndexTableSort = {
    attach: (context) => {
      once('saho-index-table', '[data-saho-sortable]', context).forEach((wrapper) => {
        initSortable(wrapper);
      });
    },
  };
})(Drupal, once);
