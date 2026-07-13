<script>
  import { timeline } from '../state/timeline.svelte.js';

  /** Curated era chips - one horizontally scrollable row of entry points. */
  let { currentYear = null, onjump = null } = $props();

  const eras = timeline.settings?.eras ?? [];

  function isActive(era) {
    if (currentYear === null) {
      return false;
    }
    const start = era.start ?? -10000;
    const end = era.end ?? 10000;
    return currentYear >= start && currentYear < end;
  }
</script>

<nav class="tl-eras" aria-label="Eras">
  {#each eras as era (era.id)}
    <button
      type="button"
      class="tl-eras__chip"
      class:is-active={isActive(era)}
      onclick={() => onjump?.(era)}
    >
      {era.label}
    </button>
  {/each}
</nav>

<style>
  .tl-eras {
    display: flex;
    gap: var(--space-1, 0.5rem);
    overflow-x: auto;
    scrollbar-width: none;
    padding-block: var(--space-2, 0.75rem);
  }

  .tl-eras::-webkit-scrollbar {
    display: none;
  }

  .tl-eras__chip {
    flex: 0 0 auto;
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 11px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 6px 10px;
    background: var(--saho-paper-raised, #f1efe7);
    color: var(--text-primary, #1b1c17);
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    border-radius: 0;
    cursor: pointer;
  }

  .tl-eras__chip:hover {
    color: var(--saho-oxblood, #990000);
    border-color: var(--saho-oxblood, #990000);
  }

  .tl-eras__chip.is-active {
    background: var(--saho-oxblood, #990000);
    color: var(--saho-paper, #e7e4d8);
    border-color: var(--saho-oxblood, #990000);
  }
</style>
