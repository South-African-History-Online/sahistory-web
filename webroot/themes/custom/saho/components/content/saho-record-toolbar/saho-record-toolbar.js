/**
 * @file
 * Record toolbar behavior (R3 #476): stuck-state REF, permanent-link
 * copy with confirmation, connections jump with header offset.
 */
((Drupal, once) => {
  Drupal.behaviors.sahoRecordToolbar = {
    attach: (context) => {
      once('saho-record-toolbar', '[data-saho-record-toolbar]', context).forEach((bar) => {
        const sentinel = document.createElement('span');
        sentinel.setAttribute('aria-hidden', 'true');
        bar.parentNode.insertBefore(sentinel, bar);
        new IntersectionObserver(([entry]) => {
          bar.classList.toggle('is-stuck', !entry.isIntersecting);
        }).observe(sentinel);

        const live = bar.querySelector('.saho-record-toolbar__live');
        const announce = (message) => {
          if (live) {
            live.textContent = message;
          }
        };

        const permanent = bar.querySelector('[data-saho-permanent-link]');
        if (permanent) {
          const label = permanent.textContent;
          permanent.addEventListener('click', () => {
            const url = permanent.getAttribute('data-saho-permanent-link');
            const confirm = () => {
              permanent.textContent = Drupal.t('Copied · this link is permanent');
              announce(Drupal.t('Permanent link copied'));
              setTimeout(() => {
                permanent.textContent = label;
              }, 2000);
            };
            if (navigator.clipboard?.writeText) {
              navigator.clipboard.writeText(url).then(confirm);
            } else {
              const input = document.createElement('input');
              input.value = url;
              document.body.appendChild(input);
              input.select();
              document.execCommand('copy');
              input.remove();
              confirm();
            }
          });
        }

        const connections = bar.querySelector('[data-saho-connections]');
        if (connections) {
          connections.addEventListener('click', () => {
            const target = document.querySelector('.saho-reltabs, [data-record-connections]');
            if (!target) {
              return;
            }
            const y = target.getBoundingClientRect().top + window.scrollY - 56;
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: y, behavior: reduced ? 'auto' : 'smooth' });
          });
        }
      });
    },
  };
})(Drupal, once);
