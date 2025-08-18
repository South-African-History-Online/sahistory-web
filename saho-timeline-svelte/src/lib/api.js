import axios from 'axios';

const API_BASE = import.meta.env.VITE_API_URL || 'https://sahistory-web.ddev.site';
const API_ENDPOINT = '/api/timeline/events';

// Cache for API responses
const cache = new Map();
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

export async function fetchTimelineEvents(limit = 5000) {
  const cacheKey = `events_${limit}`;
  const cached = cache.get(cacheKey);
  
  if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
    console.log('Using cached events');
    return cached.data;
  }
  
  try {
    console.log(`Fetching events from ${API_BASE}${API_ENDPOINT}`);
    const response = await axios.get(`${API_BASE}${API_ENDPOINT}`, {
      params: { limit },
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    const events = response.data.events || [];
    console.log(`Fetched ${events.length} events from API`);
    
    // Cache the response
    cache.set(cacheKey, {
      data: events,
      timestamp: Date.now()
    });
    
    return events;
  } catch (error) {
    console.error('Failed to fetch timeline events:', error);
    throw error;
  }
}

export function processEvents(events) {
  // Sort events by date
  const sorted = [...events].sort((a, b) => {
    const dateA = new Date(a.date);
    const dateB = new Date(b.date);
    return dateA - dateB;
  });
  
  // Group events by year for timeline navigation
  const byYear = {};
  sorted.forEach(event => {
    const year = new Date(event.date).getFullYear();
    if (!byYear[year]) {
      byYear[year] = [];
    }
    byYear[year].push(event);
  });
  
  // Calculate date range
  const dates = sorted.map(e => new Date(e.date)).filter(d => !isNaN(d.getTime()));
  const minDate = dates[0];
  const maxDate = dates[dates.length - 1];
  
  return {
    all: sorted,
    byYear,
    minYear: minDate?.getFullYear() || 1300,
    maxYear: maxDate?.getFullYear() || 2024,
    totalEvents: sorted.length
  };
}

export function searchEvents(events, query) {
  if (!query) return events;
  
  const lowerQuery = query.toLowerCase();
  return events.filter(event => 
    event.title?.toLowerCase().includes(lowerQuery) ||
    event.body?.toLowerCase().includes(lowerQuery) ||
    event.location?.toLowerCase().includes(lowerQuery)
  );
}

export function filterEventsByDateRange(events, startYear, endYear) {
  return events.filter(event => {
    const year = new Date(event.date).getFullYear();
    return year >= startYear && year <= endYear;
  });
}