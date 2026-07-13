<script>
  import { timeline } from '../state/timeline.svelte.js';

  /**
   * Instant title search over the skeleton - debounced, client-side,
   * with the result count announced for screen readers. (Full-text
   * Solr search is a later phase; this covers titles today.)
   */

  let { onsearch = null } = $props();

  let value = $state(timeline.query);
  let timer = null;

  export function setValue(text) {
    value = text;
  }

  function onInput() {
    clearTimeout(timer);
    timer = setTimeout(() => {
      timeline.setQuery(value);
      onsearch?.(value);
    }, 180);
  }

  function clear() {
    clearTimeout(timer);
    value = '';
    timeline.setQuery('');
    onsearch?.('');
  }

  const matchTotal = $derived(timeline.visible ? timeline.visible.length : null);
</script>

<div class="tl-search">
  <input
    type="search"
    placeholder="Search the record by title"
    aria-label="Search events by title"
    bind:value
    oninput={onInput}
  />
  {#if matchTotal !== null}
    <span class="tl-search__count" role="status">
      {matchTotal} {matchTotal === 1 ? 'match' : 'matches'}
    </span>
    <button type="button" class="tl-search__clear" onclick={clear} aria-label="Clear search">&times;</button>
  {/if}
</div>

<style>
  .tl-search {
    display: flex;
    align-items: center;
    gap: var(--space-2, 0.75rem);
  }

  input {
    flex: 1 1 auto;
    min-width: 0;
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 13px;
    padding: 8px 10px;
    background: var(--saho-paper-raised, #f1efe7);
    color: var(--text-primary, #1b1c17);
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    border-radius: 0;
  }

  input:focus-visible {
    outline: 2px solid var(--saho-oxblood, #990000);
    outline-offset: -1px;
  }

  .tl-search__count {
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 11px;
    letter-spacing: 0.05em;
    color: var(--text-muted, #5d5e52);
    white-space: nowrap;
  }

  .tl-search__clear {
    font-size: 16px;
    line-height: 1;
    padding: 4px 8px;
    background: transparent;
    color: var(--text-muted, #5d5e52);
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    border-radius: 0;
    cursor: pointer;
  }

  .tl-search__clear:hover {
    color: var(--saho-oxblood, #990000);
  }
</style>
