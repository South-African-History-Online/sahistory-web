<script>
  import { timeline } from '../state/timeline.svelte.js';
  import EventRow from './EventRow.svelte';

  /**
   * The Register: a custom two-tier virtualizer over the skeleton.
   *
   * Every row can render instantly in compact form from skeleton data
   * (fixed estimated height) and hydrates in place when its decade
   * bucket arrives. Real heights are measured by one shared
   * ResizeObserver into a prefix-sum offset array - 3.5k entries, so a
   * full rebuild is a trivial O(n) pass scheduled per animation frame.
   * The page itself scrolls (no inner scrollbox): natural on mobile.
   */

  let { onopen = null, visible = null, onviewchange = null } = $props();

  const ESTIMATE = 96;
  const BUFFER = 10;

  // rowAt maps display position -> global row index (identity unless a
  // filter is active).
  const rowCount = $derived(visible ? visible.length : timeline.count);
  const rowAt = (pos) => (visible ? visible[pos] : pos);

  let heights = new Float64Array(0);
  let offsets = new Float64Array(1);
  let totalHeight = $state(0);
  // Bumped whenever offsets are rebuilt: heights/offsets are plain
  // arrays mutated in place, so this counter is the reactive signal
  // that row positions changed.
  let layoutVersion = $state(0);
  let lo = $state(0);
  let hi = $state(-1);
  let containerEl = $state(null);

  $effect(() => {
    // (Re)size measurement arrays when the row set changes. All
    // reactive writes (layoutVersion, lo/hi, totalHeight) happen in the
    // scheduled rAF, never synchronously inside this effect - a sync
    // write here re-enters the flush and Svelte aborts with
    // effect_update_depth_exceeded.
    const n = rowCount;
    heights = new Float64Array(n).fill(ESTIMATE);
    offsets = new Float64Array(n + 1);
    scheduleRebuild();
  });

  function rebuildOffsets() {
    let sum = 0;
    for (let i = 0; i < heights.length; i += 1) {
      offsets[i] = sum;
      sum += heights[i];
    }
    offsets[heights.length] = sum;
    totalHeight = sum;
    layoutVersion += 1;
  }

  let rebuildScheduled = false;
  function scheduleRebuild() {
    if (rebuildScheduled) {
      return;
    }
    rebuildScheduled = true;
    requestAnimationFrame(() => {
      rebuildScheduled = false;

      // Scroll anchoring: hydration changes row heights, which moves
      // every row below. Without compensation the viewport slides onto
      // different rows, requests THEIR buckets, and the register crawls
      // away from where the reader actually was. Pin the first visible
      // row across the rebuild - unless a jump target is pending, which
      // takes precedence and re-glues the view to the target row.
      const targetActive = pendingTarget !== null && performance.now() < pendingUntil;
      if (pendingTarget !== null && !targetActive) {
        pendingTarget = null;
      }
      if (targetActive) {
        // Heights are still settling - keep gluing while rebuilds keep
        // coming (rolling window; user input cancels outright).
        pendingUntil = performance.now() + 900;
      }

      let anchorPos = -1;
      let anchorOldOffset = 0;
      if (!targetActive && containerEl && heights.length > 0) {
        const viewTop = -containerEl.getBoundingClientRect().top;
        if (viewTop > 0) {
          anchorPos = indexAt(viewTop);
          anchorOldOffset = offsets[anchorPos];
        }
      }

      rebuildOffsets();

      if (targetActive) {
        applyTarget();
      }
      else if (anchorPos >= 0) {
        const delta = offsets[anchorPos] - anchorOldOffset;
        if (delta !== 0) {
          window.scrollBy({ top: delta, behavior: 'instant' });
        }
      }
      updateWindow();
    });
  }

  /** Binary search: last offset <= y. */
  function indexAt(y) {
    let a = 0;
    let b = heights.length - 1;
    while (a < b) {
      const mid = (a + b + 1) >> 1;
      if (offsets[mid] <= y) {
        a = mid;
      }
      else {
        b = mid - 1;
      }
    }
    return a;
  }

  function updateWindow() {
    if (!containerEl || heights.length === 0) {
      hi = -1;
      return;
    }
    const rect = containerEl.getBoundingClientRect();
    const viewTop = -rect.top;
    const viewBottom = viewTop + window.innerHeight;
    const first = indexAt(Math.max(0, viewTop));
    const last = indexAt(Math.max(0, viewBottom));
    lo = Math.max(0, first - BUFFER);
    hi = Math.min(heights.length - 1, last + BUFFER);
    timeline.requestRange(rowAt(lo), rowAt(hi));
    notifyView(timeline.years[rowAt(first)]);
  }

  // The view-change notification escapes the current flush: updateWindow
  // can run synchronously inside an effect (scrollToPosition from the
  // boot effect), and the parent's handler reads/writes reactive state -
  // calling it inline re-enters the flush (effect_update_depth_exceeded).
  let notifyQueued = false;
  let notifyYear = null;
  function notifyView(year) {
    notifyYear = year;
    if (notifyQueued) {
      return;
    }
    notifyQueued = true;
    queueMicrotask(() => {
      notifyQueued = false;
      onviewchange?.(notifyYear);
    });
  }

  let ticking = false;
  function onScroll() {
    if (ticking) {
      return;
    }
    ticking = true;
    requestAnimationFrame(() => {
      ticking = false;
      updateWindow();
    });
  }

  $effect(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    return () => {
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('resize', onScroll);
    };
  });

  const observer = new ResizeObserver((entries) => {
    let changed = false;
    for (const entry of entries) {
      const pos = entry.target.__registerPos;
      if (pos == null || pos >= heights.length) {
        continue;
      }
      const h = entry.borderBoxSize?.[0]?.blockSize ?? entry.contentRect.height;
      if (h > 0 && Math.abs(heights[pos] - h) > 1) {
        heights[pos] = h;
        changed = true;
      }
    }
    if (changed) {
      scheduleRebuild();
    }
  });

  function measure(node, pos) {
    node.__registerPos = pos;
    observer.observe(node);
    return {
      update(newPos) {
        node.__registerPos = newPos;
      },
      destroy() {
        observer.unobserve(node);
      },
    };
  }

  // A jump target stays "pending" while nearby buckets hydrate: heights
  // above the target keep growing, so a single scrollTo computed from
  // estimate offsets lands short (observed: 'Apartheid 1948' landing in
  // 1685). Each offset rebuild re-applies the target until heights
  // settle, a timeout passes, or the user takes over.
  let pendingTarget = null;
  let pendingUntil = 0;

  function applyTarget() {
    if (pendingTarget === null || !containerEl) {
      return;
    }
    const rect = containerEl.getBoundingClientRect();
    const target = window.scrollY + rect.top + offsets[pendingTarget] - 140;
    // ALWAYS instant: Bootstrap reboot sets :root{scroll-behavior:smooth},
    // and a smooth scroll restarted by every rebuild turns a jump into an
    // endless crawl through history that fetches every bucket it passes.
    window.scrollTo({ top: Math.max(0, target), behavior: 'instant' });
  }

  function cancelTarget() {
    pendingTarget = null;
  }

  $effect(() => {
    const options = { passive: true };
    window.addEventListener('wheel', cancelTarget, options);
    window.addEventListener('touchstart', cancelTarget, options);
    window.addEventListener('pointerdown', cancelTarget, options);
    return () => {
      window.removeEventListener('wheel', cancelTarget);
      window.removeEventListener('touchstart', cancelTarget);
      window.removeEventListener('pointerdown', cancelTarget);
    };
  });

  /** Scrolls the register so the given display position tops the view. */
  export function scrollToPosition(pos) {
    if (!containerEl || pos < 0 || pos >= heights.length) {
      return;
    }
    pendingTarget = pos;
    pendingUntil = performance.now() + 2500;
    applyTarget();
    updateWindow();
  }

  // Rendered slice - recomputed when the window or hydration state
  // moves. cardVersion is read so bucket arrivals re-render rows.
  const rows = $derived.by(() => {
    void timeline.cardVersion;
    void layoutVersion;
    const out = [];
    // Clamp against the CURRENT row set: when a filter shrinks it, this
    // derived re-runs (rowAt reads `visible`) before the rAF resets
    // lo/hi - unclamped positions would map to undefined rows and crash
    // the keyed each with duplicate keys.
    const last = Math.min(hi, rowCount - 1, offsets.length - 2);
    for (let pos = Math.min(lo, last < 0 ? 0 : last); pos <= last; pos += 1) {
      const index = rowAt(pos);
      out.push({
        pos,
        index,
        nid: timeline.nids[index],
        top: offsets[pos],
        prevYear: pos > 0 ? timeline.years[rowAt(pos - 1)] : null,
      });
    }
    return out;
  });
</script>

<div class="tl-register" bind:this={containerEl} style="height: {totalHeight}px" role="feed" aria-label="Historical events, oldest first" aria-busy={!timeline.ready}>
  {#each rows as row (row.nid)}
    <div class="tl-register__slot" style="transform: translateY({row.top}px)" use:measure={row.pos}>
      <EventRow index={row.index} prevYear={row.prevYear} {onopen} />
    </div>
  {/each}
</div>

<style>
  .tl-register {
    position: relative;
    contain: layout style;
  }

  .tl-register__slot {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    will-change: transform;
  }
</style>
