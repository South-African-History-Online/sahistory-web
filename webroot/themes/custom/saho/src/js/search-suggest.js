/**
 * @file
 * Search overlay typeahead (R3 #478).
 *
 * Live suggestions from /api/search/suggest after 2+ characters with a
 * 250ms debounce. Row anatomy: square type marker + title (match
 * substring underlined) + mono ref, then a "search all N results" row.
 * Full keyboard support; the discovery chips fade while suggestions are
 * open and return on empty input.
 */
((Drupal, once) => {
  const DEBOUNCE = 250;
  const MIN_CHARS = 2;

  const escapeHtml = (s) => s.replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);

  const markMatch = (title, keyword) => {
    const safe = escapeHtml(title);
    const i = title.toLowerCase().indexOf(keyword.toLowerCase());
    if (i === -1) {
      return safe;
    }
    // Re-find in the escaped string is unsafe; underline via split on the
    // raw string then escape the parts.
    const before = escapeHtml(title.slice(0, i));
    const match = escapeHtml(title.slice(i, i + keyword.length));
    const after = escapeHtml(title.slice(i + keyword.length));
    return `${before}<u>${match}</u>${after}`;
  };

  Drupal.behaviors.sahoSearchSuggest = {
    attach: (context) => {
      once('saho-search-suggest', '#saho-search-modal-input', context).forEach((input) => {
        const box = document.getElementById('sahoSearchSuggest');
        const discovery = document.querySelector('.saho-search-discovery');
        if (!box) {
          return;
        }
        let timer = null;
        let active = -1;
        let rows = [];

        const close = () => {
          box.hidden = true;
          box.innerHTML = '';
          active = -1;
          rows = [];
          input.removeAttribute('aria-activedescendant');
          if (discovery) {
            discovery.classList.remove('is-dimmed');
          }
        };

        const setActive = (i) => {
          active = i;
          rows.forEach((r, n) => {
            r.classList.toggle('is-active', n === i);
            r.setAttribute('aria-selected', n === i ? 'true' : 'false');
          });
          if (i >= 0 && rows[i]) {
            input.setAttribute('aria-activedescendant', rows[i].id);
          }
        };

        const render = (data, keyword) => {
          if (!data.suggestions.length) {
            close();
            return;
          }
          const items = data.suggestions
            .map(
              (s, i) =>
                `<a class="saho-suggest__row" role="option" id="saho-suggest-${i}" href="${s.url}">` +
                `<span class="saho-suggest__marker saho-type--${s.type}" aria-hidden="true"></span>` +
                `<span class="saho-suggest__title">${markMatch(s.title, keyword)}</span>` +
                `<span class="saho-suggest__ref">${escapeHtml(s.ref || s.type)}</span></a>`
            )
            .join('');
          const allUrl = `/search?search_api_fulltext=${encodeURIComponent(keyword)}`;
          const footer =
            `<a class="saho-suggest__row saho-suggest__all" role="option" id="saho-suggest-all" href="${allUrl}">` +
            `${Drupal.t('Search all @count results for "@q"', { '@count': data.total.toLocaleString(), '@q': keyword })} &rarr;</a>`;
          box.innerHTML = items + footer;
          box.hidden = false;
          rows = [...box.querySelectorAll('[role=option]')];
          setActive(-1);
          if (discovery) {
            discovery.classList.add('is-dimmed');
          }
          const live = data.suggestions.length;
          box.setAttribute('aria-label', Drupal.t('@count suggestions', { '@count': live }));
        };

        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-controls', 'sahoSearchSuggest');
        input.setAttribute('aria-expanded', 'false');

        input.addEventListener('input', () => {
          const keyword = input.value.trim();
          clearTimeout(timer);
          if (keyword.length < MIN_CHARS) {
            close();
            return;
          }
          timer = setTimeout(() => {
            fetch(`/api/search/suggest?q=${encodeURIComponent(keyword)}`)
              .then((r) => (r.ok ? r.json() : { total: 0, suggestions: [] }))
              .then((data) => {
                if (input.value.trim() === keyword) {
                  render(data, keyword);
                  input.setAttribute('aria-expanded', String(!box.hidden));
                }
              })
              .catch(close);
          }, DEBOUNCE);
        });

        input.addEventListener('keydown', (e) => {
          if (box.hidden) {
            return;
          }
          if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(active + 1, rows.length - 1));
          } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(active - 1, -1));
          } else if (e.key === 'Enter' && active >= 0) {
            e.preventDefault();
            rows[active].click();
          } else if (e.key === 'Escape') {
            // Escape closes only the suggestion listbox; stop the event so the
            // surrounding search modal's document-level handler does not also
            // close the whole overlay in the same keypress.
            e.preventDefault();
            e.stopPropagation();
            close();
            input.setAttribute('aria-expanded', 'false');
          }
        });

        document.addEventListener('click', (e) => {
          if (!box.contains(e.target) && e.target !== input) {
            close();
          }
        });
      });
    },
  };
})(Drupal, once);
