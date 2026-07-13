<script>
  import { timeline } from '../state/timeline.svelte.js';

  /**
   * The Ruler: an inline-SVG decade histogram of the whole archive.
   *
   * It shows the honest shape of the corpus (the 20th-century skew is
   * itself a story), doubles as fast travel (click / drag / keyboard,
   * role="slider"), and overlays search-match density when a query is
   * active. Built from ~54 decade counts shipped in drupalSettings, so
   * it renders before any fetch completes.
   */

  let { currentYear = null, onjump = null } = $props();

  const counts = timeline.settings?.decadeCounts ?? {};
  const START = 1500;
  const END = Math.max(2030, ...Object.keys(counts).filter((k) => k !== 'pre1500').map(Number).filter(Number.isFinite));

  // Bars: pre1500 first, then every decade to END. sqrt scale keeps the
  // sparse early centuries visible next to the 373-event 1960s.
  const bars = (() => {
    const tokens = ['pre1500'];
    for (let d = START; d < END; d += 10) {
      tokens.push(String(d));
    }
    const max = Math.max(1, ...Object.values(counts));
    return tokens.map((token, i) => ({
      token,
      index: i,
      count: counts[token] ?? 0,
      h: counts[token] ? Math.max(2, Math.sqrt(counts[token] / max) * 100) : 0,
      year: token === 'pre1500' ? 1300 : Number(token),
    }));
  })();
  const BAR_W = 100 / bars.length;
  const maxMatch = $derived(timeline.matchCounts ? Math.max(1, ...Object.values(timeline.matchCounts)) : 1);

  const minYear = $derived(timeline.range[0] || 1300);
  const maxYear = $derived(timeline.range[1] || END);

  function barPositionForYear(year) {
    if (year < START) {
      return BAR_W / 2;
    }
    const i = 1 + Math.min(bars.length - 2, Math.floor((year - START) / 10));
    return i * BAR_W + BAR_W / 2;
  }

  function yearFromClientX(el, clientX) {
    const rect = el.getBoundingClientRect();
    const frac = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width));
    const pos = Math.min(bars.length - 1, Math.floor(frac * bars.length));
    const bar = bars[pos];
    return bar.token === 'pre1500' ? minYear : bar.year;
  }

  let svgEl = $state(null);
  let dragging = $state(false);
  let dragYear = $state(null);

  function jump(year) {
    onjump?.(Math.min(maxYear, Math.max(minYear, year)));
  }

  function onPointerDown(event) {
    dragging = true;
    svgEl.setPointerCapture(event.pointerId);
    dragYear = yearFromClientX(svgEl, event.clientX);
  }

  function onPointerMove(event) {
    if (!dragging) {
      return;
    }
    dragYear = yearFromClientX(svgEl, event.clientX);
  }

  function onPointerUp() {
    if (!dragging) {
      return;
    }
    dragging = false;
    if (dragYear !== null) {
      jump(dragYear);
      dragYear = null;
    }
  }

  function onKeydown(event) {
    const base = currentYear ?? minYear;
    const steps = {
      ArrowLeft: -1,
      ArrowRight: 1,
      ArrowDown: -1,
      ArrowUp: 1,
      PageDown: -10,
      PageUp: 10,
    };
    if (event.key in steps) {
      event.preventDefault();
      jump(base + steps[event.key] * (event.shiftKey ? 10 : 1));
    }
    else if (event.key === 'Home') {
      event.preventDefault();
      jump(minYear);
    }
    else if (event.key === 'End') {
      event.preventDefault();
      jump(maxYear);
    }
  }

  const needleYear = $derived(dragYear ?? currentYear);
</script>

<div class="tl-ruler">
  <svg
    bind:this={svgEl}
    viewBox="0 0 100 34"
    preserveAspectRatio="none"
    role="slider"
    tabindex="0"
    aria-label="Jump to year"
    aria-valuemin={minYear}
    aria-valuemax={maxYear}
    aria-valuenow={needleYear ?? minYear}
    aria-valuetext={needleYear ? `Year ${needleYear}` : 'Timeline start'}
    onpointerdown={onPointerDown}
    onpointermove={onPointerMove}
    onpointerup={onPointerUp}
    onpointercancel={onPointerUp}
    onkeydown={onKeydown}
  >
    {#each bars as bar (bar.token)}
      <rect
        class="tl-ruler__bar"
        x={bar.index * BAR_W + 0.08}
        y={30 - (bar.h * 0.3)}
        width={BAR_W - 0.16}
        height={bar.h * 0.3 || 0.001}
      />
      {#if timeline.matchCounts}
        {@const m = timeline.matchCounts[bar.token] ?? 0}
        {#if m > 0}
          <rect
            class="tl-ruler__match"
            x={bar.index * BAR_W + 0.08}
            y={30 - Math.max(0.8, Math.sqrt(m / maxMatch) * 30)}
            width={BAR_W - 0.16}
            height={Math.max(0.8, Math.sqrt(m / maxMatch) * 30)}
          />
        {/if}
      {/if}
    {/each}
    {#if needleYear !== null}
      <line
        class="tl-ruler__needle"
        x1={barPositionForYear(needleYear)}
        x2={barPositionForYear(needleYear)}
        y1="0"
        y2="30"
      />
    {/if}
    <line class="tl-ruler__base" x1="0" x2="100" y1="30" y2="30" />
  </svg>
  <div class="tl-ruler__labels" aria-hidden="true">
    <span>&lt;1500</span>
    <span>1700</span>
    <span>1900</span>
    <span>{maxYear}</span>
  </div>
  {#if needleYear !== null}
    <span class="tl-ruler__year" aria-hidden="true">{needleYear}</span>
  {/if}
</div>

<style>
  .tl-ruler {
    position: relative;
    padding-top: 14px;
  }

  svg {
    display: block;
    width: 100%;
    height: 44px;
    cursor: crosshair;
    touch-action: none;
  }

  svg:focus-visible {
    outline: 2px solid var(--saho-oxblood, #990000);
    outline-offset: 2px;
  }

  .tl-ruler__bar {
    fill: var(--saho-ink, #1b1c17);
    opacity: 0.35;
  }

  .tl-ruler__match {
    fill: var(--saho-oxblood, #990000);
  }

  .tl-ruler__needle {
    stroke: var(--saho-oxblood, #990000);
    stroke-width: 0.4;
    vector-effect: non-scaling-stroke;
  }

  .tl-ruler__base {
    stroke: var(--border-default, #bdb9a6);
    stroke-width: 1;
    vector-effect: non-scaling-stroke;
  }

  .tl-ruler__labels {
    display: flex;
    justify-content: space-between;
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 10px;
    letter-spacing: 0.05em;
    color: var(--text-muted, #5d5e52);
    margin-top: 2px;
  }

  .tl-ruler__year {
    position: absolute;
    top: -2px;
    right: 0;
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 12px;
    font-weight: 600;
    color: var(--saho-oxblood, #990000);
  }
</style>
