/**
 * @file
 * The one quiet sort control on /archives.
 *
 * The select's option values are "sort_by|sort_order" pairs; changing it
 * rewrites the current URL's views sort parameters (everything else -
 * query, facets, layout - is preserved) and navigates. No Apply button.
 */
((Drupal, once) => {
  Drupal.behaviors.sahoArchiveSort = {
    attach: (context) => {
      once('saho-archive-sort', '[data-saho-archive-sort]', context).forEach((select) => {
        select.addEventListener('change', () => {
          const [sortBy, sortOrder] = select.value.split('|');
          const url = new URL(window.location.href);
          url.searchParams.set('sort_by', sortBy);
          url.searchParams.set('sort_order', sortOrder);
          url.searchParams.delete('page');
          window.location.assign(url.toString());
        });
      });
    },
  };
})(Drupal, once);
