/**
 * Fetch layer for the timeline v2 API. Same-origin, relative URLs from
 * drupalSettings - no hostname sniffing (the old app hardcoded per-host
 * API bases). Bucket and event responses are memoised in tiny Maps;
 * bucket fetches are abortable so fast scrubbing cancels stale loads.
 */

let endpoints = {
  index: '/api/timeline/v2/index',
  bucket: '/api/timeline/v2/events',
  event: '/api/timeline/v2/event',
};

export function configure(config) {
  if (config?.endpoints) {
    endpoints = { ...endpoints, ...config.endpoints };
  }
}

async function getJson(url, options = {}) {
  const response = await fetch(url, { credentials: 'same-origin', ...options });
  if (!response.ok) {
    throw new Error(`${url} -> HTTP ${response.status}`);
  }
  return response.json();
}

export function getSkeleton() {
  return getJson(endpoints.index);
}

const bucketCache = new Map();
const bucketAborts = new Map();

export function getBucket(token) {
  if (bucketCache.has(token)) {
    return bucketCache.get(token);
  }
  const controller = new AbortController();
  bucketAborts.set(token, controller);
  const promise = getJson(`${endpoints.bucket}/${token}`, { signal: controller.signal })
    .finally(() => bucketAborts.delete(token));
  promise.catch(() => bucketCache.delete(token));
  bucketCache.set(token, promise);
  return promise;
}

export function abortBucket(token) {
  bucketAborts.get(token)?.abort();
  bucketAborts.delete(token);
  bucketCache.delete(token);
}

const eventCache = new Map();

export function getEvent(nid) {
  if (eventCache.has(nid)) {
    return eventCache.get(nid);
  }
  const promise = getJson(`${endpoints.event}/${nid}`);
  promise.catch(() => eventCache.delete(nid));
  eventCache.set(nid, promise);
  return promise;
}

/**
 * Maps a year to its bucket token - must mirror
 * TimelineEventService::bucketForDate().
 */
export function bucketForYear(year) {
  return year < 1500 ? 'pre1500' : String(Math.floor(year / 10) * 10);
}
