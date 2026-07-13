/**
 * @file
 * Tab switching for the related-tabs cross-reference engine.
 *
 * The server renders the first tab active; clicking a tab swaps the
 * is-active state on both the tab row and the matching panel.
 */
((Drupal, once) => {
  Drupal.behaviors.sahoRelatedTabs = {
    attach: (context) => {
      once('saho-reltabs', '[data-saho-reltabs]', context).forEach((root) => {
        const tabs = [...root.querySelectorAll('.saho-reltabs__tab')];
        const panels = [...root.querySelectorAll('.saho-reltabs__panel')];
        tabs.forEach((tab) => {
          tab.addEventListener('click', () => {
            const id = tab.getAttribute('data-tab');
            tabs.forEach((t) => {
              const active = t === tab;
              t.classList.toggle('is-active', active);
              t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach((p) => {
              p.classList.toggle('is-active', p.getAttribute('data-panel') === id);
            });
          });
        });
      });
    },
  };
})(Drupal, once);
