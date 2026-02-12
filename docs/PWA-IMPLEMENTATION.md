# Progressive Web App (PWA) Implementation for SAHO
## Offline-First Mobile Experience

**Priority:** P2 (Medium-High)
**Estimated Time:** 3 hours
**Risk Level:** 🟢 Low (additive only)

---

## Goals

- Add to home screen capability
- Offline article reading
- Fast loading with service worker caching
- App-like mobile experience
- Push notifications (future)

---

## Step 1: Install PWA Module

```bash
# Install Drupal PWA module
composer require drupal/pwa
ddev drush en pwa -y

# Configure
ddev drush config:set pwa.config manifest.name "South African History Online" -y
ddev drush config:set pwa.config manifest.short_name "SAHO" -y
ddev drush config:set pwa.config manifest.description "Towards a People's History" -y
ddev drush config:set pwa.config manifest.theme_color "#990000" -y
ddev drush config:set pwa.config manifest.background_color "#ffffff" -y

# Export config
ddev drush config:export -y
```

---

## Step 2: Create App Icons

```bash
# Generate icons from logo
cd webroot/themes/custom/saho/

# Create icon sizes (using ImageMagick)
convert logo.svg -resize 192x192 icon-192.png
convert logo.svg -resize 512x512 icon-512.png
convert logo.svg -resize 180x180 apple-touch-icon.png

# For maskable icons (safe area)
convert logo.svg -resize 512x512 -background white -gravity center -extent 640x640 icon-512-maskable.png
```

---

## Step 3: Create manifest.json

**File:** `webroot/manifest.json`

```json
{
  "name": "South African History Online",
  "short_name": "SAHO",
  "description": "Towards a People's History of South Africa",
  "start_url": "/",
  "scope": "/",
  "display": "standalone",
  "orientation": "portrait-primary",
  "theme_color": "#990000",
  "background_color": "#ffffff",
  "lang": "en",
  "dir": "ltr",
  "categories": ["education", "history", "reference"],
  "icons": [
    {
      "src": "/themes/custom/saho/icon-192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "/themes/custom/saho/icon-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "/themes/custom/saho/icon-512-maskable.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "maskable"
    }
  ],
  "screenshots": [
    {
      "src": "/themes/custom/saho/screenshot-mobile.png",
      "sizes": "540x720",
      "type": "image/png",
      "form_factor": "narrow"
    },
    {
      "src": "/themes/custom/saho/screenshot-desktop.png",
      "sizes": "1920x1080",
      "type": "image/png",
      "form_factor": "wide"
    }
  ],
  "shortcuts": [
    {
      "name": "Timeline",
      "short_name": "Timeline",
      "url": "/saho-timeline",
      "icons": [
        {
          "src": "/themes/custom/saho/icon-timeline.png",
          "sizes": "96x96",
          "type": "image/png"
        }
      ]
    },
    {
      "name": "Search",
      "short_name": "Search",
      "url": "/search",
      "icons": [
        {
          "src": "/themes/custom/saho/icon-search.png",
          "sizes": "96x96",
          "type": "image/png"
        }
      ]
    }
  ],
  "related_applications": [],
  "prefer_related_applications": false
}
```

---

## Step 4: Create Service Worker

**File:** `webroot/themes/custom/saho/js/service-worker.js`

```javascript
/**
 * SAHO Service Worker
 * Offline-first caching strategy for articles and images
 */

const CACHE_VERSION = 'saho-v1';
const CACHE_NAME = `saho-cache-${CACHE_VERSION}`;

// Resources to cache immediately
const PRECACHE_URLS = [
  '/',
  '/themes/custom/saho/dist/css/main.css',
  '/themes/custom/saho/dist/js/main.script.js',
  '/themes/custom/saho/logo.svg',
  '/offline',
];

// Cache on install
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

// Clean old caches on activate
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames
            .filter(name => name.startsWith('saho-') && name !== CACHE_NAME)
            .map(name => caches.delete(name))
        );
      })
      .then(() => self.clients.claim())
  );
});

// Fetch strategy: Network first, fallback to cache
self.addEventListener('fetch', event => {
  const { request } = event;

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip admin, user, and API routes
  if (request.url.includes('/admin/') ||
      request.url.includes('/user/') ||
      request.url.includes('/api/')) {
    return;
  }

  event.respondWith(
    fetch(request)
      .then(response => {
        // Clone response to cache
        const responseClone = response.clone();

        // Cache successful responses
        if (response.status === 200) {
          caches.open(CACHE_NAME).then(cache => {
            cache.put(request, responseClone);
          });
        }

        return response;
      })
      .catch(() => {
        // Network failed, try cache
        return caches.match(request)
          .then(cachedResponse => {
            if (cachedResponse) {
              return cachedResponse;
            }

            // Show offline page for HTML requests
            if (request.headers.get('accept').includes('text/html')) {
              return caches.match('/offline');
            }

            // Return generic offline response
            return new Response('Offline', {
              status: 503,
              statusText: 'Service Unavailable',
              headers: new Headers({
                'Content-Type': 'text/plain'
              })
            });
          });
      })
  );
});

// Background sync (future enhancement)
self.addEventListener('sync', event => {
  if (event.tag === 'sync-bookmarks') {
    event.waitUntil(syncBookmarks());
  }
});

// Push notifications (future enhancement)
self.addEventListener('push', event => {
  const data = event.data.json();
  const options = {
    body: data.body,
    icon: '/themes/custom/saho/icon-192.png',
    badge: '/themes/custom/saho/icon-72.png',
    vibrate: [200, 100, 200],
    data: {
      url: data.url
    }
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
});
```

---

## Step 5: Register Service Worker

**File:** `webroot/themes/custom/saho/js/pwa-register.js`

```javascript
/**
 * @file
 * PWA Service Worker Registration
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.sahoPWA = {
    attach: function (context, settings) {
      // Only register service worker once
      if (context !== document) {
        return;
      }

      // Check if service workers are supported
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/themes/custom/saho/js/service-worker.js')
            .then(registration => {
              console.log('✅ Service Worker registered:', registration.scope);

              // Check for updates every hour
              setInterval(() => {
                registration.update();
              }, 3600000);

              // Prompt user to reload when new version available
              registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;

                newWorker.addEventListener('statechange', () => {
                  if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    // New version available
                    if (confirm('A new version of SAHO is available. Reload to update?')) {
                      window.location.reload();
                    }
                  }
                });
              });
            })
            .catch(error => {
              console.error('❌ Service Worker registration failed:', error);
            });
        });
      }

      // Prompt to install PWA (iOS Safari, Android Chrome)
      let deferredPrompt;

      window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        // Show custom install button
        const installBanner = document.createElement('div');
        installBanner.className = 'pwa-install-banner';
        installBanner.innerHTML = `
          <div class="pwa-install-content">
            <p>Install SAHO for offline access and faster loading!</p>
            <button id="pwa-install-btn" class="btn btn-primary">Install App</button>
            <button id="pwa-dismiss-btn" class="btn btn-outline-secondary">Not Now</button>
          </div>
        `;

        document.body.appendChild(installBanner);

        document.getElementById('pwa-install-btn').addEventListener('click', () => {
          deferredPrompt.prompt();
          deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
              console.log('✅ PWA installed');
            }
            deferredPrompt = null;
            installBanner.remove();
          });
        });

        document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
          installBanner.remove();
        });
      });

      // Detect when app is installed
      window.addEventListener('appinstalled', () => {
        console.log('✅ SAHO PWA installed successfully');
        // Track with analytics
        if (typeof gtag === 'function') {
          gtag('event', 'pwa_installed', {
            'event_category': 'engagement',
            'event_label': 'PWA Installation'
          });
        }
      });
    }
  };

})(Drupal);
```

---

## Step 6: Add to Libraries

**File:** `webroot/themes/custom/saho/saho.libraries.yml`

```yaml
pwa:
  js:
    js/pwa-register.js: { attributes: { defer: true } }
  css:
    theme:
      css/pwa.css: {}
  dependencies:
    - core/drupal
```

**File:** `webroot/themes/custom/saho/css/pwa.css`

```css
/**
 * PWA Install Banner Styles
 */

.pwa-install-banner {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(135deg, #990000 0%, #770000 100%);
  color: white;
  padding: 1rem;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.3);
  z-index: 9999;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.pwa-install-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.pwa-install-content p {
  margin: 0;
  font-weight: 600;
}

.pwa-install-content button {
  white-space: nowrap;
}

@media (max-width: 768px) {
  .pwa-install-content {
    flex-direction: column;
    text-align: center;
  }
}
```

---

## Step 7: Create Offline Page

**File:** `webroot/themes/custom/saho/templates/page/page--offline.html.twig`

```twig
{#
/**
 * @file
 * Offline page template
 */
#}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Offline - SAHO</title>
  <style>
    body {
      font-family: 'Open Sans', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
      text-align: center;
      padding: 2rem;
    }
    .offline-container {
      max-width: 500px;
    }
    .offline-icon {
      font-size: 5rem;
      margin-bottom: 1rem;
    }
    h1 {
      color: #990000;
      margin-bottom: 1rem;
    }
    p {
      color: #666;
      line-height: 1.6;
      margin-bottom: 2rem;
    }
    .retry-btn {
      background: #990000;
      color: white;
      border: none;
      padding: 0.75rem 2rem;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .retry-btn:hover {
      background: #770000;
    }
  </style>
</head>
<body>
  <div class="offline-container">
    <div class="offline-icon">📡</div>
    <h1>You're Offline</h1>
    <p>
      It looks like you've lost your internet connection.
      Some content may still be available from your cache.
    </p>
    <button class="retry-btn" onclick="window.location.reload()">
      Try Again
    </button>
  </div>
</body>
</html>
```

Create corresponding node:
```bash
ddev drush generate:content 1 --types=page --title="Offline"
# Set URL alias to /offline
```

---

## Step 8: Update HTML Head

**File:** `webroot/themes/custom/saho/templates/system/html.html.twig`

```twig
<head>
  {# ... existing head content ... #}

  {# PWA Manifest #}
  <link rel="manifest" href="/manifest.json">

  {# Theme color for browser UI #}
  <meta name="theme-color" content="#990000">

  {# iOS specific #}
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="SAHO">
  <link rel="apple-touch-icon" href="/themes/custom/saho/apple-touch-icon.png">

  {# Android specific #}
  <meta name="mobile-web-app-capable" content="yes">
</head>
```

---

## Step 9: Test PWA

### Local Testing

```bash
# Build production assets
cd webroot/themes/custom/saho
npm run production

# Clear cache
ddev drush cr

# Test manifest
curl http://sahistory-web.ddev.site/manifest.json

# Test service worker
# Open: http://sahistory-web.ddev.site
# Chrome DevTools > Application > Service Workers
# Verify registration
```

### Lighthouse Audit

```bash
# Install Lighthouse CLI
npm install -g lighthouse

# Run PWA audit
lighthouse https://sahistory-web.ddev.site \
  --only-categories=pwa \
  --output=html \
  --output-path=./pwa-audit.html

# Open report
open pwa-audit.html
```

**Target Scores:**
- ✅ Fast and reliable on slow connections
- ✅ Installable
- ✅ PWA Optimized
- ✅ Provides a custom offline page

---

## Step 10: Monitor & Iterate

### Analytics Tracking

```javascript
// Track PWA installations
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.ready.then(() => {
    gtag('event', 'pwa_ready', {
      'event_category': 'engagement'
    });
  });
}

// Track offline usage
window.addEventListener('online', () => {
  gtag('event', 'back_online', {
    'event_category': 'connectivity'
  });
});

window.addEventListener('offline', () => {
  gtag('event', 'went_offline', {
    'event_category': 'connectivity'
  });
});
```

### Metrics to Track

- Install rate (prompts shown vs. installs)
- Offline page views
- Service worker cache hit rate
- App launch vs. browser visits
- Time spent in PWA vs. browser

---

## Rollback

If PWA causes issues:

```bash
# Disable PWA module
ddev drush pmu pwa -y

# Remove service worker registration
# Comment out in saho.libraries.yml:
# pwa:
#   js:
#     js/pwa-register.js: {}

# Clear cache
ddev drush cr

# Service worker will be unregistered on next visit
```

---

## Future Enhancements

### Phase 2 Features

1. **Background Sync**
   - Save articles for offline reading
   - Sync bookmarks when back online

2. **Push Notifications**
   - New article alerts
   - This Day in History reminders
   - Event notifications

3. **Advanced Caching**
   - Predictive prefetching
   - Adaptive caching based on user behavior

4. **Share Target API**
   - Share to SAHO from other apps
   - Create bookmarks via share

---

## Resources

- [PWA Checklist](https://web.dev/pwa-checklist/)
- [Workbox (Advanced SW)](https://developers.google.com/web/tools/workbox)
- [PWA Badge](https://web.dev/badging-api/)
- [Drupal PWA Module](https://www.drupal.org/project/pwa)

---

**Status:** Ready to implement
**Next Steps:** Create app icons, test locally, deploy to staging
