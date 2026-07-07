/**
 * @file
 * Back-to-top affordance (R3).
 *
 * Injected globally: appears once the reader is 1.5 viewports deep,
 * scrolls home smoothly (instant under prefers-reduced-motion), 44px
 * touch target, square Open Record register. No markup dependencies -
 * the button is created here so every page gets it for free.
 */
((Drupal, once) => {
  Drupal.behaviors.sahoScrollTop = {
    attach: (context) => {
      once('saho-scroll-top', 'body', context).forEach((body) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'saho-scroll-top';
        button.setAttribute('aria-label', Drupal.t('Back to top'));
        button.innerHTML =
          '<span class="saho-scroll-top__glyph" aria-hidden="true">&uarr;</span>' +
          '<span class="saho-scroll-top__label" aria-hidden="true">Top</span>';
        body.appendChild(button);

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        button.addEventListener('click', () => {
          window.scrollTo({
            top: 0,
            behavior: reduced.matches ? 'auto' : 'smooth',
          });
        });

        let ticking = false;
        const toggle = () => {
          button.classList.toggle(
            'is-visible',
            window.scrollY > window.innerHeight * 1.5
          );
          ticking = false;
        };
        window.addEventListener(
          'scroll',
          () => {
            if (!ticking) {
              ticking = true;
              window.requestAnimationFrame(toggle);
            }
          },
          { passive: true }
        );
        toggle();
      });
    },
  };
})(Drupal, once);
