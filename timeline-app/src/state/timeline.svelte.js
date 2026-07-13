import { configure, getSkeleton } from '../lib/api.js';

/**
 * The single timeline store (Svelte 5 runes, module scope).
 *
 * The skeleton index is held in typed/flat arrays parsed ONCE at load:
 * dates as yyyymmdd ints in an Int32Array, years in an Int16Array.
 * Nothing downstream ever allocates a Date per event - the lesson from
 * the old canvas (issue #486) codified in the data model.
 */
function createTimeline() {
  let settings = $state(null);
  let ready = $state(false);
  let error = $state(null);
  let count = $state(0);
  let range = $state([0, 0]);

  // Flat skeleton columns - not reactive state on purpose: they load
  // once and are read by index. Row order is date-ascending.
  let nids = [];
  let titles = [];
  let precision = [];
  let dateInts = new Int32Array(0);
  let years = new Int16Array(0);

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
    init(drupalSettings) {
      if (settings) {
        return;
      }
      settings = drupalSettings;
      configure(drupalSettings);
      load();
    },
  };
}

export const timeline = createTimeline();
