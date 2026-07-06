/**
 * @file
 * In-rail facet lookup for long checkbox lists (/archives).
 *
 * Facet groups with more than 12 values render as a scrollable sunk well
 * with a mono filter input above it: typing narrows the checkbox list
 * client-side (diacritic-insensitive), checked values always stay visible.
 * Long lists need lookup, not SHOW MORE chip walls.
 */
((Drupal, once) => {
  const THRESHOLD = 12;

  const normalize = (text) =>
    text
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();

  Drupal.behaviors.sahoFacetNarrow = {
    attach: (context) => {
      once(
        'saho-facet-narrow',
        '.saho-archive-index .saho-search-rail .bef-checkboxes',
        context
      ).forEach((group) => {
        const items = [...group.querySelectorAll('.form-item')];
        if (items.length <= THRESHOLD) {
          return;
        }

        group.classList.add('saho-facet-narrow');

        const legend = group.closest('fieldset')?.querySelector('legend');
        const groupLabel = legend?.textContent.trim();

        // A mono total on the heading says how much the well holds - the
        // visible rows are a window, not the full list.
        if (legend && !legend.querySelector('.saho-facet-narrow__count')) {
          const total = document.createElement('span');
          total.className = 'saho-facet-narrow__count';
          total.textContent = items.length;
          (legend.querySelector('.saho-facet-group__toggle') || legend).appendChild(total);
        }
        const input = document.createElement('input');
        input.type = 'search';
        input.className = 'saho-facet-narrow__input';
        input.placeholder = groupLabel
          ? Drupal.t('Filter @label…', { '@label': groupLabel.toLowerCase() })
          : Drupal.t('Filter options…');
        input.setAttribute('aria-label', input.placeholder);
        group.parentNode.insertBefore(input, group);

        input.addEventListener('input', () => {
          const query = normalize(input.value.trim());
          items.forEach((item) => {
            const label = item.querySelector('label');
            const checked = item.querySelector('input[type="checkbox"]')?.checked;
            const match = query === '' || normalize(label?.textContent || '').includes(query);
            item.hidden = !(checked || match);
          });
        });
      });
    },
  };
})(Drupal, once);
