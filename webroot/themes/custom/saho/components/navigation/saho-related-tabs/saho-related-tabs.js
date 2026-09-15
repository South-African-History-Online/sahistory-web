/**
 * @file
 * Tab switching for the related-tabs cross-reference engine.
 *
 * The server renders the first tab active; clicking a tab swaps the
 * is-active state on both the tab row and the matching panel. Keyboard
 * follows the WAI-ARIA APG tabs pattern: one tab stop for the whole row
 * (roving tabindex), ArrowLeft/ArrowRight move and activate with wrap,
 * Home/End jump to the first/last tab.
 */
((Drupal, once) => {
  Drupal.behaviors.sahoRelatedTabs = {
    attach: (context) => {
      once('saho-reltabs', '[data-saho-reltabs]', context).forEach((root) => {
        const tabs = [...root.querySelectorAll('.saho-reltabs__tab')];
        const panels = [...root.querySelectorAll('.saho-reltabs__panel')];

        const activate = (tab) => {
          const id = tab.getAttribute('data-tab');
          tabs.forEach((t) => {
            const active = t === tab;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
            t.setAttribute('tabindex', active ? '0' : '-1');
          });
          panels.forEach((p) => {
            p.classList.toggle('is-active', p.getAttribute('data-panel') === id);
          });
        };

        // Roving tabindex from the server-rendered active tab.
        const initial = tabs.find((t) => t.classList.contains('is-active')) || tabs[0];
        if (initial) {
          tabs.forEach((t) => {
            t.setAttribute('tabindex', t === initial ? '0' : '-1');
          });
        }

        tabs.forEach((tab, i) => {
          tab.addEventListener('click', () => activate(tab));
          tab.addEventListener('keydown', (event) => {
            let next = null;
            if (event.key === 'ArrowRight') {
              next = tabs[(i + 1) % tabs.length];
            } else if (event.key === 'ArrowLeft') {
              next = tabs[(i - 1 + tabs.length) % tabs.length];
            } else if (event.key === 'Home') {
              next = tabs[0];
            } else if (event.key === 'End') {
              next = tabs[tabs.length - 1];
            }
            if (!next) {
              return;
            }
            event.preventDefault();
            next.focus();
            activate(next);
          });
        });
      });
    },
  };
})(Drupal, once);
