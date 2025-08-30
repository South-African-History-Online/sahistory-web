<script>
  import { onMount } from 'svelte';
  import ResearchTimeline from './lib/ResearchTimeline.svelte';
  import { fetchTimelineEvents, processEvents } from './lib/api.js';
  import Icon from './lib/Icon.svelte';
  
  let events = [];
  let datelessEvents = [];
  let processedData = null;
  let loading = true;
  let error = null;
  
  onMount(async () => {
    try {
      console.log('Loading SAHO Research Timeline...');
      const apiData = await fetchTimelineEvents(5000);
      
      if (apiData && apiData.events && apiData.events.length > 0) {
        processedData = processEvents(apiData.events);
        events = processedData.all;
        datelessEvents = apiData.datelessEvents || apiData.dateless_events || [];
        console.log(`✅ Loaded ${events.length} events with dates spanning ${processedData.minYear}-${processedData.maxYear}`);
        console.log(`📋 Found ${datelessEvents.length} dateless events for review`);
        console.log('🔬 Research Timeline ready for scholarly exploration');
      } else if (apiData && apiData.events) {
        // Handle case where events array exists but is empty
        events = [];
        datelessEvents = apiData.datelessEvents || apiData.dateless_events || [];
        processedData = { all: [], byYear: {}, minYear: 1300, maxYear: 2024, totalEvents: 0 };
        console.log('⚠️ No events with dates found, but API responded');
      } else {
        throw new Error('No events received from API');
      }
    } catch (err) {
      console.error('Failed to load timeline events:', err);
      error = err.message;
    } finally {
      loading = false;
    }
  });
  
  function handleEventSelect(event) {
    console.log('Selected event for research:', event.detail);
  }
</script>

<main>
  {#if loading}
    <div class="loading">
      <div class="loading-content">
        <div class="loading-logo">
          <img src="/saho-timeline/saho_logo_white_transparent.svg" alt="SAHO" class="loading-saho-logo" />
        </div>
        <div class="loading-spinner"></div>
        <h2>Loading SAHO Research Timeline</h2>
        <p>Preparing 3,500+ historical events for scholarly exploration</p>
        <div class="loading-features">
          <div class="feature"><Icon name="book" size="16" color="white" /> Academic Research Tools</div>
          <div class="feature"><Icon name="analytics" size="16" color="white" /> Data Analytics</div>
          <div class="feature"><Icon name="filter" size="16" color="white" /> Advanced Filtering</div>
          <div class="feature"><Icon name="article" size="16" color="white" /> Citation Generator</div>
        </div>
      </div>
    </div>
  {:else if error}
    <div class="error">
      <h2>Error Loading Research Timeline</h2>
      <p>{error}</p>
      <p>Please check that the Drupal API is accessible at:</p>
      <code>{import.meta.env.VITE_API_URL || 'http://localhost:5173'}/api/timeline/events</code>
      <div class="error-actions">
        <button on:click={() => window.location.reload()}><Icon name="refresh" size="16" color="white" /> Retry</button>
        <a href="https://sahistory-web.ddev.site" target="_blank"><Icon name="globe" size="16" color="#97212d" /> Check DDEV Site</a>
      </div>
    </div>
  {:else}
    <ResearchTimeline 
      {events}
      {datelessEvents}
      minYear={processedData.minYear}
      maxYear={processedData.maxYear}
      on:select={handleEventSelect}
    />
  {/if}
</main>

<style>
  main {
    height: 100vh;
    height: 100dvh; /* Dynamic viewport height for mobile */
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 
                 'Source Sans Pro', sans-serif;
    overflow: hidden;
  }
  
  .loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    background: linear-gradient(135deg, #97212d 0%, #7a1b24 100%);
    color: white;
    text-align: center;
    padding: 2rem;
  }
  
  .loading-content {
    max-width: 500px;
  }
  
  .loading-logo {
    margin-bottom: 2rem;
    animation: logoFloat 3s ease-in-out infinite;
  }
  
  .loading-saho-logo {
    width: 120px;
    height: 120px;
    object-fit: contain;
    /* SVG is already white, adding subtle glow effect */
    filter: drop-shadow(0 4px 12px rgba(255, 255, 255, 0.3));
  }
  
  @keyframes logoFloat {
    0%, 100% {
      transform: translateY(0px);
    }
    50% {
      transform: translateY(-10px);
    }
  }
  
  .loading-content h2 {
    margin: 1rem 0 0.5rem;
    font-size: 2.2rem;
    font-weight: 700;
    letter-spacing: -0.5px;
  }
  
  .loading-content p {
    margin: 0 0 2rem;
    font-size: 1.1rem;
    opacity: 0.9;
    font-weight: 300;
  }
  
  .loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 2rem;
  }
  
  .loading-features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 2rem;
  }
  
  .feature {
    background: rgba(255,255,255,0.1);
    padding: 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  
  .error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    text-align: center;
    padding: 2rem;
    background: #f8f9fa;
    color: #d32f2f;
  }
  
  .error h2 {
    margin-bottom: 1rem;
    color: #97212d;
  }
  
  .error code {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    font-family: 'Monaco', 'Menlo', monospace;
    margin: 1rem 0;
    display: block;
    border: 1px solid #e9ecef;
    color: #495057;
  }
  
  .error-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
  }
  
  .error-actions button,
  .error-actions a {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .error-actions button {
    background: #97212d;
    color: white;
  }
  
  .error-actions button:hover {
    background: #7a1b24;
  }
  
  .error-actions a {
    background: white;
    color: #97212d;
    border: 1px solid #97212d;
  }
  
  .error-actions a:hover {
    background: #97212d;
    color: white;
  }
  
  @media (max-width: 768px) {
    .loading-features {
      grid-template-columns: 1fr;
    }
    
    .loading-content h2 {
      font-size: 1.8rem;
    }
    
    .loading-content p {
      font-size: 1rem;
    }
    
    .error-actions {
      flex-direction: column;
      width: 100%;
    }
    
    .error-actions button,
    .error-actions a {
      width: 100%;
    }
  }
</style>