<script>
  import { getEvent } from '../lib/api.js';
  import { timeline, formatDate } from '../state/timeline.svelte.js';

  /**
   * The record panel: a native <dialog> (focus trap, Esc, inert
   * background for free) styled as a bottom sheet on small screens and
   * a right-hand file panel on wide ones. Fetches the single-event
   * resolver for the full card + related people/organisations/topics.
   */

  let { nid = null, onclose = null, onnavigate = null } = $props();

  let dialogEl = $state(null);
  let detail = $state(null);
  let failed = $state(false);
  let copied = $state(false);

  $effect(() => {
    if (!dialogEl) {
      return;
    }
    if (nid) {
      detail = null;
      failed = false;
      copied = false;
      if (!dialogEl.open) {
        dialogEl.showModal();
        document.documentElement.style.overflow = 'hidden';
      }
      getEvent(nid)
        .then((data) => {
          detail = data.event;
        })
        .catch(() => {
          failed = true;
        });
    }
    else if (dialogEl.open) {
      dialogEl.close();
    }
  });

  function released() {
    document.documentElement.style.overflow = '';
    onclose?.();
  }

  function backdropClick(event) {
    if (event.target === dialogEl) {
      dialogEl.close();
    }
  }

  const index = $derived(nid ? timeline.indexOfNid(nid) : -1);
  const dateLabel = $derived(index >= 0
    ? formatDate(timeline.dateInts[index], timeline.precisionCodes[index])
    : (detail?.date ?? ''));

  const RELATED_LABELS = { people: 'People', organizations: 'Organisations', topics: 'Topics' };

  function neighborNid(delta) {
    if (index < 0) {
      return null;
    }
    if (timeline.visible) {
      const pos = timeline.visible.indexOf(index);
      const next = pos >= 0 ? timeline.visible[pos + delta] : undefined;
      return next === undefined ? null : timeline.nids[next];
    }
    return timeline.nids[index + delta] ?? null;
  }

  const prevNid = $derived.by(() => (nid ? neighborNid(-1) : null));
  const nextNid = $derived.by(() => (nid ? neighborNid(1) : null));

  async function share() {
    const url = window.location.href;
    const title = detail?.title ?? 'SAHO timeline';
    if (navigator.share) {
      try {
        await navigator.share({ title, url });
        return;
      }
      catch {
        // Fall through to clipboard on dismissal/unsupported targets.
      }
    }
    try {
      await navigator.clipboard.writeText(url);
      copied = true;
      setTimeout(() => {
        copied = false;
      }, 2000);
    }
    catch {
      // Clipboard unavailable - the URL bar still has the deep link.
    }
  }
</script>

<dialog
  bind:this={dialogEl}
  class="tl-panel"
  aria-label="Event record"
  onclose={released}
  onclick={backdropClick}
>
  {#if nid}
    <div class="tl-panel__inner">
      <header class="tl-panel__strip">
        <span class="tl-panel__tab">event</span>
        <span class="tl-panel__date">{dateLabel}</span>
        <button type="button" class="tl-panel__close" onclick={() => dialogEl.close()} aria-label="Close">&times;</button>
      </header>

      {#if failed}
        <p class="tl-panel__meta">This record could not be loaded.
          <a href={`/node/${nid}`}>Open its page instead</a>.</p>
      {:else if !detail}
        <p class="tl-panel__meta" aria-live="polite">Retrieving record&hellip;</p>
      {:else}
        {#if detail.image}
          <img class="tl-panel__image" src={detail.image} alt="" width="640" height="480" />
        {/if}
        <h2 class="tl-panel__title">{detail.title}</h2>
        {#if detail.body}
          <p class="tl-panel__body">{detail.body}</p>
        {/if}

        <a class="tl-panel__cta" href={detail.url}>Read the full record &rarr;</a>

        {#each Object.entries(detail.related ?? {}) as [group, items] (group)}
          {#if items.length}
            <div class="tl-panel__related">
              <h3>{RELATED_LABELS[group] ?? group}</h3>
              <ul>
                {#each items as item (item.nid)}
                  <li><a href={item.url}>{item.title}</a></li>
                {/each}
              </ul>
            </div>
          {/if}
        {/each}

        {#if detail.ref}
          <div class="tl-panel__sources">
            <h3>Sources</h3>
            <p>{detail.ref}</p>
          </div>
        {/if}
      {/if}

      <footer class="tl-panel__footer">
        <button type="button" disabled={prevNid === null} onclick={() => onnavigate?.(prevNid)}>&larr; Previous</button>
        <button type="button" onclick={share}>{copied ? 'Link copied' : 'Share'}</button>
        <button type="button" disabled={nextNid === null} onclick={() => onnavigate?.(nextNid)}>Next &rarr;</button>
      </footer>
    </div>
  {/if}
</dialog>

<style>
  .tl-panel {
    padding: 0;
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    background: var(--saho-paper-raised, #f1efe7);
    color: var(--text-primary, #1b1c17);
    width: min(100%, 560px);
    max-height: 85dvh;

    /* Bottom sheet by default (mobile-first). */
    margin: auto 0 0;
    max-width: 100%;
  }

  .tl-panel::backdrop {
    background: rgb(27 28 23 / 45%);
  }

  @media (min-width: 62rem) {
    .tl-panel {
      /* File-pull panel on wide screens. */
      margin: 0 0 0 auto;
      height: 100dvh;
      max-height: 100dvh;
      width: 440px;
    }
  }

  .tl-panel__inner {
    padding: var(--space-3, 1rem);
    overflow-y: auto;
    max-height: inherit;
    box-sizing: border-box;
  }

  .tl-panel__strip {
    display: flex;
    align-items: center;
    gap: var(--space-2, 0.75rem);
    border-bottom: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    padding-bottom: var(--space-2, 0.75rem);
    margin-bottom: var(--space-3, 1rem);
  }

  .tl-panel__tab {
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 10px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    background: var(--saho-oxblood, #990000);
    color: var(--saho-paper, #e7e4d8);
    padding: 3px 8px;
  }

  .tl-panel__date {
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 12px;
    letter-spacing: 0.06em;
    color: var(--text-muted, #5d5e52);
  }

  .tl-panel__close {
    margin-left: auto;
    font-size: 20px;
    line-height: 1;
    padding: 2px 8px;
    background: transparent;
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    border-radius: 0;
    color: var(--text-primary, #1b1c17);
    cursor: pointer;
  }

  .tl-panel__image {
    display: block;
    width: 100%;
    height: auto;
    background: var(--saho-paper-sunk, #dad6c7);
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    margin-bottom: var(--space-3, 1rem);
  }

  .tl-panel__title {
    font-family: var(--font-serif-display, 'Libre Caslon Display', georgia, serif);
    font-size: var(--font-size-xl, 1.375rem);
    line-height: 1.25;
    margin: 0 0 var(--space-2, 0.75rem);
  }

  .tl-panel__body {
    font-family: var(--font-serif, 'Libre Caslon Text', georgia, serif);
    line-height: 1.6;
    margin: 0 0 var(--space-3, 1rem);
  }

  .tl-panel__meta {
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 12px;
    color: var(--text-muted, #5d5e52);
  }

  .tl-panel__cta {
    display: inline-block;
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    background: var(--saho-oxblood, #990000);

    /* !important: the theme's global oxblood link color otherwise wins
       and paints oxblood-on-oxblood. */
    color: var(--saho-paper, #e7e4d8) !important;
    text-decoration: none;
    padding: 10px 14px;
    margin-bottom: var(--space-3, 1rem);
  }

  .tl-panel__related h3,
  .tl-panel__sources h3 {
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--text-muted, #5d5e52);
    margin: var(--space-3, 1rem) 0 var(--space-1, 0.5rem);
  }

  .tl-panel__related ul {
    list-style: none;
    margin: 0;
    padding: 0;
    border-top: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
  }

  .tl-panel__related li {
    border-bottom: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    padding: 6px 0;
    font-family: var(--font-serif, 'Libre Caslon Text', georgia, serif);
    font-size: var(--font-size-sm, 0.9375rem);
  }

  .tl-panel__related a {
    color: var(--text-primary, #1b1c17);
    text-decoration: none;
    border-bottom: var(--bw-hair, 1px) solid currentcolor;
  }

  .tl-panel__related a:hover {
    color: var(--saho-oxblood, #990000);
  }

  .tl-panel__sources p {
    font-family: var(--font-serif, 'Libre Caslon Text', georgia, serif);
    font-size: 13px;
    line-height: 1.5;
    color: var(--text-secondary, #3a3b33);
  }

  .tl-panel__footer {
    display: flex;
    justify-content: space-between;
    gap: var(--space-2, 0.75rem);
    border-top: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    padding-top: var(--space-2, 0.75rem);
    margin-top: var(--space-3, 1rem);
  }

  .tl-panel__footer button {
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 11px;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 8px 10px;
    background: var(--saho-paper-raised, #f1efe7);
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    border-radius: 0;
    color: var(--text-primary, #1b1c17);
    cursor: pointer;
  }

  .tl-panel__footer button:disabled {
    opacity: 0.4;
    cursor: default;
  }

  .tl-panel__footer button:not(:disabled):hover {
    color: var(--saho-oxblood, #990000);
    border-color: var(--saho-oxblood, #990000);
  }
</style>
