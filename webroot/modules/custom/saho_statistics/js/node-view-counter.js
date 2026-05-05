/**
 * @file
 * Fires an AJAX POST to the saho_statistics node-view counter endpoint.
 *
 * Replaces the Statistics module's statistics.php tracking while keeping
 * the page fully served from Drupal's page cache. The request is sent
 * once per browser session per node to avoid inflating counts on reload.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Returns true if sessionStorage is available in this browser context.
   *
   * sessionStorage may be unavailable in private browsing (Safari ITP) or
   * when the browser blocks storage access. Wrapping the probe in a
   * try/catch prevents unhandled exceptions from breaking the page.
   *
   * @return {boolean}
   */
  function sessionStorageAvailable() {
    try {
      var key = '__saho_ss_test__';
      sessionStorage.setItem(key, '1');
      sessionStorage.removeItem(key);
      return true;
    }
    catch (e) {
      return false;
    }
  }

  Drupal.behaviors.sahoNodeViewCounter = {
    attach: function (context, settings) {
      // Guard: only run when our settings are present.
      if (
        !settings.sahoNodeCounter ||
        !settings.sahoNodeCounter.nid ||
        !settings.sahoNodeCounter.url
      ) {
        return;
      }

      var nid = settings.sahoNodeCounter.nid;
      var url = settings.sahoNodeCounter.url;
      var sessionKey = 'saho_nv_' + nid;
      var useSession = sessionStorageAvailable();

      once('saho-node-counter', 'body', context).forEach(function () {
        // Deduplicate within the browser session so page reloads do not
        // double-count (mirrors the Statistics module's JS behaviour).
        if (useSession && sessionStorage.getItem(sessionKey)) {
          return;
        }

        if (useSession) {
          sessionStorage.setItem(sessionKey, '1');
        }

        // Prefer navigator.sendBeacon() — designed for fire-and-forget
        // analytics, reliably fires even when the page is being unloaded.
        if (navigator.sendBeacon) {
          var data = new FormData();
          data.append('nid', nid);
          navigator.sendBeacon(url, data);
        }
        else {
          // Fallback for browsers that do not support sendBeacon.
          var xhr = new XMLHttpRequest();
          xhr.open('POST', url, true);
          xhr.setRequestHeader(
            'Content-Type',
            'application/x-www-form-urlencoded'
          );
          xhr.send('nid=' + encodeURIComponent(nid));
        }
      });
    }
  };

})(Drupal, drupalSettings, once);
