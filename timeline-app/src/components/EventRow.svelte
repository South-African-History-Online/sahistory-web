<script>
  import { timeline, formatDate } from '../state/timeline.svelte.js';

  /**
   * One register row in the saho-chronology visual language: mono year
   * gutter, square marker on a hairline spine, entry to the right.
   *
   * Compact tier renders purely from skeleton columns; when the row's
   * decade bucket has arrived it hydrates in place with excerpt and
   * thumbnail. Parent reads cardVersion, so hydration re-renders us.
   */

  let { index, prevYear = null, onopen = null } = $props();

  const nid = $derived(timeline.nids[index]);
  const year = $derived(timeline.years[index]);
  const showYear = $derived(prevYear === null || prevYear !== year);
  const dateLabel = $derived(formatDate(timeline.dateInts[index], timeline.precisionCodes[index]));
  // cardVersion is the hydration signal: the cards Map itself is not
  // reactive, so read the counter to re-derive when buckets arrive.
  const card = $derived.by(() => {
    void timeline.cardVersion;
    return timeline.card(nid);
  });

  // Era chapter banner above the first row of each era - rendered
  // inside the row slot so the virtualizer's ResizeObserver measures it
  // for free.
  const era = $derived(timeline.eraStartAt(index));
  const eraSpan = $derived.by(() => {
    if (!era) {
      return '';
    }
    if (era.start === null) {
      return `before ${era.end}`;
    }
    return era.end === null ? `${era.start} onward` : `${era.start} - ${era.end}`;
  });

  function open(event) {
    if (onopen) {
      event.preventDefault();
      onopen(nid);
    }
  }
</script>

{#if era}
  <div class="tl-era-banner">
    <span class="tl-era-banner__label">{era.label}</span>
    <span class="tl-era-banner__span">{eraSpan}</span>
    {#if era.blurb}
      <p class="tl-era-banner__blurb">{era.blurb}</p>
    {/if}
  </div>
{/if}

<article class="tl-row" class:tl-row--hydrated={!!card}>
  <span class="tl-row__year">{showYear ? year : ''}</span>
  <span class="tl-row__marker" aria-hidden="true"></span>
  <div class="tl-row__body">
    <span class="tl-row__date">{dateLabel}</span>
    <h3 class="tl-row__title">
      <a href={card ? card.url : `/node/${nid}`} onclick={open}>
        {card ? card.title : timeline.titles[index]}
      </a>
    </h3>
    {#if card}
      {#if card.body}
        <p class="tl-row__excerpt">{card.body}</p>
      {/if}
      {#if card.thumb}
        <div class="tl-row__media">
          <img src={card.thumb} alt="" loading="lazy" decoding="async" width="120" height="90" />
        </div>
      {/if}
    {/if}
  </div>
</article>

<style>
  .tl-era-banner {
    margin: var(--space-4, 1.5rem) 0 var(--space-2, 0.75rem);
    padding: var(--space-3, 1rem);
    background: var(--saho-paper-raised, #f1efe7);
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
    border-left: 3px solid var(--saho-ochre, #b88a2e);
  }

  .tl-era-banner__label {
    display: block;
    font-family: var(--font-serif-display, 'Libre Caslon Display', georgia, serif);
    font-size: var(--font-size-xl, 1.375rem);
    color: var(--text-primary, #1b1c17);
  }

  .tl-era-banner__span {
    display: block;
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--text-muted, #5d5e52);
    margin-top: 2px;
  }

  .tl-era-banner__blurb {
    font-family: var(--font-serif, 'Libre Caslon Text', georgia, serif);
    font-size: var(--font-size-sm, 0.9375rem);
    line-height: 1.55;
    color: var(--text-secondary, #3a3b33);
    margin: var(--space-1, 0.5rem) 0 0;
    max-width: var(--measure, 65ch);
  }

  .tl-row {
    position: relative;
    display: grid;
    grid-template-columns: 56px 16px minmax(0, 1fr);
    column-gap: var(--space-3, 1rem);
    padding-block: var(--space-2, 0.75rem);

    /* The theme cards <article> globally; the register is a ledger,
       not a stack of cards. */
    background: transparent;
    border: 0;
    box-shadow: none;
    margin: 0;
  }

  /* The spine. */
  .tl-row::before {
    content: '';
    position: absolute;
    left: calc(56px + var(--space-3, 1rem) + 7px);
    top: 0;
    bottom: 0;
    width: var(--bw-hair, 1px);
    background: var(--border-default, #bdb9a6);
  }

  .tl-row__year {
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 13px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--saho-oxblood, #990000);
    text-align: right;
    padding-top: 3px;
  }

  .tl-row__marker {
    position: relative;
    z-index: 1;
    width: 8px;
    height: 8px;
    margin-top: 7px;
    justify-self: center;
    background: var(--saho-ink, #1b1c17);
  }

  .tl-row__body {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    grid-template-areas: 'date media' 'title media' 'excerpt media';
    column-gap: var(--space-3, 1rem);
    padding-right: var(--space-2, 0.75rem);
  }

  .tl-row__date {
    grid-area: date;
    font-family: var(--font-mono, 'IBM Plex Mono', monospace);
    font-size: 11px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--text-muted, #5d5e52);
  }

  .tl-row__title {
    grid-area: title;
    font-family: var(--font-serif, 'Libre Caslon Text', georgia, serif);
    font-size: var(--font-size-md, 1.0625rem);
    font-weight: 600;
    line-height: 1.35;
    margin: 2px 0 0;
  }

  .tl-row__title a {
    color: var(--text-primary, #1b1c17);
    text-decoration: none;
  }

  .tl-row__title a:hover {
    color: var(--saho-oxblood, #990000);
  }

  .tl-row__excerpt {
    grid-area: excerpt;
    font-family: var(--font-serif, 'Libre Caslon Text', georgia, serif);
    font-size: var(--font-size-sm, 0.9375rem);
    line-height: 1.55;
    color: var(--text-secondary, #3a3b33);
    margin: 4px 0 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .tl-row__media {
    grid-area: media;
    align-self: start;
  }

  .tl-row__media img {
    display: block;
    width: 120px;
    height: 90px;
    object-fit: cover;
    background: var(--saho-paper-sunk, #dad6c7);
    border: var(--bw-hair, 1px) solid var(--border-default, #bdb9a6);
  }

  @media (max-width: 40rem) {
    .tl-row {
      grid-template-columns: 44px 12px minmax(0, 1fr);
      column-gap: var(--space-2, 0.75rem);
    }

    .tl-row::before {
      left: calc(44px + var(--space-2, 0.75rem) + 5px);
    }

    .tl-row__media img {
      width: 72px;
      height: 54px;
    }
  }
</style>
