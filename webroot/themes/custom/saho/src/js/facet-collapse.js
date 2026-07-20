/**
 * @file
 * Collapsible facet groups for the Open Record refine rails (/archives, /search).
 *
 * Five facet groups stacked open would wall the rail, so each group's
 * heading becomes a toggle. A group starts open when it is one of the
 * first two (the strongest facets lead the rail) or has an active
 * selection (checked boxes / chosen option); everything else starts
 * collapsed. A mono count chip on the heading shows how many values are
 * active while a group is closed.
 */
((Drupal, once) => {
  Drupal.behaviors.sahoFacetCollapse = {
    attach: (context) => {
      once('saho-facet-collapse', '.saho-search-layout .saho-search-rail form', context).forEach(
        (form) => {
          const groups = [...form.querySelectorAll('fieldset, .form-item-collection')];

          groups.forEach((group, index) => {
            // The rail CSS hides the BEF sort/order widgets; leave them be.
            if (group.offsetParent === null) {
              return;
            }
            const isFieldset = group.matches('fieldset');
            const heading = isFieldset
              ? group.querySelector('legend')
              : group.querySelector('label');
            // Radix fieldsets wrap the options in a plain div after the
            // legend (no .fieldset-wrapper).
            const body = isFieldset
              ? group.querySelector(':scope > div')
              : group.querySelector('select');
            if (!heading || !body) {
              return;
            }

            // A select's "no selection" value is '' (legacy) or 'All'
            // (views exposed filters).
            const selectValue = group.querySelector('select')?.value;
            const activeCount = isFieldset
              ? group.querySelectorAll('input:checked').length
              : selectValue && selectValue !== 'All'
                ? 1
                : 0;

            group.classList.add('saho-facet-group');
            const bodyId = `saho-facet-body-${index}`;
            body.id = body.id || bodyId;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'saho-facet-group__toggle';
            button.setAttribute('aria-controls', body.id);
            while (heading.firstChild) {
              button.appendChild(heading.firstChild);
            }
            if (activeCount > 0) {
              const count = document.createElement('span');
              count.className = 'saho-facet-group__count';
              count.textContent = activeCount;
              button.appendChild(count);
            }
            heading.appendChild(button);

            const open = index < 2 || activeCount > 0;
            group.classList.toggle('is-collapsed', !open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');

            button.addEventListener('click', () => {
              const collapsed = group.classList.toggle('is-collapsed');
              button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            });
          });
        }
      );
    },
  };
})(Drupal, once);
