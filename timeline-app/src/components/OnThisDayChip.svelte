<script>
  import { timeline } from '../state/timeline.svelte.js';

  /**
   * "On this day" - the archive's daily hook, computed entirely from the
   * skeleton (month/day live in the yyyymmdd ints). Toggles a filtered
   * register of every year's events sharing today's date.
   */

  let { ontoggle = null } = $props();

  const now = new Date();
  const month = now.getMonth() + 1;
  const day = now.getDate();
  const label = now.toLocaleDateString('en-ZA', { day: 'numeric', month: 'long' }).toUpperCase();

  const total = timeline.countForDay(month, day);
  const active = $derived(!!timeline.dayFilter);

  function toggle() {
    if (active) {
      timeline.setDayFilter(null);
      ontoggle?.(false);
    }
    else {
      timeline.setDayFilter(month, day);
      ontoggle?.(true);
    }
  }
</script>

{#if total > 0}
  <button type="button" class="tl-otd" class:is-active={active} onclick={toggle}>
    <span class="tl-otd__date">{label}</span>
    <span class="tl-otd__count">
      {active ? 'showing' : ''} {total} {total === 1 ? 'event' : 'events'} on this day
      {active ? '- show all' : '→'}
    </span>
  </button>
{/if}

<style>
  .tl-otd {
    display: inline-flex;
    align-items: baseline;
    gap: var(--space-2, 0.75rem);
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 11px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 8px 12px;
    margin-block: var(--space-2, 0.75rem);
    background: var(--saho-paper-raised, #f1efe7);
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    border-left: 3px solid var(--saho-oxblood, #990000);
    border-radius: 0;
    color: var(--text-primary, #1b1c17);
    cursor: pointer;
  }

  .tl-otd:hover {
    color: var(--saho-oxblood, #990000);
  }

  .tl-otd.is-active {
    background: var(--saho-oxblood, #990000);
    color: var(--saho-paper, #e7e4d8);
    border-color: var(--saho-oxblood, #990000);
  }

  .tl-otd__date {
    font-weight: 600;
  }
</style>
