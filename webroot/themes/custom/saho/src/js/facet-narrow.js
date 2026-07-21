/**
 * @file
 * Scroll wells for long facet checkbox lists (/archives, /search).
 *
 * Facet groups with more than 12 values render as a scrollable sunk well
 * with a mono total on the heading - the visible rows are a window onto
 * the full count-ordered list. Long lists get a well, not SHOW MORE chip
 * walls. (A per-group filter input was tried and retired 2026-07-21:
 * count-ordered lists put the strong values on top, so the input was
 * apparatus clutter ahead of the actual options.)
 */
((Drupal, once) => {
  const THRESHOLD = 12;

  Drupal.behaviors.sahoFacetNarrow = {
    attach: (context) => {
      once(
        'saho-facet-narrow',
        '.saho-search-layout .saho-search-rail .bef-checkboxes',
        context
      ).forEach((group) => {
        const items = [...group.querySelectorAll('.form-item')];
        if (items.length <= THRESHOLD) {
          return;
        }

        group.classList.add('saho-facet-narrow');

        const legend = group.closest('fieldset')?.querySelector('legend');

        // A mono total on the heading says how much the well holds - the
        // visible rows are a window, not the full list.
        if (legend && !legend.querySelector('.saho-facet-narrow__count')) {
          const total = document.createElement('span');
          total.className = 'saho-facet-narrow__count';
          total.textContent = items.length;
          (legend.querySelector('.saho-facet-group__toggle') || legend).appendChild(total);
        }
      });
    },
  };
})(Drupal, once);
