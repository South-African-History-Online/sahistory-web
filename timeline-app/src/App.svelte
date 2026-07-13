<script>
  import { timeline } from './state/timeline.svelte.js';
  import Register from './components/Register.svelte';

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
    <Register />
  </div>
{/if}
