<script>
  import { createEventDispatcher } from 'svelte';
  
  export let historicalPeriods = [];
  export let availableThemes = [];
  export let eventTypes = [];
  export let selectedPeriod = null;
  export let selectedThemes = [];
  export let selectedEventTypes = [];
  export let selectedYear = null;
  export let showCitations = false;
  export let eventsCount = 0;
  
  const dispatch = createEventDispatcher();
  
  let expandedSections = {
    periods: true,
    tools: true
  };
  
  
  function clearAllFilters() {
    selectedPeriod = null;
    selectedThemes = [];
    selectedEventTypes = [];
    selectedYear = null;
  }
  
  function toggleSection(section) {
    expandedSections[section] = !expandedSections[section];
  }
  
  
</script>

<div class="research-tools">
  <header class="tools-header">
    <h3>Research Tools</h3>
    <div class="results-count">
      {eventsCount.toLocaleString()} results
    </div>
  </header>
  
  <!-- Quick Actions -->
  <section class="tool-section">
    <button class="section-header" on:click={() => toggleSection('tools')}>
      <span class="icon">{expandedSections.tools ? '📂' : '📁'}</span>
      Research Actions
    </button>
    
    {#if expandedSections.tools}
      <div class="section-content">
        <label class="checkbox-item">
          <input 
            type="checkbox" 
            bind:checked={showCitations}
          />
          Show Academic Citations
        </label>
        
        <button class="action-btn clear-btn" on:click={clearAllFilters}>
          🗑️ Clear All Filters
        </button>
        
        <div class="filter-summary">
          {#if selectedPeriod || selectedThemes.length > 0 || selectedEventTypes.length > 0}
            <strong>Active Filters:</strong>
            {#if selectedPeriod}
              <span class="filter-tag">📅 {selectedPeriod.name}</span>
            {/if}
            {#each selectedThemes as theme}
              <span class="filter-tag">🏷️ {theme}</span>
            {/each}
            {#each selectedEventTypes as type}
              <span class="filter-tag">📋 {type}</span>
            {/each}
          {:else}
            <em>No filters applied</em>
          {/if}
        </div>
      </div>
    {/if}
  </section>
  
  <!-- Historical Periods -->
  <section class="tool-section">
    <button class="section-header" on:click={() => toggleSection('periods')}>
      <span class="icon">{expandedSections.periods ? '📂' : '📁'}</span>
      Historical Periods
    </button>
    
    {#if expandedSections.periods}
      <div class="section-content">
        <div class="period-list">
          {#each historicalPeriods as period}
            <label class="period-item">
              <input 
                type="radio" 
                bind:group={selectedPeriod} 
                value={period}
                name="period"
              />
              <div class="period-info">
                <div class="period-name">{period.name}</div>
                <div class="period-range">{period.start}–{period.end}</div>
                <div class="period-bar" style="background: {period.color}"></div>
              </div>
            </label>
          {/each}
        </div>
      </div>
    {/if}
  </section>
  
  <!-- Research Tips -->
  <section class="tool-section tips-section">
    <button class="section-header" on:click={() => toggleSection('tips')}>
      <span class="icon">💡</span>
      Research Tips
    </button>
    
    {#if expandedSections.tips}
      <div class="section-content">
        <div class="tip">
          <strong>Advanced Search:</strong> Use quotes for exact phrases, + for required terms
        </div>
        <div class="tip">
          <strong>Timeline Navigation:</strong> Click years to jump to specific periods
        </div>
        <div class="tip">
          <strong>Citations:</strong> Enable academic citations for scholarly work
        </div>
        <div class="tip">
          <strong>Event Details:</strong> Click any event card to view full details and citations
        </div>
      </div>
    {/if}
  </section>
</div>

<style>
  .research-tools {
    height: 100%;
    overflow-y: auto;
    background: white;
  }
  
  .tools-header {
    padding: 1.5rem;
    border-bottom: 2px solid #e9ecef;
    background: #f8f9fa;
  }
  
  .tools-header h3 {
    margin: 0;
    color: #97212d;
    font-size: 1.3rem;
    font-weight: 600;
  }
  
  .results-count {
    margin-top: 0.5rem;
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
  }
  
  .tool-section {
    border-bottom: 1px solid #e9ecef;
  }
  
  .section-header {
    width: 100%;
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #495057;
    transition: background 0.2s;
  }
  
  .section-header:hover {
    background: #f8f9fa;
  }
  
  .icon {
    font-size: 1.1rem;
  }
  
  .count {
    margin-left: auto;
    font-size: 0.8rem;
    color: #666;
    font-weight: normal;
  }
  
  .section-content {
    padding: 0 1.5rem 1rem;
  }
  
  .checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    cursor: pointer;
    color: #495057;
    font-weight: 500;
  }
  
  .action-btn {
    width: 100%;
    padding: 0.75rem;
    background: #97212d;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    margin-bottom: 1rem;
    transition: background 0.2s;
  }
  
  .action-btn:hover {
    background: #7a1b24;
  }
  
  
  .filter-summary {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    line-height: 1.6;
  }
  
  .filter-tag {
    display: inline-block;
    background: #e9ecef;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    margin: 0.1rem;
    font-size: 0.8rem;
  }
  
  .period-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .period-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: background 0.2s;
  }
  
  .period-item:hover {
    background: #f8f9fa;
  }
  
  .period-info {
    flex: 1;
  }
  
  .period-name {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.25rem;
  }
  
  .period-range {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.25rem;
  }
  
  .period-bar {
    height: 3px;
    border-radius: 2px;
  }
  
  
  
  .tips-section {
    background: #f8f9fa;
  }
  
  .tip {
    margin-bottom: 1rem;
    font-size: 0.85rem;
    line-height: 1.5;
    color: #495057;
  }
  
  .tip strong {
    color: #97212d;
  }
</style>