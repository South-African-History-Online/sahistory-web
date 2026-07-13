import { configure, getSkeleton, getBucket, bucketForYear } from '../lib/api.js';

/**
 * The single timeline store (Svelte 5 runes, module scope).
 *
 * The skeleton index is held in typed/flat arrays parsed ONCE at load:
 * dates as yyyymmdd ints in an Int32Array, years in an Int16Array.
 * Nothing downstream ever allocates a Date per event - the lesson from
 * the old canvas (issue #486) codified in the data model.
 *
 * Detail cards arrive per decade bucket and are keyed by nid; the
 * cardVersion counter is the reactivity signal for hydration (the Map
 * itself is deliberately non-reactive).
 */
function createTimeline() {
  let settings = $state(null);
  let ready = $state(false);
  let error = $state(null);
  let count = $state(0);
  let range = $state([0, 0]);
  let cardVersion = $state(0);

  // Flat skeleton columns - not reactive state on purpose: they load
  // once and are read by index. Row order is date-ascending.
  let nids = [];
  let titles = [];
  let precision = [];
  let dateInts = new Int32Array(0);
  let years = new Int16Array(0);

  const cards = new Map();
  const loadedBuckets = new Set();

  async function load() {
    try {
      const data = await getSkeleton();
      nids = data.nids;
      titles = data.titles;
      precision = data.precision;
      const n = data.dates.length;
      dateInts = new Int32Array(n);
      years = new Int16Array(n);
      for (let i = 0; i < n; i++) {
        // 'YYYY-MM-DD' -> yyyymmdd without Date allocation.
        const d = data.dates[i];
        const y = +d.slice(0, 4);
        dateInts[i] = y * 10000 + +d.slice(5, 7) * 100 + +d.slice(8, 10);
        years[i] = y;
      }
      count = data.count;
      range = data.range;
      ready = true;
    }
    catch (e) {
      error = e;
    }
  }

  async function ensureBucket(token) {
    if (loadedBuckets.has(token)) {
      return;
    }
    loadedBuckets.add(token);
    try {
      const data = await getBucket(token);
      for (const event of data.events) {
        cards.set(event.nid, event);
      }
      cardVersion++;
    }
    catch {
      // Refetchable on the next request for this range.
      loadedBuckets.delete(token);
    }
  }

  return {
    get ready() { return ready; },
    get error() { return error; },
    get count() { return count; },
    get range() { return range; },
    get settings() { return settings; },
    get nids() { return nids; },
    get titles() { return titles; },
    get precisionCodes() { return precision; },
    get dateInts() { return dateInts; },
    get years() { return years; },
    get cardVersion() { return cardVersion; },

    init(drupalSettings) {
      if (settings) {
        return;
      }
      settings = drupalSettings;
      configure(drupalSettings);
      load();
    },

    /** The hydrated card for a row's nid, if its bucket has arrived. */
    card(nid) {
      return cards.get(nid);
    },

    /**
     * Ensures detail buckets covering rows [loIdx, hiIdx] are loading,
     * plus one bucket of lookahead each side for scroll momentum.
     */
    requestRange(loIdx, hiIdx) {
      if (!ready || count === 0) {
        return;
      }
      const lo = Math.max(0, loIdx);
      const hi = Math.min(count - 1, hiIdx);
      const tokens = new Set();
      for (let i = lo; i <= hi; i += 1) {
        tokens.add(bucketForYear(years[i]));
      }
      tokens.add(bucketForYear(years[Math.max(0, lo - 40)]));
      tokens.add(bucketForYear(years[Math.min(count - 1, hi + 40)]));
      for (const token of tokens) {
        ensureBucket(token);
      }
    },

    /** First row index whose year >= the given year (binary search). */
    firstIndexForYear(year) {
      let lo = 0;
      let hi = count;
      while (lo < hi) {
        const mid = (lo + hi) >> 1;
        if (years[mid] < year) {
          lo = mid + 1;
        }
        else {
          hi = mid;
        }
      }
      return Math.min(lo, count - 1);
    },

    /** Row index for a nid, or -1. */
    indexOfNid(nid) {
      return nids.indexOf(nid);
    },
  };
}

export const timeline = createTimeline();

const MONTHS = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

/**
 * Human date label from a yyyymmdd int + precision code - no Date
 * allocation, honest about precision (a year-only event says '1651',
 * never a fabricated 1 January).
 */
export function formatDate(dateInt, precisionCode) {
  const y = Math.floor(dateInt / 10000);
  const m = Math.floor((dateInt % 10000) / 100);
  const d = dateInt % 100;
  switch (precisionCode) {
    case 'd':
      return `${d} ${MONTHS[m - 1] ?? ''} ${y}`;

    case 'm':
      return `${MONTHS[m - 1] ?? ''} ${y}`;

    case 'c':
      return `c. ${y}`;

    case 'r':
      return `${y} -`;

    default:
      return String(y);
  }
}
