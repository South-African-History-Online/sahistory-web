/**
 * @file
 * Live search typeahead (R3 #478), on every SAHO search field.
 *
 * Suggestions from /api/search/suggest after 2+ characters with a 250ms
 * debounce. Row anatomy: square type marker + title (match substring
 * underlined) + mono ref, then a "search all N results" row. Full keyboard
 * support. Originally overlay-only; now bound to every saho-search-field SDC
 * (header overlay, front-page block, /search page) - the overlay keeps its
 * external listbox + discovery chips, other fields get an anchored dropdown.
 */
((Drupal, once) => {
  const DEBOUNCE = 250;
  const MIN_CHARS = 2;

  const escapeHtml = (s) => s.replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);

  const markMatch = (title, keyword) => {
    const i = title.toLowerCase().indexOf(keyword.toLowerCase());
    if (i === -1) {
      return escapeHtml(title);
    }
    const before = escapeHtml(title.slice(0, i));
    const match = escapeHtml(title.slice(i, i + keyword.length));
    const after = escapeHtml(title.slice(i + keyword.length));
    return `${before}<u>${match}</u>${after}`;
  };

  /**
   * Wires the typeahead for one input against one suggestions box.
   *
   * @param {HTMLElement} input The search input.
   * @param {HTMLElement} box The listbox to render suggestions into.
   * @param {HTMLElement|null} discovery Optional chips to dim while open.
   */
  const initTypeahead = (input, box, discovery) => {
    let timer = null;
    let active = -1;
    let rows = [];

    const close = () => {
      box.hidden = true;
      box.innerHTML = '';
      active = -1;
      rows = [];
      input.removeAttribute('aria-activedescendant');
      input.setAttribute('aria-expanded', 'false');
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

    // Unique row-id prefix so multiple fields on a page never collide.
    const uid =
      box.id || `sug-${Math.round(input.getBoundingClientRect().top)}-${input.name || 'q'}`;

    const render = (data, keyword) => {
      if (!data.suggestions.length) {
        close();
        return;
      }
      const items = data.suggestions
        .map(
          (s, i) =>
            `<a class="saho-suggest__row" role="option" id="${uid}-${i}" href="${s.url}">` +
            `<span class="saho-suggest__marker saho-type--${s.type}" aria-hidden="true"></span>` +
            `<span class="saho-suggest__title">${markMatch(s.title, keyword)}</span>` +
            `<span class="saho-suggest__ref">${escapeHtml(s.ref || s.type)}</span></a>`
        )
        .join('');
      const allUrl = `/search?search_api_fulltext=${encodeURIComponent(keyword)}`;
      const footer =
        `<a class="saho-suggest__row saho-suggest__all" role="option" id="${uid}-all" href="${allUrl}">` +
        `${Drupal.t('Search all @count results for "@q"', { '@count': data.total.toLocaleString(), '@q': keyword })} &rarr;</a>`;
      box.innerHTML = items + footer;
      box.hidden = false;
      rows = [...box.querySelectorAll('[role=option]')];
      setActive(-1);
      input.setAttribute('aria-expanded', 'true');
      if (discovery) {
        discovery.classList.add('is-dimmed');
      }
      box.setAttribute(
        'aria-label',
        Drupal.t('@count suggestions', { '@count': data.suggestions.length })
      );
    };

    input.setAttribute('role', 'combobox');
    if (box.id) {
      input.setAttribute('aria-controls', box.id);
    }
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('autocomplete', 'off');

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
        // Close only the listbox; stop the event so a surrounding modal's
        // document handler does not also close the whole overlay.
        e.preventDefault();
        e.stopPropagation();
        close();
      }
    });

    document.addEventListener('click', (e) => {
      if (!box.contains(e.target) && e.target !== input) {
        close();
      }
    });
  };

  Drupal.behaviors.sahoSearchSuggest = {
    attach: (context) => {
      once('saho-search-suggest', '.saho-search__input', context).forEach((input) => {
        // The overlay keeps its purpose-built external listbox + discovery
        // chips; every other field gets an anchored dropdown inside its own
        // .saho-search wrapper (created once).
        let box = null;
        let discovery = null;
        if (input.id === 'saho-search-modal-input') {
          box = document.getElementById('sahoSearchSuggest');
          discovery = document.querySelector('.saho-search-discovery');
        }
        if (!box) {
          const wrap = input.closest('.saho-search') || input.parentElement;
          box = wrap.querySelector(':scope > .saho-search__suggest');
          if (!box) {
            box = document.createElement('div');
            box.className = 'saho-search-suggest saho-search__suggest';
            box.setAttribute('role', 'listbox');
            box.setAttribute('aria-label', Drupal.t('Search suggestions'));
            box.hidden = true;
            wrap.appendChild(box);
          }
        }
        if (box) {
          initTypeahead(input, box, discovery);
        }
      });
    },
  };
})(Drupal, once);
