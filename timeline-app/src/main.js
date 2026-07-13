import { mount } from 'svelte';
import App from './App.svelte';
import { timeline } from './state/timeline.svelte.js';

// The Drupal library loads this deferred; the mount target and
// drupalSettings.sahoTimeline are both rendered server-side by
// TimelineController. No settings, no app - the server shell stays.
function boot() {
  const target = document.getElementById('saho-timeline-app');
  const settings = window.drupalSettings?.sahoTimeline;
  if (!target || !settings) {
    return;
  }
  timeline.init(settings);
  mount(App, { target });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
}
else {
  boot();
}
