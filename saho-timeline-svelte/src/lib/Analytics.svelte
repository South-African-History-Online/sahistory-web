<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { format } from 'date-fns';
  
  export let events = [];
  export let filteredEvents = [];
  export let historicalPeriods = [];
  export let analyticalMode = 'density';
  
  const dispatch = createEventDispatcher();
  
  let chartContainer;
  
  // Calculate analytics data
  $: eventsByDecade = calculateEventsByDecade(filteredEvents);
  $: eventsByPeriod = calculateEventsByPeriod(filteredEvents, historicalPeriods);
  $: themeAnalysis = calculateThemeAnalysis(filteredEvents);
  $: temporalDensity = calculateTemporalDensity(filteredEvents);
  $: keyStatistics = calculateKeyStatistics(filteredEvents);
  
  function calculateEventsByDecade(events) {
    const decades = {};
    events.forEach(event => {
      const year = new Date(event.date).getFullYear();
      const decade = Math.floor(year / 10) * 10;
      decades[decade] = (decades[decade] || 0) + 1;
    });
    return Object.entries(decades)
      .map(([decade, count]) => ({ decade: parseInt(decade), count }))
      .sort((a, b) => a.decade - b.decade);
  }
  
  function calculateEventsByPeriod(events, periods) {
    return periods.map(period => {
      const count = events.filter(event => {
        const year = new Date(event.date).getFullYear();
        return year >= period.start && year <= period.end;
      }).length;
      return { ...period, count };
    });
  }
  
  function calculateThemeAnalysis(events) {
    const themes = {};
    events.forEach(event => {
      if (event.themes) {
        event.themes.forEach(theme => {
          themes[theme] = (themes[theme] || 0) + 1;
        });
      }
    });
    return Object.entries(themes)
      .map(([theme, count]) => ({ theme, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 20);
  }
  
  function calculateTemporalDensity(events) {
    if (events.length === 0) return [];
    
    const years = events.map(e => new Date(e.date).getFullYear());
    const minYear = Math.min(...years);
    const maxYear = Math.max(...years);
    const yearRange = maxYear - minYear;
    const binSize = Math.max(1, Math.floor(yearRange / 50));
    
    const bins = {};
    events.forEach(event => {
      const year = new Date(event.date).getFullYear();
      const bin = Math.floor((year - minYear) / binSize) * binSize + minYear;
      bins[bin] = (bins[bin] || 0) + 1;
    });
    
    return Object.entries(bins)
      .map(([year, count]) => ({ year: parseInt(year), count }))
      .sort((a, b) => a.year - b.year);
  }
  
  function calculateKeyStatistics(events) {
    if (events.length === 0) return {};
    
    const years = events.map(e => new Date(e.date).getFullYear());
    const decades = [...new Set(years.map(y => Math.floor(y / 10) * 10))];
    const centuries = [...new Set(years.map(y => Math.floor(y / 100) * 100))];
    
    const eventsWithThemes = events.filter(e => e.themes && e.themes.length > 0);
    const eventsWithImages = events.filter(e => e.image);
    const eventsWithLocation = events.filter(e => e.location);
    
    return {
      totalEvents: events.length,
      timeSpan: `${Math.min(...years)}–${Math.max(...years)}`,
      yearsSpanned: Math.max(...years) - Math.min(...years),
      decadesCovered: decades.length,
      centuriesCovered: centuries.length,
      eventsWithThemes: eventsWithThemes.length,
      eventsWithImages: eventsWithImages.length,
      eventsWithLocation: eventsWithLocation.length,
      averageEventsPerYear: (events.length / (Math.max(...years) - Math.min(...years))).toFixed(1),
      mostActiveDecade: eventsByDecade.reduce((max, current) => 
        current.count > max.count ? current : max, { decade: 0, count: 0 }
      )
    };
  }
  
  function changeAnalyticalMode(mode) {
    analyticalMode = mode;
    dispatch('analyticalModeChange', mode);
  }
</script>

<div class="analytics-container">
  <header class="analytics-header">
    <h2>Data Analytics & Insights</h2>
    <div class="mode-selector">
      <button 
        class:active={analyticalMode === 'density'}
        on:click={() => changeAnalyticalMode('density')}
      >
        📊 Temporal Density
      </button>
      <button 
        class:active={analyticalMode === 'themes'}
        on:click={() => changeAnalyticalMode('themes')}
      >
        🏷️ Theme Analysis
      </button>
      <button 
        class:active={analyticalMode === 'periods'}
        on:click={() => changeAnalyticalMode('periods')}
      >
        📅 Historical Periods
      </button>
      <button 
        class:active={analyticalMode === 'statistics'}
        on:click={() => changeAnalyticalMode('statistics')}
      >
        📈 Key Statistics
      </button>
    </div>
  </header>
  
  <div class="analytics-content">
    {#if analyticalMode === 'statistics'}
      <!-- Key Statistics Dashboard -->
      <div class="statistics-grid">
        <div class="stat-card primary">
          <div class="stat-number">{keyStatistics.totalEvents?.toLocaleString()}</div>
          <div class="stat-label">Total Events</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-number">{keyStatistics.timeSpan}</div>
          <div class="stat-label">Time Period Covered</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-number">{keyStatistics.yearsSpanned}</div>
          <div class="stat-label">Years Spanned</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-number">{keyStatistics.averageEventsPerYear}</div>
          <div class="stat-label">Events per Year (Avg)</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-number">{keyStatistics.mostActiveDecade?.decade}s</div>
          <div class="stat-label">Most Active Decade</div>
          <div class="stat-detail">{keyStatistics.mostActiveDecade?.count} events</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-number">{keyStatistics.centuriesCovered}</div>
          <div class="stat-label">Centuries Covered</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-number">{((keyStatistics.eventsWithImages / keyStatistics.totalEvents) * 100).toFixed(1)}%</div>
          <div class="stat-label">Events with Images</div>
        </div>
        
        <div class="stat-card">
          <div class="stat-number">{((keyStatistics.eventsWithThemes / keyStatistics.totalEvents) * 100).toFixed(1)}%</div>
          <div class="stat-label">Events with Themes</div>
        </div>
      </div>
      
    {:else if analyticalMode === 'density'}
      <!-- Temporal Density Visualization -->
      <div class="chart-section">
        <h3>Event Density Over Time</h3>
        <p class="chart-description">
          This visualization shows the distribution of historical events across time periods, 
          revealing patterns of historical documentation and activity.
        </p>
        
        <div class="density-chart">
          {#each temporalDensity as bin, i}
            {@const maxCount = Math.max(...temporalDensity.map(b => b.count))}
            {@const height = (bin.count / maxCount) * 200}
            <div 
              class="density-bar"
              style="height: {height}px"
              title="{bin.year}: {bin.count} events"
            >
              <div class="bar-label">{bin.year}</div>
              <div class="bar-count">{bin.count}</div>
            </div>
          {/each}
        </div>
      </div>
      
    {:else if analyticalMode === 'themes'}
      <!-- Theme Analysis -->
      <div class="chart-section">
        <h3>Most Common Themes</h3>
        <p class="chart-description">
          Analysis of the most frequently occurring themes across all events in the current selection.
        </p>
        
        <div class="theme-chart">
          {#each themeAnalysis as { theme, count }, i}
            {@const maxCount = themeAnalysis[0]?.count || 1}
            {@const width = (count / maxCount) * 100}
            <div class="theme-bar">
              <div class="theme-info">
                <span class="theme-name">{theme}</span>
                <span class="theme-count">{count} events</span>
              </div>
              <div class="theme-bar-visual">
                <div 
                  class="theme-bar-fill"
                  style="width: {width}%"
                ></div>
              </div>
            </div>
          {/each}
        </div>
      </div>
      
    {:else if analyticalMode === 'periods'}
      <!-- Historical Periods Analysis -->
      <div class="chart-section">
        <h3>Events by Historical Period</h3>
        <p class="chart-description">
          Distribution of events across major historical periods in South African history.
        </p>
        
        <div class="periods-chart">
          {#each eventsByPeriod as period}
            {@const maxCount = Math.max(...eventsByPeriod.map(p => p.count))}
            {@const width = period.count > 0 ? (period.count / maxCount) * 100 : 0}
            <div class="period-bar">
              <div class="period-header">
                <div class="period-name" style="border-left: 4px solid {period.color}">
                  {period.name}
                </div>
                <div class="period-count">{period.count} events</div>
              </div>
              <div class="period-visual">
                <div 
                  class="period-fill"
                  style="width: {width}%; background: {period.color}"
                ></div>
              </div>
              <div class="period-details">
                <span>{period.start}–{period.end}</span>
                <span>{((period.count / keyStatistics.totalEvents) * 100).toFixed(1)}% of total</span>
              </div>
            </div>
          {/each}
        </div>
      </div>
    {/if}
    
    <!-- Research Insights -->
    <div class="insights-section">
      <h3>Research Insights</h3>
      <div class="insights-grid">
        <div class="insight-card">
          <h4>📈 Documentation Trends</h4>
          <p>
            Event documentation shows significant increase in modern periods, 
            reflecting both increased historical activity and better record-keeping.
          </p>
        </div>
        
        <div class="insight-card">
          <h4>🏛️ Historical Coverage</h4>
          <p>
            The archive spans {keyStatistics.centuriesCovered} centuries, providing 
            comprehensive coverage of South African historical development.
          </p>
        </div>
        
        <div class="insight-card">
          <h4>🎯 Research Opportunities</h4>
          <p>
            {keyStatistics.eventsWithThemes} events ({((keyStatistics.eventsWithThemes / keyStatistics.totalEvents) * 100).toFixed(1)}%) 
            are tagged with research themes, enabling thematic analysis.
          </p>
        </div>
        
        <div class="insight-card">
          <h4>📸 Visual Documentation</h4>
          <p>
            {keyStatistics.eventsWithImages} events ({((keyStatistics.eventsWithImages / keyStatistics.totalEvents) * 100).toFixed(1)}%) 
            include visual materials, supporting multimedia research approaches.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .analytics-container {
    height: 100%;
    padding: 2rem;
    overflow-y: auto;
    background: white;
  }
  
  .analytics-header {
    margin-bottom: 2rem;
  }
  
  .analytics-header h2 {
    margin: 0 0 1rem;
    color: #97212d;
    font-size: 1.8rem;
  }
  
  .mode-selector {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  
  .mode-selector button {
    padding: 0.6rem 1.2rem;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s;
  }
  
  .mode-selector button:hover {
    background: #f8f9fa;
  }
  
  .mode-selector button.active {
    background: #97212d;
    color: white;
    border-color: #97212d;
  }
  
  .analytics-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
  
  .statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }
  
  .stat-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e9ecef;
  }
  
  .stat-card.primary {
    background: linear-gradient(135deg, #97212d 0%, #7a1b24 100%);
    color: white;
  }
  
  .stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
  }
  
  .stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
    font-weight: 500;
  }
  
  .stat-detail {
    font-size: 0.8rem;
    margin-top: 0.25rem;
    opacity: 0.7;
  }
  
  .chart-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }
  
  .chart-section h3 {
    margin: 0 0 0.5rem;
    color: #97212d;
  }
  
  .chart-description {
    margin: 0 0 2rem;
    color: #666;
    line-height: 1.6;
  }
  
  .density-chart {
    display: flex;
    align-items: end;
    gap: 2px;
    min-height: 250px;
    overflow-x: auto;
    padding: 1rem;
    background: white;
    border-radius: 6px;
  }
  
  .density-bar {
    min-width: 20px;
    background: linear-gradient(to top, #97212d 0%, #cd6d7a 100%);
    position: relative;
    cursor: pointer;
    transition: opacity 0.2s;
  }
  
  .density-bar:hover {
    opacity: 0.8;
  }
  
  .bar-label {
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%) rotate(-45deg);
    font-size: 0.7rem;
    white-space: nowrap;
  }
  
  .bar-count {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    color: #495057;
  }
  
  .theme-chart {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .theme-bar {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .theme-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .theme-name {
    font-weight: 500;
    color: #495057;
  }
  
  .theme-count {
    font-size: 0.9rem;
    color: #666;
  }
  
  .theme-bar-visual {
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
  }
  
  .theme-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #97212d 0%, #cd6d7a 100%);
    transition: width 0.3s ease;
  }
  
  .periods-chart {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .period-bar {
    background: white;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
  }
  
  .period-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }
  
  .period-name {
    font-weight: 600;
    color: #495057;
    padding-left: 0.75rem;
  }
  
  .period-count {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
  }
  
  .period-visual {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
  }
  
  .period-fill {
    height: 100%;
    transition: width 0.3s ease;
  }
  
  .period-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #666;
  }
  
  .insights-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }
  
  .insights-section h3 {
    margin: 0 0 1.5rem;
    color: #97212d;
  }
  
  .insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
  }
  
  .insight-card {
    background: white;
    padding: 1.5rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
  }
  
  .insight-card h4 {
    margin: 0 0 0.75rem;
    color: #495057;
    font-size: 1rem;
  }
  
  .insight-card p {
    margin: 0;
    color: #666;
    line-height: 1.6;
    font-size: 0.9rem;
  }
  
  @media (max-width: 768px) {
    .analytics-container {
      padding: 1rem;
    }
    
    .statistics-grid {
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .stat-number {
      font-size: 1.5rem;
    }
    
    .mode-selector {
      flex-direction: column;
    }
  }
</style>