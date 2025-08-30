<script>
  import { format } from 'date-fns';
  import { createEventDispatcher } from 'svelte';
  import Icon from './Icon.svelte';
  
  export let event;
  export let selected = false;
  export let researchMode = false;
  export let showCitations = false;
  
  const dispatch = createEventDispatcher();
  
  function handleClick() {
    dispatch('click', event);
  }
  
  function generateCitation(event) {
    // Use field_ref_str if available, otherwise generate generic citation
    if (event.field_ref_str && event.field_ref_str.trim()) {
      return event.field_ref_str.trim();
    }
    
    // Fallback to generic citation
    const year = new Date(event.date).getFullYear();
    return `"${event.title}" (${year}). South African History Online. Retrieved ${format(new Date(), 'MMM d, yyyy')}.`;
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
  
  // Extract year for visual emphasis
  $: eventYear = new Date(event.date).getFullYear();
  $: formattedDate = format(new Date(event.date), 'MMM d, yyyy');
  $: historicalPeriod = getHistoricalPeriod(eventYear);
  
  function getHistoricalPeriod(year) {
    if (year < 1650) return { name: 'Pre-Colonial', color: '#8B4513' };
    if (year < 1900) return { name: 'Colonial', color: '#2F4F4F' };
    if (year < 1948) return { name: 'Union Era', color: '#CD853F' };
    if (year < 1994) return { name: 'Apartheid', color: '#B22222' };
    return { name: 'Democratic', color: '#228B22' };
  }
</script>

<button
  class="event-card"
  class:selected
  class:research-mode={researchMode}
  on:click={handleClick}
  aria-label="View details for {event.title}, {formattedDate}"
  aria-pressed={selected}
>
  <div class="event-timeline-marker">
    <div class="event-year" style="border-left: 4px solid {historicalPeriod.color}">
      {eventYear}
    </div>
    {#if researchMode}
      <div class="historical-period" style="color: {historicalPeriod.color}">
        {historicalPeriod.name}
      </div>
    {/if}
  </div>
  
  <div class="event-content">
    {#if event.image}
      <div class="event-image">
        <img src={event.image} alt={event.title} loading="lazy" />
      </div>
    {/if}
    
    <div class="event-details">
      <header class="event-header">
        <h3 class="event-title">{event.title}</h3>
        <div class="event-meta">
          <time class="event-date">{formattedDate}</time>
          {#if event.type}
            <span class="event-type">{event.type}</span>
          {/if}
        </div>
      </header>
      
      {#if event.body}
        <p class="event-excerpt">
          {event.body.substring(0, researchMode ? 200 : 150)}{event.body.length > (researchMode ? 200 : 150) ? '...' : ''}
        </p>
      {/if}
      
      <div class="event-metadata">
        {#if event.location}
          <div class="metadata-item">
            <Icon name="location" size="16" color="#666" />
            <span class="metadata-text">{event.location}</span>
          </div>
        {/if}
        
        {#if event.themes && event.themes.length > 0}
          <div class="metadata-item themes">
            <Icon name="tag" size="16" color="#666" />
            <div class="theme-tags">
              {#each event.themes.slice(0, 3) as theme}
                <span class="theme-tag">{theme}</span>
              {/each}
              {#if event.themes.length > 3}
                <span class="theme-more">+{event.themes.length - 3}</span>
              {/if}
            </div>
          </div>
        {/if}
      </div>
      
      {#if showCitations && researchMode}
        <div class="citation-preview">
          <strong>Citations:</strong>
          {#each parseCitations(generateCitation(event)) as citation, index}
            <div class="citation-item">
              <span class="citation-number">{index + 1}.</span>
              <p class="citation-text">{citation}</p>
            </div>
          {/each}
        </div>
      {/if}
    </div>
  </div>
</button>

<style>
  .event-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    display: flex;
    gap: 1.5rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 2px solid #f1f3f4;
    position: relative;
    overflow: hidden;
    text-align: left;
    font-family: inherit;
    font-size: inherit;
    width: 100%;
  }
  
  .event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(151, 33, 45, 0.12);
    border-color: #97212d;
  }
  
  .event-card:focus {
    outline: 3px solid #ffd700;
    outline-offset: 2px;
    border-color: #97212d;
  }
  
  .event-card.selected {
    background: linear-gradient(135deg, #fef5f5 0%, #fdf2f2 100%);
    border: 3px solid #97212d;
    box-shadow: 0 8px 25px rgba(151, 33, 45, 0.2);
  }
  
  .event-card.research-mode {
    padding: 2rem;
    margin-bottom: 1.5rem;
  }
  
  .event-timeline-marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 80px;
    text-align: center;
  }
  
  .event-year {
    font-size: 1.8rem;
    font-weight: 700;
    color: #97212d;
    padding-left: 1rem;
    margin-bottom: 0.25rem;
  }
  
  .historical-period {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.8;
  }
  
  .event-content {
    flex: 1;
    display: flex;
    gap: 1.5rem;
  }
  
  .event-image {
    width: 120px;
    height: 90px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  
  .research-mode .event-image {
    width: 160px;
    height: 120px;
  }
  
  .event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }
  
  .event-card:hover .event-image img {
    transform: scale(1.05);
  }
  
  .event-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .event-header {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .event-title {
    margin: 0;
    font-size: 1.2rem;
    color: #1a1a1a;
    font-weight: 600;
    line-height: 1.4;
  }
  
  .research-mode .event-title {
    font-size: 1.3rem;
  }
  
  .event-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
  }
  
  .event-date {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
  }
  
  .event-type {
    background: #e9ecef;
    color: #495057;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
  }
  
  .event-excerpt {
    margin: 0;
    font-size: 0.95rem;
    color: #555;
    line-height: 1.6;
  }
  
  .research-mode .event-excerpt {
    font-size: 1rem;
    line-height: 1.7;
  }
  
  .event-metadata {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .metadata-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.85rem;
  }
  
  
  .metadata-text {
    color: #666;
    font-style: italic;
  }
  
  .themes {
    align-items: flex-start;
  }
  
  .theme-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
  }
  
  .theme-tag {
    background: #f8f9fa;
    color: #495057;
    padding: 0.15rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
    border: 1px solid #e9ecef;
  }
  
  .theme-more {
    background: #97212d;
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
  }
  
  .citation-preview {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    border-left: 3px solid #97212d;
    margin-top: 0.5rem;
  }
  
  .citation-preview strong {
    color: #97212d;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.5rem;
  }
  
  .citation-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }
  
  .citation-item:last-child {
    margin-bottom: 0;
  }
  
  .citation-number {
    color: #97212d;
    font-weight: 600;
    font-size: 0.8rem;
    min-width: 1.2rem;
    flex-shrink: 0;
  }
  
  .citation-text {
    margin: 0;
    font-family: 'Georgia', serif;
    font-style: italic;
    font-size: 0.8rem;
    color: #555;
    line-height: 1.5;
    flex: 1;
  }
  
  @media (max-width: 768px) {
    .event-card {
      flex-direction: column;
      padding: 1rem;
      margin-bottom: 1rem;
      min-height: 160px; /* Ensure good touch target */
      -webkit-tap-highlight-color: rgba(151, 33, 45, 0.1); /* Better tap feedback */
    }
    
    .event-card.research-mode {
      padding: 1.25rem;
    }
    
    .event-timeline-marker {
      flex-direction: row;
      justify-content: flex-start;
      align-items: center;
      gap: 1rem;
      min-width: auto;
      margin-bottom: 0.75rem;
    }
    
    .event-year {
      font-size: 1.3rem;
      margin-bottom: 0;
      padding-left: 0.75rem;
    }
    
    .historical-period {
      font-size: 0.65rem;
    }
    
    .event-content {
      flex-direction: column;
      gap: 1rem;
    }
    
    .event-image {
      width: 100%;
      height: 160px;
      order: -1; /* Move image to top on mobile */
      -webkit-touch-callout: none; /* Prevent image save popup on long press */
    }
    
    .research-mode .event-image {
      width: 100%;
      height: 180px;
    }
    
    .event-title {
      font-size: 1.1rem;
      line-height: 1.3;
    }
    
    .research-mode .event-title {
      font-size: 1.2rem;
    }
    
    .event-meta {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.4rem;
    }
    
    .event-excerpt {
      font-size: 0.9rem;
      line-height: 1.5;
    }
    
    .metadata-item {
      font-size: 0.8rem;
      gap: 0.4rem;
    }
    
    .theme-tags {
      gap: 0.2rem;
    }
    
    .theme-tag {
      font-size: 0.65rem;
      padding: 0.1rem 0.4rem;
    }
    
    .citation-preview {
      padding: 0.75rem;
      margin-top: 0.75rem;
    }
    
    .citation-item {
      gap: 0.4rem;
      margin-bottom: 0.5rem;
    }
    
    .citation-number {
      font-size: 0.75rem;
      min-width: 1rem;
    }
    
    .citation-text {
      font-size: 0.75rem;
    }
  }
  
  @media (max-width: 480px) {
    .event-card {
      padding: 0.875rem;
      border-radius: 8px;
    }
    
    .event-card.research-mode {
      padding: 1rem;
    }
    
    .event-year {
      font-size: 1.2rem;
      padding-left: 0.5rem;
    }
    
    .event-title {
      font-size: 1rem;
    }
    
    .research-mode .event-title {
      font-size: 1.1rem;
    }
    
    .event-image {
      height: 140px;
      border-radius: 6px;
    }
    
    .research-mode .event-image {
      height: 160px;
    }
    
    .event-excerpt {
      font-size: 0.85rem;
    }
    
    .metadata-item {
      font-size: 0.75rem;
    }
  }
</style>