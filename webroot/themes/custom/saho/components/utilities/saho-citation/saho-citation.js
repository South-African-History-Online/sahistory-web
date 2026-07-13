/**
 * @file
 * Format switching + copy for the on-page citation SDC.
 *
 * The server renders the default format active; clicking a format tab
 * swaps the is-active state on both the tab and the matching text pane.
 * Copy puts the active pane's text on the clipboard.
 */
((Drupal, once) => {
  const copyText = (text) =>
    navigator.clipboard && window.isSecureContext
      ? navigator.clipboard.writeText(text)
      : new Promise((resolve, reject) => {
          const scratch = document.createElement('textarea');
          scratch.value = text;
          scratch.setAttribute('readonly', '');
          scratch.style.position = 'fixed';
          scratch.style.opacity = '0';
          document.body.appendChild(scratch);
          scratch.select();
          const ok = document.execCommand('copy');
          scratch.remove();
          if (ok) {
            resolve();
          } else {
            reject(new Error('copy rejected'));
          }
        });

  // Named sahoCitationEntry: saho_tools' modal JS already owns
  // Drupal.behaviors.sahoCitation on record pages.
  Drupal.behaviors.sahoCitationEntry = {
    attach: (context) => {
      once('saho-citation', '[data-saho-citation]', context).forEach((root) => {
        const tabs = [...root.querySelectorAll('.saho-cite__fmt')];
        const panes = [...root.querySelectorAll('.saho-cite__text')];
        const copy = root.querySelector('[data-saho-copy]');

        const activate = (tab) => {
          const format = tab.getAttribute('data-format');
          tabs.forEach((t) => {
            const active = t === tab;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
          });
          panes.forEach((p) => {
            p.classList.toggle('is-active', p.getAttribute('data-format') === format);
          });
        };

        tabs.forEach((tab, i) => {
          tab.addEventListener('click', () => activate(tab));
          tab.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
              return;
            }
            event.preventDefault();
            const step = event.key === 'ArrowRight' ? 1 : -1;
            const next = tabs[(i + step + tabs.length) % tabs.length];
            next.focus();
            activate(next);
          });
        });

        if (copy) {
          const label = copy.textContent;
          copy.addEventListener('click', () => {
            const active = panes.find((p) => p.classList.contains('is-active'));
            if (!active) {
              return;
            }
            copyText(active.textContent.trim()).then(
              () => {
                copy.textContent = Drupal.t('Copied');
                setTimeout(() => {
                  copy.textContent = label;
                }, 1500);
              },
              () => {
                copy.textContent = Drupal.t('Copy failed');
                setTimeout(() => {
                  copy.textContent = label;
                }, 1500);
              }
            );
          });
        }
      });
    },
  };
})(Drupal, once);
