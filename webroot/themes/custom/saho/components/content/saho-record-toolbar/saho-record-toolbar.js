/**
 * @file
 * Record toolbar behavior (R3 #476): stuck-state REF, permanent-link
 * copy with confirmation, connections jump with header offset.
 *
 * The toolbar sticks below the sticky site header. The CSS token
 * --saho-header-height carries a static per-breakpoint fallback; this
 * behavior refines it to the live-measured header height (zoom, font
 * swaps, admin toolbar) and keys the is-stuck observer off the same
 * offset so the stuck state flips exactly when the toolbar reaches its
 * resting line, not the viewport top.
 */
((Drupal, once) => {
  const header = () => document.querySelector('header.saho-header-wrapper');
  const displaceTop = () =>
    Number.parseFloat(
      getComputedStyle(document.documentElement).getPropertyValue('--drupal-displace-offset-top')
    ) || 0;
  const headerHeight = () => {
    const el = header();
    return el ? el.getBoundingClientRect().height : 78;
  };
  const headerOffset = () => headerHeight() + displaceTop();
  const publishHeaderHeight = () => {
    document.documentElement.style.setProperty(
      '--saho-header-height',
      `${Math.round(headerHeight())}px`
    );
  };

  Drupal.behaviors.sahoRecordToolbar = {
    attach: (context) => {
      once('saho-record-toolbar', '[data-saho-record-toolbar]', context).forEach((bar) => {
        publishHeaderHeight();

        const sentinel = document.createElement('span');
        sentinel.setAttribute('aria-hidden', 'true');
        bar.parentNode.insertBefore(sentinel, bar);
        // The sentinel leaves through the top edge once it passes under the
        // header, which is when the toolbar actually reaches its sticky
        // resting line - hence the negative top rootMargin.
        let observer;
        const observe = () => {
          if (observer) {
            observer.disconnect();
          }
          observer = new IntersectionObserver(
            ([entry]) => {
              bar.classList.toggle('is-stuck', !entry.isIntersecting);
            },
            { rootMargin: `-${Math.ceil(headerOffset())}px 0px 0px 0px` }
          );
          observer.observe(sentinel);
        };
        observe();

        let resizeTimer;
        window.addEventListener('resize', () => {
          clearTimeout(resizeTimer);
          resizeTimer = setTimeout(() => {
            publishHeaderHeight();
            observe();
          }, 250);
        });

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
            const target = document.querySelector('.saho-reltabs');
            if (!target) {
              return;
            }
            // Land the heading clear of the header AND the toolbar, which
            // will be stuck (collapsed to one row on mobile) on arrival.
            const stuckBarHeight = bar.classList.contains('is-stuck') ? bar.offsetHeight : 48;
            const y =
              target.getBoundingClientRect().top +
              window.scrollY -
              (headerOffset() + stuckBarHeight + 12);
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: y, behavior: reduced ? 'auto' : 'smooth' });
          });
        }
      });
    },
  };
})(Drupal, once);
