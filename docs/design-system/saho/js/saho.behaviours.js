/**
 * @file
 * SAHO progressive-enhancement behaviours. Every interaction degrades to a
 * usable, server-rendered default; JS only enhances. Uses Drupal.behaviors +
 * core/once so it is safe under AJAX and Layout Builder re-renders.
 */
((Drupal, once) => {
  'use strict';

  /* Citation: switch format, copy to clipboard. */
  Drupal.behaviors.sahoCitation = {
    attach(context) {
      once('saho-citation', '[data-saho-citation]', context).forEach((root) => {
        const texts = root.querySelectorAll('.saho-cite__text');
        root.querySelectorAll('.saho-cite__fmt').forEach((btn) => {
          btn.addEventListener('click', () => {
            const fmt = btn.dataset.format;
            root.querySelectorAll('.saho-cite__fmt').forEach((b) => {
              const on = b === btn;
              b.classList.toggle('is-active', on);
              b.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            texts.forEach((t) => t.classList.toggle('is-active', t.dataset.format === fmt));
          });
        });
        const copy = root.querySelector('[data-saho-copy]');
        if (copy) {
          copy.addEventListener('click', () => {
            const active = root.querySelector('.saho-cite__text.is-active');
            if (active && navigator.clipboard) {
              navigator.clipboard.writeText(active.textContent.trim());
              const label = copy.textContent;
              copy.textContent = 'Copied';
              setTimeout(() => { copy.textContent = label; }, 1600);
            }
          });
        }
      });
    },
  };

  /* Content warning: reveal protected content on opt-in. */
  Drupal.behaviors.sahoContentWarning = {
    attach(context) {
      once('saho-cw', '[data-saho-content-warning]', context).forEach((root) => {
        const btn = root.querySelector('[data-cw-reveal]');
        const notice = root.querySelector('[data-cw-notice]');
        const content = root.querySelector('[data-cw-content]');
        if (btn && content) {
          btn.addEventListener('click', () => {
            content.hidden = false;
            if (notice) notice.hidden = true;
          });
        }
      });
    },
  };

  /* Timeline: filter events by theme. */
  Drupal.behaviors.sahoTimeline = {
    attach(context) {
      once('saho-timeline', '[data-saho-timeline]', context).forEach((root) => {
        const events = root.querySelectorAll('.saho-timeline__event');
        root.querySelectorAll('.saho-timeline__filter').forEach((btn) => {
          btn.addEventListener('click', () => {
            const theme = btn.dataset.theme;
            root.querySelectorAll('.saho-timeline__filter').forEach((b) => b.classList.toggle('is-active', b === btn));
            events.forEach((ev) => {
              ev.hidden = !(theme === 'All' || ev.dataset.theme === theme);
            });
          });
        });
      });
    },
  };

  /* Search scope chips (visual selection; the form still submits ?q=). */
  Drupal.behaviors.sahoSearchScope = {
    attach(context) {
      once('saho-scopes', '[data-saho-scopes]', context).forEach((root) => {
        root.querySelectorAll('.saho-search__scope').forEach((btn) => {
          btn.addEventListener('click', () => {
            root.querySelectorAll('.saho-search__scope').forEach((b) => b.classList.toggle('is-active', b === btn));
          });
        });
      });
    },
  };

  /* Index table: client-side column sort (server order is authoritative). */
  Drupal.behaviors.sahoIndexTable = {
    attach(context) {
      once('saho-sortable', '[data-saho-sortable]', context).forEach((root) => {
        const table = root.querySelector('table');
        const tbody = table && table.tBodies[0];
        if (!tbody) return;
        root.querySelectorAll('th[data-sort-key]').forEach((th, colIndex) => {
          const sort = () => {
            const asc = th.getAttribute('aria-sort') !== 'ascending';
            root.querySelectorAll('th[data-sort-key]').forEach((h) => h.setAttribute('aria-sort', 'none'));
            th.setAttribute('aria-sort', asc ? 'ascending' : 'descending');
            const idx = Array.from(th.parentNode.children).indexOf(th);
            const rows = Array.from(tbody.rows);
            rows.sort((a, b) => {
              const x = a.cells[idx].textContent.trim();
              const y = b.cells[idx].textContent.trim();
              const n = parseFloat(x) - parseFloat(y);
              const cmp = Number.isNaN(n) ? x.localeCompare(y) : n;
              return asc ? cmp : -cmp;
            });
            rows.forEach((r) => tbody.appendChild(r));
          };
          th.addEventListener('click', sort);
          th.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sort(); } });
        });
      });
    },
  };
})(Drupal, once);
