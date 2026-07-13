/**
 * @file
 * Deliberate degradation for images that fail to load (R3 X-1).
 *
 * Thousands of legacy image derivatives 404 (the pre-2019 corruption
 * backlog). Two treatments, chosen by context:
 * - Card media wrappers are removed so the card degrades to its
 *   text-only variant.
 * - Record figures (portrait frames, feature banners, gallery tiles)
 *   swap the broken img for the archival placeholder - sunk paper,
 *   hairline frame, mono "No image in the record" - matching the
 *   saho:saho-degraded-image SDC markup.
 */
((Drupal, once) => {
  const CARD_WRAPPERS = [
    '.saho-card-image',
    '.saho-acard__media',
    '.saho-fbio__media',
    '.saho-uev__media',
  ].join(',');

  const FIGURE_WRAPPERS = [
    '.saho-figure__frame',
    '.saho-feature-banner',
    '.saho-degraded-swap',
  ].join(',');

  function placeholder() {
    const el = document.createElement('div');
    el.className = 'saho-degraded saho-degraded--placeholder saho-degraded--auto';
    el.setAttribute('role', 'img');
    el.setAttribute('aria-label', Drupal.t('No image in the record'));
    const marker = document.createElement('span');
    marker.className = 'saho-degraded__marker';
    marker.setAttribute('aria-hidden', 'true');
    const caption = document.createElement('span');
    caption.className = 'saho-degraded__caption';
    caption.setAttribute('aria-hidden', 'true');
    caption.textContent = Drupal.t('No image in the record');
    el.append(marker, caption);
    return el;
  }

  function guard(img) {
    const card = img.closest(CARD_WRAPPERS);
    const figure = card ? null : img.closest(FIGURE_WRAPPERS);
    if (!card && !figure) {
      return;
    }
    const degrade = () => {
      if (card) {
        card.remove();
        return;
      }
      figure.style.position = 'relative';
      img.replaceWith(placeholder());
    };
    if (img.complete && img.naturalWidth === 0) {
      degrade();
      return;
    }
    img.addEventListener('error', degrade, { once: true });
  }

  Drupal.behaviors.sahoBrokenImageGuard = {
    attach: (context) => {
      once(
        'saho-broken-image-guard',
        `${CARD_WRAPPERS} img, ${FIGURE_WRAPPERS} img`,
        context
      ).forEach(guard);
    },
  };
})(Drupal, once);
