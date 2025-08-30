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
  export let datelessEvents = [];
  export let minYear = 1300;
  export let maxYear = 2024;
  
  const dispatch = createEventDispatcher();
  
  let selectedEvent = null;
  let viewMode = 'research'; // 'research', 'timeline', 'analytical', 'dateless_review'
  let searchQuery = '';
  let filteredEvents = events;
  let selectedThemes = [];
  let selectedPeriod = null;
  let selectedEventTypes = [];
  let selectedYear = null;
  let showCitations = true;
  let analyticalMode = 'density'; // 'density', 'themes', 'geographical'
  let mobileMenuOpen = false;
  let showMobileSearch = false;
  let showMobileFilters = false;
  let isMobile = window.innerWidth <= 768;
  let bookmarkedEvents = [];
  let copySuccess = false;
  
  // Virtual list configuration - responsive item height
  let itemHeight = window.innerWidth <= 768 ? 180 : 140; // Taller on mobile for better touch targets
  let viewport;
  
  // Update item height on window resize
  function updateItemHeight() {
    itemHeight = window.innerWidth <= 768 ? 180 : 140;
  }
  
  onMount(() => {
    window.addEventListener('resize', updateItemHeight);
    window.addEventListener('resize', checkMobile);
    return () => {
      window.removeEventListener('resize', updateItemHeight);
      window.removeEventListener('resize', checkMobile);
    };
  });
  
  function checkMobile() {
    isMobile = window.innerWidth <= 768;
    if (!isMobile) {
      mobileMenuOpen = false;
      showMobileSearch = false;
      showMobileFilters = false;
    }
  }
  
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
    // Use field_ref_str if available, otherwise generate generic citation
    if (event.field_ref_str && event.field_ref_str.trim()) {
      return event.field_ref_str.trim();
    }
    
    // Fallback to generic citation
    const year = new Date(event.date).getFullYear();
    const eventUrl = generateEventUrl(event);
    return `"${event.title}" (${year}). South African History Online. Retrieved ${format(new Date(), 'MMMM d, yyyy')} from ${eventUrl}`;
  }
  
  function parseCitations(citationString) {
    if (!citationString || !citationString.trim()) {
      return [];
    }
    
    // Split by pipe symbol and clean up each citation
    return citationString.split('|')
      .map(citation => citation.trim())
      .filter(citation => citation.length > 0);
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

  async function copyCitation(event, citation) {
    event.stopPropagation();
    event.preventDefault();
    
    try {
      await navigator.clipboard.writeText(citation);
      copySuccess = true;
      setTimeout(() => {
        copySuccess = false;
      }, 2000);
    } catch (err) {
      console.error('Failed to copy citation:', err);
      // Fallback for older browsers
      const textArea = document.createElement('textarea');
      textArea.value = citation;
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      try {
        document.execCommand('copy');
        copySuccess = true;
        setTimeout(() => {
          copySuccess = false;
        }, 2000);
      } catch (fallbackErr) {
        console.error('Fallback copy failed:', fallbackErr);
      }
      document.body.removeChild(textArea);
    }
  }
  
</script>

<svelte:window on:keydown={handleKeydown} />

<div class="research-timeline" class:mobile={isMobile}>
  <!-- Desktop Header -->
  {#if !isMobile}
    <header class="desktop-header">
      <div class="header-top">
        <div class="brand-section">
          <img src="/saho-timeline/saho_logo_white_transparent.svg" alt="SAHO" class="desktop-logo" />
          <div class="brand-info">
            <h1>South African History Online</h1>
            <p>Digital Research Archive & Timeline</p>
          </div>
        </div>
        
        <div class="stats-section">
          <div class="stat-item">
            <span class="stat-value">{filteredEvents.length}</span>
            <span class="stat-label">events</span>
          </div>
          <div class="stat-divider">•</div>
          <div class="stat-item">
            <span class="stat-value">{years[0] || minYear}–{years[years.length - 1] || maxYear}</span>
          </div>
          <div class="stat-divider">•</div>
          <div class="stat-item">
            <span class="stat-value">{years.length}</span>
            <span class="stat-label">years covered</span>
          </div>
          {#if datelessEvents.length > 0}
            <div class="stat-divider">•</div>
            <div class="stat-item alert">
              <span class="stat-value">{datelessEvents.length}</span>
              <span class="stat-label">need review</span>
            </div>
          {/if}
        </div>
      </div>
      
      <div class="header-controls">
        <div class="search-section">
          <input 
            type="text" 
            placeholder="Search events, people, places, themes..."
            bind:value={searchQuery}
            class="search-input"
          />
        </div>
        
        <div class="view-tabs">
          <button 
            class:active={viewMode === 'research'}
            on:click={() => viewMode = 'research'}
          >
            <Icon name="research" size="16" color="currentColor" />
            Research View
          </button>
          <button 
            class:active={viewMode === 'timeline'}
            on:click={() => viewMode = 'timeline'}
          >
            <Icon name="timeline" size="16" color="currentColor" />
            Timeline Visualisation
          </button>
          <button 
            class:active={viewMode === 'analytical'}
            on:click={() => viewMode = 'analytical'}
          >
            <Icon name="analytics" size="16" color="currentColor" />
            Data Analytics
          </button>
          {#if datelessEvents.length > 0}
            <button 
              class="dateless-review-tab"
              class:active={viewMode === 'dateless_review'}
              on:click={() => viewMode = 'dateless_review'}
            >
              <Icon name="alert" size="16" color="currentColor" />
              Dateless Review ({datelessEvents.length})
            </button>
          {/if}
        </div>
      </div>
    </header>
  {/if}

  <!-- Mobile Header -->
  {#if isMobile}
    <header class="mobile-header">
      <div class="mobile-top-bar">
        <button 
          class="menu-toggle"
          on:click={() => mobileMenuOpen = !mobileMenuOpen}
          aria-label="Toggle menu"
        >
          <span class="hamburger" class:open={mobileMenuOpen}>
            <span></span>
            <span></span>
            <span></span>
          </span>
        </button>
        
        <div class="mobile-brand">
          <div class="logo-title-wrapper">
            <img src="/saho-timeline/saho_logo_white_transparent.svg" alt="SAHO" class="saho-logo" />
            <h1>SAHO Timeline</h1>
          </div>
          <p class="mobile-stats">
            {filteredEvents.length} events
            {#if selectedPeriod || selectedThemes.length > 0}
              <span class="filtered-indicator">• filtered</span>
            {/if}
          </p>
        </div>
        
        <button 
          class="search-toggle"
          on:click={() => showMobileSearch = !showMobileSearch}
          aria-label="Toggle search"
        >
          <Icon name="search" size="20" color="white" />
        </button>
      </div>
      
      {#if showMobileSearch}
        <div class="mobile-search-bar">
          <input 
            type="search" 
            placeholder="Search events..." 
            bind:value={searchQuery}
            class="mobile-search-input"
          />
          <button 
            class="filter-toggle"
            on:click={() => { showMobileSearch = false; mobileMenuOpen = true; }}
          >
            <Icon name="filter" size="16" color="white" />
            Menu
          </button>
        </div>
      {/if}
      
      {#if mobileMenuOpen}
        <nav class="mobile-menu">
          <!-- Quick Filters Section -->
          <div class="menu-section">
            <h4 class="menu-section-title">Quick Filters</h4>
            <div class="quick-filters">
              {#each historicalPeriods as period}
                <button 
                  class="filter-chip period-chip"
                  class:active={selectedPeriod?.name === period.name}
                  style="border-left: 3px solid {period.color}"
                  on:click={() => { 
                    selectedPeriod = selectedPeriod?.name === period.name ? null : period; 
                    mobileMenuOpen = false; 
                  }}
                >
                  {period.name.split(' (')[0]}
                  <small>({period.start}-{period.end === maxYear ? 'present' : period.end})</small>
                </button>
              {/each}
              {#if selectedPeriod || selectedThemes.length > 0}
                <button 
                  class="filter-chip clear-filters"
                  on:click={() => { selectedPeriod = null; selectedThemes = []; mobileMenuOpen = false; }}
                >
                  Clear All Filters
                </button>
              {/if}
            </div>
          </div>
          
          <!-- Popular Themes Section -->
          <div class="menu-section">
            <h4 class="menu-section-title">Popular Themes</h4>
            <div class="theme-grid">
              {#each availableThemes.slice(0, 8) as theme}
                <button 
                  class="theme-chip"
                  class:active={selectedThemes.includes(theme)}
                  on:click={() => {
                    if (selectedThemes.includes(theme)) {
                      selectedThemes = selectedThemes.filter(t => t !== theme);
                    } else {
                      selectedThemes = [...selectedThemes, theme];
                    }
                  }}
                >
                  {theme}
                </button>
              {/each}
            </div>
          </div>
          
          <!-- Research Actions -->
          <div class="menu-section">
            <h4 class="menu-section-title">Research Tools</h4>
            <button 
              class="research-action"
              on:click={() => { 
                const randomEvent = filteredEvents[Math.floor(Math.random() * filteredEvents.length)];
                handleEventClick(randomEvent);
                mobileMenuOpen = false;
              }}
            >
              <Icon name="shuffle" size="16" color="currentColor" />
              Random Event
            </button>
            <button 
              class="research-action"
              on:click={() => { showCitations = !showCitations; mobileMenuOpen = false; }}
            >
              <Icon name="article" size="16" color="currentColor" />
              {showCitations ? 'Hide' : 'Show'} Citations
            </button>
            
            {#if datelessEvents.length > 0}
              <button 
                class="research-action admin-action"
                on:click={() => { 
                  viewMode = viewMode === 'dateless_review' ? 'research' : 'dateless_review';
                  mobileMenuOpen = false;
                }}
              >
                <Icon name="alert" size="16" color="currentColor" />
                {viewMode === 'dateless_review' ? 'Back to Timeline' : `Review Dateless (${datelessEvents.length})`}
              </button>
            {/if}
            
            <button 
              class="research-action"
              on:click={() => { showMobileFilters = true; mobileMenuOpen = false; }}
            >
              <Icon name="filter" size="16" color="currentColor" />
              Advanced Filters
            </button>
          </div>
          
          <!-- Results Info -->
          <div class="menu-section results-info">
            <div class="results-summary">
              <strong>{filteredEvents.length}</strong> events found
              {#if filteredMinYear !== minYear || filteredMaxYear !== maxYear}
                <br><small>Filtered: {filteredMinYear}–{filteredMaxYear}</small>
              {:else}
                <br><small>Full archive: {minYear}–{maxYear}</small>
              {/if}
            </div>
          </div>
        </nav>
      {/if}
    </header>
  {/if}
  
  <!-- Research Tools Sidebar -->
  <div class="main-content" class:mobile-content={isMobile}>
    {#if !isMobile || showMobileFilters}
      <aside class="research-sidebar" class:mobile-filters={isMobile && showMobileFilters}>
        {#if isMobile && showMobileFilters}
          <div class="mobile-filters-header">
            <h3>Filters</h3>
            <button 
              class="close-filters"
              on:click={() => showMobileFilters = false}
            >
              ×
            </button>
          </div>
        {/if}
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
    {/if}
    
    <!-- Main Timeline Content -->
    <main class="timeline-content">
      {#if viewMode === 'research'}
        <!-- Historical Period Navigation -->
        {#if !isMobile}
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
        {/if}
        
        <!-- Year Navigation -->
        {#if !isMobile}
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
        {/if}
        
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
        <!-- Timeline Visualisation -->
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
        
      {:else if viewMode === 'dateless_review'}
        <!-- Dateless Events Review -->
        <div class="dateless-review">
          <header class="review-header">
            <h3>Dateless Events Review</h3>
            <p class="review-description">
              {datelessEvents.length} events found without proper historical dates. 
              These events need date review before appearing in the timeline.
            </p>
            <button 
              class="back-btn"
              on:click={() => viewMode = 'research'}
            >
              ← Back to Timeline
            </button>
          </header>
          
          <div class="dateless-list">
            {#each datelessEvents as event (event.id)}
              <div class="dateless-event">
                <div class="event-header">
                  <h4>{event.title}</h4>
                  <span class="event-id">ID: {event.id}</span>
                </div>
                <p class="event-status">
                  <strong>Status:</strong> {event.status}
                </p>
                <p class="event-reason">
                  <strong>Reason:</strong> {event.reason}
                </p>
                <p class="event-created">
                  <strong>Created:</strong> {event.created}
                </p>
                <div class="event-actions">
                  <a 
                    href="{SAHO_BASE_URL}{event.edit_url}" 
                    target="_blank" 
                    class="edit-btn"
                  >
                    Edit in Drupal
                  </a>
                </div>
              </div>
            {/each}
          </div>
        </div>
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
              <h4>Academic Citation{parseCitations(generateCitation(selectedEvent)).length > 1 ? 's' : ''}:</h4>
              {#each parseCitations(generateCitation(selectedEvent)) as citation, index}
                <div class="citation-item">
                  <span class="citation-number">{index + 1}.</span>
                  <p class="citation-text">{citation}</p>
                </div>
              {/each}
              <button 
                on:click={(e) => copyCitation(e, generateCitation(selectedEvent))}
                class="copy-citation-btn"
                class:success={copySuccess}
              >
                {#if copySuccess}
                  <Icon name="check" size="16" color="white" /> Copied!
                {:else}
                  <Icon name="copy" size="16" color="white" /> Copy Citation
                {/if}
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
  
  .citation-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }
  
  .citation-item:last-child {
    margin-bottom: 1rem;
  }
  
  .citation-number {
    color: #97212d;
    font-weight: 600;
    font-size: 0.9rem;
    min-width: 1.5rem;
    flex-shrink: 0;
  }
  
  .citation-text {
    margin: 0;
    font-family: 'Georgia', serif;
    font-style: italic;
    color: #495057;
    line-height: 1.6;
    flex: 1;
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
    transition: all 0.3s ease;
  }

  .copy-citation-btn.success {
    background: #28a745;
    transform: scale(1.05);
  }

  .copy-citation-btn:hover {
    background: #7a1b24;
  }

  .copy-citation-btn.success:hover {
    background: #28a745;
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

  /* Mobile Header Styles */
  .mobile-header {
    background: linear-gradient(135deg, #97212d 0%, #7a1b24 100%);
    color: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 100;
  }
  
  .mobile-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    min-height: 60px;
  }
  
  .menu-toggle {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
  }
  
  .hamburger {
    display: flex;
    flex-direction: column;
    width: 20px;
    height: 16px;
    position: relative;
  }
  
  .hamburger span {
    background: white;
    height: 2px;
    margin: 2px 0;
    transition: all 0.3s ease;
    transform-origin: center;
  }
  
  .hamburger.open span:nth-child(1) {
    transform: rotate(45deg) translate(6px, 6px);
  }
  
  .hamburger.open span:nth-child(2) {
    opacity: 0;
  }
  
  .hamburger.open span:nth-child(3) {
    transform: rotate(-45deg) translate(6px, -6px);
  }
  
  .mobile-brand {
    flex: 1;
    text-align: center;
    margin: 0 1rem;
  }
  
  .logo-title-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
  }
  
  .saho-logo {
    width: 32px;
    height: 32px;
    object-fit: contain;
    /* SVG is already white, no filter needed */
  }
  
  .mobile-brand h1 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
  }
  
  .mobile-stats {
    margin: 0;
    font-size: 0.75rem;
    opacity: 0.8;
  }
  
  .filtered-indicator {
    color: #ffd700;
    font-weight: 600;
  }
  
  .search-toggle {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
  }
  
  .search-toggle:hover {
    background: rgba(255,255,255,0.1);
  }
  
  .mobile-search-bar {
    padding: 0 1rem 0.75rem;
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }
  
  .mobile-search-input {
    flex: 1;
    padding: 0.625rem 1rem;
    border: none;
    border-radius: 20px;
    font-size: 16px;
    background: white;
    color: #333;
  }
  
  .filter-toggle {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 15px;
    cursor: pointer;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
  }
  
  .mobile-menu {
    background: rgba(0,0,0,0.1);
    border-top: 1px solid rgba(255,255,255,0.1);
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .menu-section {
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding: 1rem;
  }
  
  .menu-section:last-child {
    border-bottom: none;
  }
  
  .menu-section-title {
    margin: 0 0 0.75rem 0;
    font-size: 0.8rem;
    color: rgba(255,255,255,0.8);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
  }
  
  .quick-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  
  .filter-chip {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.2s ease;
  }
  
  .filter-chip:hover {
    background: rgba(255,255,255,0.2);
  }
  
  .filter-chip.active {
    background: white;
    color: #97212d;
    border-color: white;
    font-weight: 600;
  }
  
  .filter-chip.clear-filters {
    background: rgba(255,0,0,0.2);
    border-color: rgba(255,0,0,0.3);
  }
  
  .filter-chip.clear-filters:hover {
    background: rgba(255,0,0,0.3);
  }
  
  .period-chip {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 0.6rem 0.8rem;
    text-align: left;
    line-height: 1.2;
  }
  
  .period-chip small {
    font-size: 0.7rem;
    opacity: 0.8;
    margin-top: 0.2rem;
  }
  
  .desktop-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
  }
  
  .citation-toggle-btn {
    padding: 0.75rem 1.5rem;
    background: #97212d;
    border: 2px solid #97212d;
    color: white;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .citation-toggle-btn:hover {
    background: #7a1b24;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  }
  
  .citation-toggle-btn:focus {
    outline: 3px solid #ffd700;
    outline-offset: 2px;
  }
  
  .theme-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.4rem;
  }
  
  .theme-chip {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.9);
    padding: 0.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.75rem;
    text-align: left;
    transition: all 0.2s ease;
  }
  
  .theme-chip:hover {
    background: rgba(255,255,255,0.1);
  }
  
  .theme-chip.active {
    background: rgba(255,255,255,0.15);
    border-color: rgba(255,255,255,0.4);
    font-weight: 600;
    color: white;
  }
  
  .research-action {
    width: 100%;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.15);
    color: white;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    text-align: left;
    transition: all 0.2s ease;
  }
  
  .research-action:hover {
    background: rgba(255,255,255,0.1);
  }
  
  .research-action:last-child {
    margin-bottom: 0;
  }
  
  .results-info {
    text-align: center;
    padding-top: 1rem;
  }
  
  .results-summary {
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
    line-height: 1.4;
  }
  
  .results-summary strong {
    color: white;
    font-size: 1.1rem;
  }
  
  .results-summary small {
    color: rgba(255,255,255,0.7);
    font-size: 0.8rem;
  }
  
  .research-timeline.mobile .main-content {
    flex-direction: column;
  }
  
  .mobile-content {
    height: calc(100vh - 60px); /* Account for mobile header */
    overflow: hidden;
  }
  
  .research-timeline.mobile .timeline-content {
    flex: 1;
    overflow: hidden;
  }
  
  .research-timeline.mobile .event-list {
    flex: 1;
    background: #f8f9fa;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0.75rem;
  }
  
  .mobile-filters {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: white;
    z-index: 200;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .mobile-filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
  }
  
  .mobile-filters-header h3 {
    margin: 0;
    color: #495057;
  }
  
  .close-filters {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
    padding: 0.25rem;
  }
  
  /* Tablet styles - maintain desktop layout until smaller screens */
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
    
    .research-sidebar {
      width: 280px; /* Slightly narrower on tablets but still sidebar */
    }
  }
  
  /* Mobile phone styles - switch to stacked layout */
  @media (max-width: 768px) {
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
  
  /* Touch-friendly adjustments for mobile */
  @media (max-width: 768px) and (pointer: coarse) {
    .view-modes button,
    .period-btn,
    .year-button {
      min-height: 44px; /* iOS touch target minimum */
      min-width: 44px;
    }
  }
  
  /* Mobile phone styles */
  @media (max-width: 768px) {
    .research-timeline {
      font-size: 14px;
    }
    
    .desktop-actions {
      flex-direction: column;
      gap: 0.75rem;
    }
    
    .citation-toggle-btn {
      padding: 0.875rem 1rem;
      font-size: 0.85rem;
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
      -webkit-appearance: none; /* Remove iOS default styling */
      appearance: none;
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
      max-height: 70vh; /* Increased height for better usability */
      border-right: none;
      border-bottom: 1px solid #e9ecef;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
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
      -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
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

  /* Dateless Events Review Styles */
  .dateless-review {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .review-header {
    padding: 2rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    flex-shrink: 0;
  }

  .review-header h3 {
    margin: 0 0 1rem 0;
    color: #97212d;
    font-size: 1.4rem;
  }

  .review-description {
    margin: 0 0 1.5rem 0;
    color: #666;
    line-height: 1.6;
  }

  .back-btn {
    background: #97212d;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
  }

  .back-btn:hover {
    background: #7a1b24;
  }

  .dateless-list {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    background: white;
  }

  .dateless-event {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
  }

  .dateless-event:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  }

  .event-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
  }

  .event-header h4 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
    line-height: 1.4;
    flex: 1;
  }

  .event-id {
    background: #e9ecef;
    color: #666;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-family: monospace;
    flex-shrink: 0;
  }

  .event-status, .event-reason, .event-created {
    margin: 0.5rem 0;
    font-size: 0.9rem;
    color: #555;
  }

  .event-status strong, .event-reason strong, .event-created strong {
    color: #333;
  }

  .event-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
  }

  .edit-btn {
    background: #007bff;
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    transition: background 0.2s;
    display: inline-block;
  }

  .edit-btn:hover {
    background: #0056b3;
    text-decoration: none;
  }

  .admin-action {
    background: #fff3cd !important;
    color: #856404 !important;
    border: 1px solid #ffeaa7;
  }

  .admin-action:hover {
    background: #ffeaa7 !important;
  }

  /* Desktop Dateless Review Tab */
  .dateless-review-tab {
    background: #fff3cd !important;
    color: #856404 !important;
    border: 1px solid #ffeaa7 !important;
    font-weight: 500;
    position: relative;
  }

  .dateless-review-tab:hover {
    background: #ffeaa7 !important;
  }

  .dateless-review-tab.active {
    background: #856404 !important;
    color: white !important;
    border-color: #856404 !important;
  }

  .dateless-review-tab:after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background: #dc3545;
    border-radius: 50%;
    border: 2px solid white;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0% {
      box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    70% {
      box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
    }
    100% {
      box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
    }
  }

  /* Desktop Logo Bar */
  .desktop-logo-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #97212d, #7a1b24);
    color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-bottom: 1px solid #6d1820;
  }

  .desktop-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .desktop-saho-logo {
    width: 48px;
    height: 48px;
    object-fit: contain;
    /* SVG is already white, no filter needed */
    transition: transform 0.2s;
  }

  .desktop-saho-logo:hover {
    transform: scale(1.05);
  }

  .brand-text h1 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
    line-height: 1.2;
  }

  .brand-text p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.9;
    font-weight: 400;
  }

  .app-stats {
    display: flex;
    gap: 2rem;
  }

  .stat-item {
    text-align: center;
    min-width: 80px;
  }

  .stat-value {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
  }

  .stat-label {
    display: block;
    font-size: 0.75rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .stat-item.alert .stat-value {
    color: #ffc107;
    animation: pulse-yellow 2s infinite;
  }

  .stat-item.alert .stat-label {
    color: #ffc107;
    font-weight: 500;
  }

  @keyframes pulse-yellow {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.7;
    }
  }

  /* Desktop Header Styles */
  .desktop-header {
    background: linear-gradient(135deg, #97212d, #7a1b24);
    color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-bottom: 1px solid #6d1820;
  }

  .header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
  }

  .brand-section {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .desktop-logo {
    height: 64px;
    width: auto;
    flex-shrink: 0;
  }

  .brand-info h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
    letter-spacing: -0.5px;
  }

  .brand-info p {
    margin: 0.25rem 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
    font-weight: 300;
  }

  .header-controls {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 0 2rem 1.5rem;
  }

  .search-section {
    display: flex;
    gap: 1rem;
    align-items: center;
  }

  .search-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    background: rgba(255,255,255,0.1);
    color: white;
    font-size: 1rem;
    transition: all 0.2s ease;
  }

  .search-input::placeholder {
    color: rgba(255,255,255,0.7);
  }

  .search-input:focus {
    outline: none;
    border-color: rgba(255,255,255,0.6);
    background: rgba(255,255,255,0.15);
  }


  .view-tabs {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
  }

  .view-tabs button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    color: white;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
  }

  .view-tabs button:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.5);
  }

  .view-tabs button.active {
    background: white;
    color: #97212d;
    border-color: white;
  }

  .stats-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.9rem;
  }

  .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1;
  }

  .stat-label {
    font-size: 0.75rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.25rem;
  }

  .stat-divider {
    opacity: 0.6;
    font-weight: 300;
  }

  .stat-item.alert .stat-value {
    color: #ffc107;
    animation: pulse-yellow 2s infinite;
  }

  .stat-item.alert .stat-label {
    color: #ffc107;
    font-weight: 500;
  }

  @keyframes pulse-yellow {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.7;
    }
  }

  /* Adjust main content when desktop header is present */
  .research-timeline:not(.mobile) .main-content {
    height: calc(100vh - 120px); /* Account for desktop header height */
  }

  /* Responsive adjustments for desktop header */
  @media (max-width: 1024px) {
    .header-top {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }
    
    .brand-info h1 {
      font-size: 2rem;
    }
  }

  @media (max-width: 768px) {
    .brand-info h1 {
      font-size: 1.8rem;
    }
    
    .stats-section {
      flex-wrap: wrap;
      justify-content: center;
    }
  }
</style>