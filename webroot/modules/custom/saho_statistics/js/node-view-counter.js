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
      // Deduplicate within the browser session so page reloads do not
      // double-count (mirrors the Statistics module's JS behaviour).
      var sessionKey = 'saho_nv_' + nid;

      once('saho-node-counter', 'body', context).forEach(function () {
        if (sessionStorage.getItem(sessionKey)) {
          return;
        }
        sessionStorage.setItem(sessionKey, '1');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader(
          'Content-Type',
          'application/x-www-form-urlencoded'
        );
        // Fire-and-forget; errors are silently swallowed so they never
        // affect the user experience.
        xhr.send('nid=' + encodeURIComponent(nid));
      });
    }
  };

})(Drupal, drupalSettings, once);
