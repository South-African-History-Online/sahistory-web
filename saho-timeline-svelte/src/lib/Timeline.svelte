<script>
  import { onMount, createEventDispatcher } from 'svelte';
  import VirtualList from 'svelte-virtual-list';
  import { format } from 'date-fns';
  import EventCard from './EventCard.svelte';
  import TimelineCanvas from './TimelineCanvas.svelte';
  
  export let events = [];
  export let minYear = 1300;
  export let maxYear = 2024;
  
  const dispatch = createEventDispatcher();
  
  let selectedYear = null;
  let selectedEvent = null;
  let viewMode = 'list'; // 'list', 'timeline', 'grid'
  let searchQuery = '';
  let filteredEvents = events;
  
  // Virtual list configuration
  let itemHeight = 120; // Approximate height of each event card
  let viewport;
  
  // Filter events based on search
  $: filteredEvents = searchQuery 
    ? events.filter(e => 
        e.title?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        e.body?.toLowerCase().includes(searchQuery.toLowerCase())
      )
    : events;
  
  // Group events by year for navigation
  $: eventsByYear = filteredEvents.reduce((acc, event) => {
    const year = new Date(event.date).getFullYear();
    if (!acc[year]) acc[year] = [];
    acc[year].push(event);
    return acc;
  }, {});
  
  $: years = Object.keys(eventsByYear).map(Number).sort((a, b) => a - b);
  
  function handleEventClick(event) {
    selectedEvent = event;
    dispatch('select', event);
  }
  
  function jumpToYear(year) {
    selectedYear = year;
    const yearEvents = eventsByYear[year];
    if (yearEvents && yearEvents.length > 0) {
      // Find index of first event in that year
      const index = filteredEvents.findIndex(e => 
        new Date(e.date).getFullYear() === year
      );
      if (index >= 0 && viewport) {
        // Scroll virtual list to that position
        viewport.scrollTo(0, index * itemHeight);
      }
    }
  }
  
  function handleKeydown(e) {
    if (e.key === 'Escape') {
      selectedEvent = null;
    }
  }
</script>

<svelte:window on:keydown={handleKeydown} />

<div class="timeline-container">
  <!-- Header Controls -->
  <div class="timeline-header">
    <div class="header-info">
      <h1>South African History Timeline</h1>
      <p class="event-count">{filteredEvents.length} events from {minYear} to {maxYear}</p>
    </div>
    
    <div class="controls">
      <input 
        type="search" 
        placeholder="Search events..." 
        bind:value={searchQuery}
        class="search-input"
      />
      
      <div class="view-modes">
        <button 
          class:active={viewMode === 'list'}
          on:click={() => viewMode = 'list'}
        >
          List
        </button>
        <button 
          class:active={viewMode === 'timeline'}
          on:click={() => viewMode = 'timeline'}
        >
          Timeline
        </button>
        <button 
          class:active={viewMode === 'grid'}
          on:click={() => viewMode = 'grid'}
        >
          Grid
        </button>
      </div>
    </div>
  </div>
  
  <!-- Year Navigation -->
  <div class="year-nav">
    <div class="year-scroll">
      {#each years as year}
        <button 
          class="year-button"
          class:active={selectedYear === year}
          on:click={() => jumpToYear(year)}
        >
          {year}
          <span class="count">({eventsByYear[year].length})</span>
        </button>
      {/each}
    </div>
  </div>
  
  <!-- Main Content Area -->
  <div class="timeline-content">
    {#if viewMode === 'list'}
      <!-- Virtual List for Performance -->
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
          />
        </VirtualList>
      </div>
    {:else if viewMode === 'timeline'}
      <!-- Canvas-based Timeline Visualization -->
      <TimelineCanvas 
        {events}
        {minYear}
        {maxYear}
        on:select={(e) => handleEventClick(e.detail)}
      />
    {:else if viewMode === 'grid'}
      <!-- Grid View -->
      <div class="event-grid">
        {#each filteredEvents.slice(0, 100) as event}
          <div 
            class="grid-item"
            on:click={() => handleEventClick(event)}
            class:selected={selectedEvent?.id === event.id}
          >
            {#if event.image}
              <img src={event.image} alt={event.title} loading="lazy" />
            {/if}
            <h3>{event.title}</h3>
            <p class="date">{format(new Date(event.date), 'MMM d, yyyy')}</p>
          </div>
        {/each}
      </div>
    {/if}
  </div>
  
  <!-- Event Detail Modal -->
  {#if selectedEvent}
    <div class="modal-overlay" on:click={() => selectedEvent = null}>
      <div class="modal" on:click|stopPropagation>
        <button class="close" on:click={() => selectedEvent = null}>×</button>
        {#if selectedEvent.image}
          <img src={selectedEvent.image} alt={selectedEvent.title} />
        {/if}
        <h2>{selectedEvent.title}</h2>
        <p class="date">{format(new Date(selectedEvent.date), 'MMMM d, yyyy')}</p>
        <div class="body">{@html selectedEvent.body}</div>
        {#if selectedEvent.url}
          <a href={selectedEvent.url} target="_blank" rel="noopener">
            Read full article →
          </a>
        {/if}
      </div>
    </div>
  {/if}
</div>

<style>
  .timeline-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f5f5f5;
  }
  
  .timeline-header {
    background: white;
    padding: 1rem 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .header-info h1 {
    margin: 0;
    color: #97212d;
    font-size: 1.8rem;
  }
  
  .event-count {
    margin: 0.25rem 0 0;
    color: #666;
    font-size: 0.9rem;
  }
  
  .controls {
    display: flex;
    gap: 1rem;
    align-items: center;
  }
  
  .search-input {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    width: 250px;
  }
  
  .view-modes {
    display: flex;
    gap: 0.25rem;
    background: #f0f0f0;
    padding: 0.25rem;
    border-radius: 4px;
  }
  
  .view-modes button {
    padding: 0.5rem 1rem;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 2px;
    transition: all 0.2s;
  }
  
  .view-modes button.active {
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  
  .year-nav {
    background: white;
    border-bottom: 1px solid #ddd;
    overflow-x: auto;
    white-space: nowrap;
    padding: 0.5rem 1rem;
  }
  
  .year-scroll {
    display: flex;
    gap: 0.5rem;
  }
  
  .year-button {
    padding: 0.25rem 0.75rem;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    border-radius: 20px;
    font-size: 0.85rem;
    transition: all 0.2s;
    white-space: nowrap;
  }
  
  .year-button:hover {
    background: #f0f0f0;
  }
  
  .year-button.active {
    background: #97212d;
    color: white;
    border-color: #97212d;
  }
  
  .year-button .count {
    font-size: 0.75rem;
    opacity: 0.7;
  }
  
  .timeline-content {
    flex: 1;
    overflow: hidden;
    position: relative;
  }
  
  .event-list {
    height: 100%;
    overflow-y: auto;
    padding: 1rem;
  }
  
  .event-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    padding: 1rem;
    overflow-y: auto;
    height: 100%;
  }
  
  .grid-item {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  
  .grid-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  }
  
  .grid-item.selected {
    outline: 3px solid #97212d;
  }
  
  .grid-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
  }
  
  .grid-item h3 {
    padding: 0.5rem 1rem 0;
    margin: 0;
    font-size: 0.95rem;
    color: #333;
  }
  
  .grid-item .date {
    padding: 0 1rem 1rem;
    margin: 0.25rem 0 0;
    font-size: 0.8rem;
    color: #666;
  }
  
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 2rem;
  }
  
  .modal {
    background: white;
    border-radius: 8px;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    position: relative;
  }
  
  .modal .close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: #666;
  }
  
  .modal img {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 1rem;
  }
  
  .modal h2 {
    color: #97212d;
    margin: 0 0 0.5rem;
  }
  
  .modal .date {
    color: #666;
    margin-bottom: 1rem;
  }
  
  .modal .body {
    line-height: 1.6;
    color: #333;
    margin-bottom: 1rem;
  }
  
  .modal a {
    color: #97212d;
    text-decoration: none;
    font-weight: 500;
  }
  
  .modal a:hover {
    text-decoration: underline;
  }
</style>