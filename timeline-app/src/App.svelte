<script>
  import { timeline } from './state/timeline.svelte.js';

  // The server-rendered era shell stays visible until the skeleton has
  // arrived and the app has something better to show.
  $effect(() => {
    if (timeline.ready) {
      document.querySelector('[data-timeline-ssr]')?.remove();
    }
  });
</script>

{#if timeline.ready}
  <div class="tl-app" data-timeline-live>
    <p class="tl-app__boot">
      Register loaded: {timeline.count} events, {timeline.range[0]} - {timeline.range[1]}.
    </p>
  </div>
{/if}
