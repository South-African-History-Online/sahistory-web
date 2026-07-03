/**
 * @file
 * Hides card media wrappers whose image fails to load.
 *
 * Thousands of legacy image derivatives 404 (the pre-2019 corruption
 * backlog), which leaves broken-image boxes inside card grids. When an
 * image inside a known media wrapper errors, the wrapper is removed so the
 * card degrades to its text-only variant.
 */
((Drupal, once) => {
  const WRAPPERS = [
    '.saho-card-image',
    '.saho-acard__media',
    '.saho-fbio__media',
    '.saho-uev__media',
  ].join(',');

  function guard(img) {
    const wrapper = img.closest(WRAPPERS);
    if (!wrapper) {
      return;
    }
    const remove = () => {
      wrapper.remove();
    };
    if (img.complete && img.naturalWidth === 0) {
      remove();
      return;
    }
    img.addEventListener('error', remove, { once: true });
  }

  Drupal.behaviors.sahoBrokenImageGuard = {
    attach: (context) => {
      once('saho-broken-image-guard', `${WRAPPERS} img`, context).forEach(guard);
    },
  };
})(Drupal, once);
