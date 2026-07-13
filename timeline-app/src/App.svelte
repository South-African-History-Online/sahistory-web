<script>
  import { timeline } from './state/timeline.svelte.js';
  import { readUrlState, replaceUrlState, pushUrlState, onUrlChange } from './lib/url.js';
  import Register from './components/Register.svelte';
  import DensityRuler from './components/DensityRuler.svelte';
  import EraRail from './components/EraRail.svelte';
  import SearchBox from './components/SearchBox.svelte';

  let register = $state(null);
  let searchBox = $state(null);
  let currentYear = $state(null);
  let currentEraId = $state(null);
  // Deep-linked event, opened by the detail panel once it exists.
  let pendingEvent = $state(null);
  let booted = false;

  // The server-rendered era shell stays visible until the skeleton has
  // arrived and the app has something better to show.
  $effect(() => {
    if (timeline.ready) {
      document.querySelector('[data-timeline-ssr]')?.remove();
    }
  });

  function jumpToYear(year) {
    if (!register || !timeline.ready) {
      return;
    }
    let position = timeline.firstIndexForYear(year);
    if (timeline.visible) {
      // Filtered view: first match at/after the year.
      position = 0;
      while (position < timeline.visible.length - 1 && timeline.years[timeline.visible[position]] < year) {
        position += 1;
      }
    }
    register.scrollToPosition(position);
    currentYear = year;
  }

  function onRulerJump(year) {
    jumpToYear(year);
    replaceUrlState({ year, q: timeline.query, era: currentEraId });
  }

  function onEraJump(era) {
    currentEraId = era.id;
    jumpToYear(era.start ?? timeline.range[0]);
    pushUrlState({ era: era.id, q: timeline.query });
  }

  function onViewChange(year) {
    currentYear = year;
    if (booted && window.scrollY > 100) {
      replaceUrlState({ year, q: timeline.query, era: currentEraId });
    }
  }

  function onSearch(text) {
    pushUrlState({ q: text || null, year: currentYear });
  }

  function applyUrlState(state) {
    if (state.q) {
      timeline.setQuery(state.q);
      searchBox?.setValue(state.q);
    }
    else if (timeline.query) {
      timeline.setQuery('');
      searchBox?.setValue('');
    }
    if (state.era) {
      const era = (timeline.settings?.eras ?? []).find((e) => e.id === state.era);
      if (era) {
        currentEraId = era.id;
        jumpToYear(era.start ?? timeline.range[0]);
      }
    }
    if (state.year) {
      jumpToYear(state.year);
    }
    if (state.event) {
      pendingEvent = state.event;
    }
  }

  // Boot: apply the entry URL once the register exists, then listen for
  // back/forward.
  $effect(() => {
    if (!timeline.ready || !register || booted) {
      return;
    }
    applyUrlState(readUrlState());
    // URL writes stay quiet until the entry state has been applied.
    setTimeout(() => {
      booted = true;
    }, 1200);
    return onUrlChange((state) => applyUrlState(state));
  });
</script>

{#if timeline.error}
  <div class="tl-app" data-timeline-live>
    <p class="tl-app__error">
      The interactive timeline could not load its data. The era links
      below still lead to individual records.
    </p>
  </div>
{:else if timeline.ready}
  <div class="tl-app" data-timeline-live>
    <EraRail {currentYear} onjump={onEraJump} />
    <div class="tl-controls">
      <SearchBox bind:this={searchBox} onsearch={onSearch} />
      <DensityRuler {currentYear} onjump={onRulerJump} />
    </div>
    {#if timeline.visible && timeline.visible.length === 0}
      <p class="tl-app__empty" role="status">
        No titles match &ldquo;{timeline.query}&rdquo;. Try a shorter
        term, or search the whole site from the header.
      </p>
    {:else}
      <Register
        bind:this={register}
        visible={timeline.visible}
        onviewchange={onViewChange}
      />
    {/if}
  </div>
{/if}

<style>
  .tl-controls {
    position: sticky;
    top: 0;
    z-index: 30;
    display: grid;
    gap: 4px;
    padding: var(--space-2, 0.75rem) 0 var(--space-1, 0.5rem);
    background: var(--saho-paper, #e7e4d8);
    border-bottom: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    margin-bottom: var(--space-2, 0.75rem);
  }

  .tl-app__error,
  .tl-app__empty {
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 13px;
    color: var(--text-muted, #5d5e52);
    padding: var(--space-4, 1.5rem) 0;
  }
</style>
