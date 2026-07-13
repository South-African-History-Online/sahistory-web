/**
 * URL state for the timeline explorer.
 *
 * Contract (on the Drupal /timeline route):
 * - ?year=1976     scroll target; throttled replaceState while scrolling
 * - ?era=apartheid pushState on era chip
 * - ?q=soweto      pushState on search
 * - #e-12401       open detail panel; legacy #event-12401 also parsed
 */

export function readUrlState() {
  const params = new URLSearchParams(window.location.search);
  const hash = window.location.hash.match(/^#(?:e|event)-(\d+)$/);
  return {
    year: params.get('year') ? parseInt(params.get('year'), 10) || null : null,
    era: params.get('era'),
    q: params.get('q'),
    event: hash ? parseInt(hash[1], 10) : null,
  };
}

function buildUrl({ year = null, era = null, q = null, event = null }) {
  const params = new URLSearchParams();
  if (q) {
    params.set('q', q);
  }
  if (era) {
    params.set('era', era);
  }
  if (year) {
    params.set('year', String(year));
  }
  const qs = params.toString();
  return `${window.location.pathname}${qs ? `?${qs}` : ''}${event ? `#e-${event}` : ''}`;
}

let lastReplace = 0;

/**
 * Throttled replaceState - scroll position updates, 1/sec, no history
 * spam. Pass force for deliberate one-shot updates (panel prev/next)
 * that must not be swallowed by the throttle.
 */
export function replaceUrlState(state, force = false) {
  const now = Date.now();
  if (!force && now - lastReplace < 1000) {
    return;
  }
  lastReplace = now;
  history.replaceState(null, '', buildUrl(state));
}

/** pushState - deliberate navigation (era, search, event panel). */
export function pushUrlState(state) {
  history.pushState(null, '', buildUrl(state));
}

export function onUrlChange(callback) {
  const handler = () => callback(readUrlState());
  window.addEventListener('popstate', handler);
  return () => window.removeEventListener('popstate', handler);
}
