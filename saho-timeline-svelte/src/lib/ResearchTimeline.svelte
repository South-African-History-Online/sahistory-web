<script>
  import { onMount, createEventDispatcher } from 'svelte';
  import VirtualList from 'svelte-virtual-list';
  import { format } from 'date-fns';
  import EventCard from './EventCard.svelte';
  import TimelineCanvas from './TimelineCanvas.svelte';
  import ResearchTools from './ResearchTools.svelte';
  import Analytics from './Analytics.svelte';
  import Icon from './Icon.svelte';
  
  // SAHO site base URL for links
  const SAHO_BASE_URL = 'https://sahistory-web.ddev.site';
  
  export let events = [];
  export let minYear = 1300;
  export let maxYear = 2024;
  
  const dispatch = createEventDispatcher();
  
  let selectedEvent = null;
  let viewMode = 'research'; // 'research', 'timeline', 'analytical'
  let searchQuery = '';
  let filteredEvents = events;
  let selectedThemes = [];
  let selectedPeriod = null;
  let selectedEventTypes = [];
  let selectedYear = null;
  let showCitations = false;
  let analyticalMode = 'density'; // 'density', 'themes', 'geographical'
  
  // Virtual list configuration
  let itemHeight = 140; // Taller for research content
  let viewport;
  
  // Advanced filtering
  $: filteredEvents = applyAdvancedFilters(events, {
    searchQuery,
    selectedThemes,
    selectedPeriod,
    selectedEventTypes,
    selectedYear
  });
  
  // Group events by year for navigation and analysis
  $: eventsByYear = filteredEvents.reduce((acc, event) => {
    const year = new Date(event.date).getFullYear();
    if (!acc[year]) acc[year] = [];
    acc[year].push(event);
    return acc;
  }, {});
  
  $: years = Object.keys(eventsByYear).map(Number).sort((a, b) => a - b);
  
  // Dynamic year range based on filtered events
  $: filteredMinYear = years.length > 0 ? Math.min(...years) : minYear;
  $: filteredMaxYear = years.length > 0 ? Math.max(...years) : maxYear;
  $: yearSpan = filteredMaxYear - filteredMinYear;
  
  // Historical periods for research
  $: historicalPeriods = [
    { name: 'Pre-Colonial (1300-1650)', start: 1300, end: 1650, color: '#8B4513' },
    { name: 'Colonial Period (1650-1900)', start: 1650, end: 1900, color: '#2F4F4F' },
    { name: 'Union & Segregation (1900-1948)', start: 1900, end: 1948, color: '#CD853F' },
    { name: 'Apartheid Era (1948-1994)', start: 1948, end: 1994, color: '#B22222' },
    { name: 'Democratic Era (1994-present)', start: 1994, end: maxYear, color: '#228B22' }
  ];
  
  // Extract themes for research filtering
  $: availableThemes = [...new Set(events.flatMap(e => e.themes || []))].sort();
  $: eventTypes = [...new Set(events.map(e => e.type))].filter(Boolean).sort();
  
  function applyAdvancedFilters(events, filters) {
    return events.filter(event => {
      // Text search
      if (filters.searchQuery) {
        const query = filters.searchQuery.toLowerCase();
        const searchFields = [
          event.title,
          event.body,
          event.location,
          ...(event.themes || [])
        ].filter(Boolean);
        
        if (!searchFields.some(field => 
          field.toLowerCase().includes(query)
        )) {
          return false;
        }
      }
      
      // Theme filtering
      if (filters.selectedThemes.length > 0) {
        if (!event.themes || !filters.selectedThemes.some(theme => 
          event.themes.includes(theme)
        )) {
          return false;
        }
      }
      
      // Period filtering
      if (filters.selectedPeriod) {
        const eventYear = new Date(event.date).getFullYear();
        if (eventYear < filters.selectedPeriod.start || 
            eventYear > filters.selectedPeriod.end) {
          return false;
        }
      }
      
      // Event type filtering
      if (filters.selectedEventTypes.length > 0) {
        if (!filters.selectedEventTypes.includes(event.type)) {
          return false;
        }
      }
      
      // Year filtering
      if (filters.selectedYear !== null) {
        const eventYear = new Date(event.date).getFullYear();
        if (eventYear !== filters.selectedYear) {
          return false;
        }
      }
      
      return true;
    });
  }
  
  function handleEventClick(event) {
    selectedEvent = event;
    dispatch('select', event);
  }
  
  function filterByYear(year) {
    // Toggle year filter - if same year clicked, clear filter
    if (selectedYear === year) {
      selectedYear = null;
      selectedPeriod = null; // Clear period filter when clearing year
    } else {
      selectedYear = year;
      selectedPeriod = null; // Clear period filter when selecting specific year
    }
  }
  
  function handleKeydown(e) {
    if (e.key === 'Escape') {
      selectedEvent = null;
    }
  }
  
  function generateCitation(event) {
    const year = new Date(event.date).getFullYear();
    const eventUrl = generateEventUrl(event);
    return `"${event.title}" (${year}). South African History Online. Retrieved ${format(new Date(), 'MMMM d, yyyy')} from ${eventUrl}`;
  }
  
  function generateEventUrl(event) {
    // Generate proper SAHO URL based on event type and ID
    if (event.url && event.url.startsWith('http')) {
      return event.url; // Use existing full URL
    }
    
    // Construct URL based on event type and ID
    const baseUrl = SAHO_BASE_URL;
    if (event.nid) {
      return `${baseUrl}/node/${event.nid}`;
    }
    
    // Fallback to general SAHO site
    return baseUrl;
  }
  
  function exportResults() {
    const exportData = filteredEvents.map(event => ({
      title: event.title,
      date: event.date,
      year: new Date(event.date).getFullYear(),
      themes: event.themes?.join('; ') || '',
      location: event.location || '',
      type: event.type || '',
      url: event.url || '',
      citation: generateCitation(event)
    }));
    
    const csv = [
      'Title,Date,Year,Themes,Location,Type,URL,Citation',
      ...exportData.map(row => Object.values(row).map(val => `"${val}"`).join(','))
    ].join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `saho-research-${format(new Date(), 'yyyy-MM-dd')}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }
</script>

<svelte:window on:keydown={handleKeydown} />

<div class="research-timeline">
  <!-- Modern Header -->
  <header class="timeline-header">
    <div class="brand-section">
      <h1>South African History Online</h1>
      <p class="subtitle">Digital Research Archive & Timeline</p>
      <div class="stats">
        <span class="stat">{filteredEvents.length.toLocaleString()} events</span>
        <span class="separator">•</span>
        <span class="stat">{filteredMinYear}–{filteredMaxYear}</span>
        <span class="separator">•</span>
        <span class="stat">{yearSpan} years covered</span>
      </div>
    </div>
    
    <div class="controls-section">
      <div class="search-container">
        <label for="timeline-search" class="sr-only">Search timeline events</label>
        <input 
          id="timeline-search"
          type="search" 
          placeholder="Search events, people, places, themes..." 
          bind:value={searchQuery}
          class="search-input"
          aria-label="Search timeline events, people, places, and themes"
        />
        <button 
          class="export-btn" 
          on:click={exportResults}
          aria-label="Export filtered research data as CSV file"
        >
          <Icon name="export" size="16" color="currentColor" />
          Export Research Data
        </button>
      </div>
      
      <div class="view-modes" role="tablist" aria-label="Timeline view modes">
        <button 
          class:active={viewMode === 'research'}
          on:click={() => viewMode = 'research'}
          role="tab"
          aria-selected={viewMode === 'research'}
          aria-controls="research-panel"
          id="research-tab"
        >
          <Icon name="research" size="16" color="currentColor" />
          Research View
        </button>
        <button 
          class:active={viewMode === 'timeline'}
          on:click={() => viewMode = 'timeline'}
          role="tab"
          aria-selected={viewMode === 'timeline'}
          aria-controls="timeline-panel"
          id="timeline-tab"
        >
          <Icon name="timeline" size="16" color="currentColor" />
          Timeline Visualization
        </button>
        <button 
          class:active={viewMode === 'analytical'}
          on:click={() => viewMode = 'analytical'}
          role="tab"
          aria-selected={viewMode === 'analytical'}
          aria-controls="analytics-panel"
          id="analytics-tab"
        >
          <Icon name="analytics" size="16" color="currentColor" />
          Data Analytics
        </button>
      </div>
    </div>
  </header>
  
  <!-- Research Tools Sidebar -->
  <div class="main-content">
    <aside class="research-sidebar">
      <ResearchTools 
        {historicalPeriods}
        {availableThemes}
        {eventTypes}
        bind:selectedPeriod
        bind:selectedThemes
        bind:selectedEventTypes
        bind:selectedYear
        bind:showCitations
        eventsCount={filteredEvents.length}
      />
    </aside>
    
    <!-- Main Timeline Content -->
    <main class="timeline-content">
      {#if viewMode === 'research'}
        <!-- Historical Period Navigation -->
        <div class="period-nav">
          <div class="period-buttons">
            <button 
              class="period-btn all"
              class:active={!selectedPeriod}
              on:click={() => selectedPeriod = null}
            >
              All Periods
            </button>
            {#each historicalPeriods as period}
              <button 
                class="period-btn"
                class:active={selectedPeriod === period}
                style="border-left: 4px solid {period.color}"
                on:click={() => selectedPeriod = period}
              >
                {period.name}
                <span class="count">
                  ({events.filter(e => {
                    const year = new Date(e.date).getFullYear();
                    return year >= period.start && year <= period.end;
                  }).length})
                </span>
              </button>
            {/each}
          </div>
        </div>
        
        <!-- Year Navigation -->
        <div class="year-nav">
          <div class="year-scroll">
            {#each years as year}
              {@const yearCount = eventsByYear[year].length}
              <button 
                class="year-button"
                class:dense={yearCount > 10}
                class:active={selectedYear === year}
                on:click={() => filterByYear(year)}
              >
                {year}
                <span class="count">({yearCount})</span>
              </button>
            {/each}
          </div>
        </div>
        
        <!-- Event List with Virtual Scrolling -->
        <div class="event-list" bind:this={viewport}>
          <VirtualList 
            items={filteredEvents}
            let:item
            {itemHeight}
          >
            <EventCard 
              event={item}
              on:click={() => handleEventClick(item)}
              selected={selectedEvent?.id === item.id}
              researchMode={true}
              {showCitations}
            />
          </VirtualList>
        </div>
        
      {:else if viewMode === 'timeline'}
        <!-- Timeline Visualization -->
        <TimelineCanvas 
          events={filteredEvents}
          {minYear}
          {maxYear}
          {historicalPeriods}
          on:select={(e) => handleEventClick(e.detail)}
        />
        
      {:else if viewMode === 'analytical'}
        <!-- Data Analytics -->
        <Analytics 
          {events}
          {filteredEvents}
          {historicalPeriods}
          {analyticalMode}
          on:analyticalModeChange={(e) => analyticalMode = e.detail}
        />
      {/if}
    </main>
  </div>
  
  <!-- Enhanced Event Detail Modal -->
  {#if selectedEvent}
    <div 
      class="modal-overlay" 
      on:click={() => selectedEvent = null}
      on:keydown={(e) => e.key === 'Escape' && (selectedEvent = null)}
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
      tabindex="-1"
    >
      <article class="event-modal">
        <header class="modal-header">
          <button 
            class="close" 
            on:click={() => selectedEvent = null}
            aria-label="Close event details"
          >×</button>
          <div class="event-meta">
            <span class="event-year">{new Date(selectedEvent.date).getFullYear()}</span>
            <span class="event-date">{format(new Date(selectedEvent.date), 'MMMM d, yyyy')}</span>
          </div>
        </header>
        
        <div class="modal-content">
          {#if selectedEvent.image}
            <div class="event-image">
              <img src={selectedEvent.image} alt={selectedEvent.title} />
            </div>
          {/if}
          
          <h2 id="modal-title" class="event-title">{selectedEvent.title}</h2>
          
          {#if selectedEvent.location}
            <p class="location"><Icon name="location" size="16" color="#666" /> {selectedEvent.location}</p>
          {/if}
          
          <div class="event-body">{@html selectedEvent.body}</div>
          
          {#if selectedEvent.themes && selectedEvent.themes.length > 0}
            <div class="themes">
              <h4>Related Themes:</h4>
              <div class="theme-tags">
                {#each selectedEvent.themes as theme}
                  <span class="theme-tag">{theme}</span>
                {/each}
              </div>
            </div>
          {/if}
          
          {#if showCitations}
            <div class="citation">
              <h4>Academic Citation:</h4>
              <p class="citation-text">{generateCitation(selectedEvent)}</p>
              <button on:click={() => navigator.clipboard.writeText(generateCitation(selectedEvent))}>
                <Icon name="copy" size="16" color="white" /> Copy Citation
              </button>
            </div>
          {/if}
          
          <footer class="modal-footer">
            <a href={generateEventUrl(selectedEvent)} target="_blank" rel="noopener" class="read-more">
              <Icon name="link" size="16" color="#97212d" /> Read Full Article →
            </a>
          </footer>
        </div>
      </article>
    </div>
  {/if}
</div>

<style>
  .research-timeline {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Source Sans Pro', sans-serif;
  }
  
  .sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }
  
  .timeline-header {
    background: linear-gradient(135deg, #97212d 0%, #7a1b24 100%);
    color: white;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
  }
  
  .brand-section h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
    letter-spacing: -0.5px;
  }
  
  .subtitle {
    margin: 0.5rem 0 1rem;
    font-size: 1.1rem;
    opacity: 0.9;
    font-weight: 300;
  }
  
  .stats {
    display: flex;
    gap: 1rem;
    align-items: center;
    font-size: 0.95rem;
    opacity: 0.8;
  }
  
  .stat {
    font-weight: 500;
  }
  
  .separator {
    opacity: 0.6;
  }
  
  .controls-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-end;
  }
  
  .search-container {
    display: flex;
    gap: 1rem;
    align-items: center;
  }
  
  .search-input {
    padding: 0.75rem 1.25rem;
    border: none;
    border-radius: 25px;
    font-size: 1rem;
    width: 350px;
    background: white;
    color: #333;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  
  .search-input::placeholder {
    color: #666;
    opacity: 1;
  }
  
  .export-btn {
    padding: 0.75rem 1.5rem;
    background: rgba(255,255,255,0.9);
    border: 2px solid white;
    color: #97212d;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    backdrop-filter: blur(10px);
  }
  
  .export-btn:hover {
    background: white;
    color: #7a1b24;
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  }
  
  .export-btn:focus {
    outline: 3px solid #ffd700;
    outline-offset: 2px;
  }
  
  .view-modes {
    display: flex;
    gap: 0.5rem;
    background: rgba(255,255,255,0.15);
    padding: 0.5rem;
    border-radius: 25px;
    border: 1px solid rgba(255,255,255,0.2);
  }
  
  .view-modes button {
    padding: 0.8rem 1.5rem;
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white;
    cursor: pointer;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .view-modes button:hover {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  }
  
  .view-modes button:focus {
    outline: 3px solid #ffd700;
    outline-offset: 2px;
  }
  
  .view-modes button.active {
    background: white;
    color: #97212d;
    border-color: white;
    text-shadow: none;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  }
  
  .view-modes button.active:hover {
    background: #f8f9fa;
    color: #7a1b24;
  }
  
  .main-content {
    flex: 1;
    display: flex;
    overflow: hidden;
  }
  
  .research-sidebar {
    width: 320px;
    background: white;
    border-right: 1px solid #e9ecef;
    overflow-y: auto;
  }
  
  .timeline-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  
  .period-nav {
    background: white;
    padding: 1rem 2rem;
    border-bottom: 1px solid #e9ecef;
  }
  
  .period-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  .period-btn {
    padding: 1rem 1.5rem;
    border: 2px solid #dee2e6;
    background: white;
    cursor: pointer;
    border-radius: 20px;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #495057;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    min-width: 140px;
  }
  
  .period-btn:hover {
    background: #f8f9fa;
    border-color: #97212d;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    color: #97212d;
  }
  
  .period-btn:focus {
    outline: 3px solid #ffd700;
    outline-offset: 2px;
  }
  
  .period-btn.active {
    background: #97212d;
    color: white;
    border-color: #97212d;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(151, 33, 45, 0.3);
  }
  
  .period-btn.all.active {
    background: #343a40;
    border-color: #343a40;
    box-shadow: 0 4px 15px rgba(52, 58, 64, 0.3);
  }
  
  .period-btn.active:hover {
    background: #7a1b24;
    border-color: #7a1b24;
  }
  
  .period-btn.all.active:hover {
    background: #23272b;
    border-color: #23272b;
  }
  
  .count {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 0.25rem;
  }
  
  .year-nav {
    background: #f8f9fa;
    padding: 1rem 2rem;
    border-bottom: 1px solid #e9ecef;
    overflow-x: auto;
  }
  
  .year-scroll {
    display: flex;
    gap: 0.5rem;
    min-width: min-content;
  }
  
  .year-button {
    padding: 0.4rem 0.8rem;
    border: 1px solid #ddd;
    background: white;
    color: #333;
    cursor: pointer;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .year-button:hover {
    background: #e9ecef;
    color: #97212d;
    border-color: #97212d;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  }
  
  .year-button.dense {
    background: #e3f2fd;
    border-color: #90caf9;
    color: #1565c0;
    font-weight: 600;
  }
  
  .year-button.active {
    background: #97212d;
    color: white;
    border-color: #97212d;
    font-weight: 600;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(151, 33, 45, 0.3);
  }
  
  .year-button.active:hover {
    background: #7a1b24;
    border-color: #7a1b24;
  }
  
  .event-list {
    flex: 1;
    padding: 1rem 2rem;
    overflow-y: auto;
  }
  
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 2rem;
  }
  
  .event-modal {
    background: white;
    border-radius: 12px;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 12px 12px 0 0;
  }
  
  .close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .close:hover {
    background: #e9ecef;
  }
  
  .event-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
  }
  
  .event-year {
    font-size: 2rem;
    font-weight: bold;
    color: #97212d;
  }
  
  .event-date {
    font-size: 1.1rem;
    color: #666;
  }
  
  .modal-content {
    padding: 2rem;
  }
  
  .event-image {
    margin-bottom: 1.5rem;
  }
  
  .event-image img {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 8px;
  }
  
  .event-title {
    color: #97212d;
    margin: 0 0 1rem;
    font-size: 1.8rem;
    line-height: 1.3;
  }
  
  .location {
    color: #666;
    margin-bottom: 1.5rem;
    font-style: italic;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .event-body {
    line-height: 1.7;
    color: #333;
    margin-bottom: 2rem;
    font-size: 1.05rem;
  }
  
  .themes {
    margin-bottom: 2rem;
  }
  
  .themes h4 {
    margin: 0 0 0.5rem;
    color: #495057;
  }
  
  .theme-tags {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  .theme-tag {
    background: #e9ecef;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.85rem;
    color: #495057;
  }
  
  .citation {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
  }
  
  .citation h4 {
    margin: 0 0 0.75rem;
    color: #495057;
  }
  
  .citation-text {
    font-family: 'Georgia', serif;
    font-style: italic;
    color: #495057;
    line-height: 1.6;
    margin-bottom: 1rem;
  }
  
  .citation button {
    background: #97212d;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 1.5rem 2rem;
    margin: 0 -2rem -2rem;
    background: #f8f9fa;
    border-radius: 0 0 12px 12px;
  }
  
  .read-more {
    color: #97212d;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .read-more:hover {
    text-decoration: underline;
  }
  
  /* Tablet styles */
  @media (max-width: 1024px) {
    .timeline-header {
      flex-direction: column;
      align-items: stretch;
      padding: 1.5rem;
    }
    
    .brand-section h1 {
      font-size: 2rem;
    }
    
    .controls-section {
      align-items: stretch;
    }
    
    .search-container {
      flex-direction: column;
      gap: 1rem;
    }
    
    .view-modes {
      justify-content: center;
    }
    
    .view-modes button {
      padding: 0.6rem 1rem;
      font-size: 0.85rem;
    }
    
    .main-content {
      flex-direction: column;
    }
    
    .research-sidebar {
      width: 100%;
      max-height: 250px;
      order: 2;
    }
    
    .timeline-content {
      order: 1;
    }
    
    .search-input {
      width: 100%;
    }
    
    .period-buttons {
      justify-content: center;
    }
    
    .period-btn {
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      min-width: 120px;
    }
  }
  
  /* Mobile phone styles */
  @media (max-width: 768px) {
    .research-timeline {
      font-size: 14px;
    }
    
    .timeline-header {
      padding: 1rem;
    }
    
    .brand-section h1 {
      font-size: 1.8rem;
      text-align: center;
    }
    
    .subtitle {
      text-align: center;
      font-size: 1rem;
    }
    
    .stats {
      flex-direction: column;
      gap: 0.5rem;
      text-align: center;
      font-size: 0.9rem;
    }
    
    .separator {
      display: none;
    }
    
    .search-input {
      font-size: 16px; /* Prevents zoom on iOS */
      padding: 0.875rem 1rem;
    }
    
    .export-btn {
      padding: 0.875rem 1rem;
      font-size: 0.85rem;
    }
    
    .view-modes {
      flex-direction: column;
      gap: 0.75rem;
      padding: 0.75rem;
    }
    
    .view-modes button {
      padding: 0.875rem;
      justify-content: center;
      width: 100%;
    }
    
    .research-sidebar {
      max-height: 300px;
      border-right: none;
      border-bottom: 1px solid #e9ecef;
    }
    
    .period-nav {
      padding: 1rem;
    }
    
    .period-buttons {
      flex-direction: column;
      gap: 0.75rem;
    }
    
    .period-btn {
      width: 100%;
      text-align: center;
      padding: 1rem;
      min-width: auto;
    }
    
    .year-nav {
      padding: 0.75rem 1rem;
    }
    
    .year-button {
      padding: 0.5rem 0.75rem;
      font-size: 0.8rem;
    }
    
    .event-list {
      padding: 1rem;
    }
    
    /* Modal improvements for mobile */
    .modal-overlay {
      padding: 1rem;
    }
    
    .event-modal {
      max-width: 100%;
      max-height: 85vh;
      margin: 0;
    }
    
    .modal-header {
      padding: 1rem;
      flex-direction: column;
      align-items: stretch;
      gap: 1rem;
    }
    
    .close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      z-index: 10;
    }
    
    .event-meta {
      flex-direction: column;
      gap: 0.5rem;
      align-items: center;
      text-align: center;
    }
    
    .event-year {
      font-size: 1.5rem;
    }
    
    .modal-content {
      padding: 1rem;
    }
    
    .event-title {
      font-size: 1.5rem;
      text-align: center;
    }
    
    .citation {
      padding: 1rem;
    }
    
    .modal-footer {
      padding: 1rem;
      text-align: center;
    }
    
    .read-more {
      justify-content: center;
      padding: 0.75rem;
      font-size: 1rem;
    }
  }
  
  /* Small mobile phones */
  @media (max-width: 480px) {
    .timeline-header {
      padding: 0.75rem;
    }
    
    .brand-section h1 {
      font-size: 1.6rem;
    }
    
    .view-modes button {
      padding: 0.75rem;
      font-size: 0.8rem;
    }
    
    .period-btn {
      padding: 0.875rem;
      font-size: 0.9rem;
    }
    
    .year-button {
      padding: 0.4rem 0.6rem;
      font-size: 0.75rem;
    }
    
    .modal-content {
      padding: 0.75rem;
    }
    
    .event-title {
      font-size: 1.3rem;
    }
  }
</style>