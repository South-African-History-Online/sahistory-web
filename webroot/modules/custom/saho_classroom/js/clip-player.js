/**
 * @file
 * Poster-first classroom clip player (#428).
 *
 * The poster is the surface; the video element stays hidden (and unloaded
 * by the browser) until engagement. A muted inline preview may start when
 * the player is at least half visible - but never for visitors who prefer
 * reduced motion or sit on a metered/slow connection. Clicking the poster
 * reveals the native player with controls and sound.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Whether ambient (muted) autoplay is acceptable for this visitor.
   *
   * @return {boolean}
   *   FALSE on reduced-motion, save-data or slow connections.
   */
  function ambientPlaybackAllowed() {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return false;
    }
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (connection) {
      if (connection.saveData) {
        return false;
      }
      if (typeof connection.effectiveType === 'string' && /(^|-)2g$/.test(connection.effectiveType)) {
        return false;
      }
    }
    return true;
  }

  Drupal.behaviors.sahoClipPlayer = {
    attach(context) {
      once('saho-clip', '[data-saho-clip]', context).forEach((player) => {
        const poster = player.querySelector('[data-clip-play]');
        const wrap = player.querySelector('.saho-clip__video');
        const video = wrap ? wrap.querySelector('video') : null;
        if (!wrap || !video) {
          return;
        }

        // Poster-first: nothing preloads until engagement.
        video.preload = 'none';
        video.controls = true;
        video.removeAttribute('autoplay');

        const engage = () => {
          if (poster) {
            poster.hidden = true;
          }
          wrap.hidden = false;
          video.muted = false;
          video.play().catch(() => {
            // Playback rejection (browser policy) leaves the native
            // controls visible - the visitor presses play.
          });
        };

        if (poster) {
          poster.addEventListener('click', engage);
        }
        else {
          wrap.hidden = false;
        }

        // Ambient preview: muted, only while at least half visible, and
        // never on reduced-motion / metered connections.
        if (poster && ambientPlaybackAllowed() && 'IntersectionObserver' in window) {
          const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
              if (poster.hidden) {
                // Engaged: stop observing; pause management is native.
                observer.disconnect();
                return;
              }
              if (entry.intersectionRatio >= 0.5) {
                wrap.hidden = false;
                video.muted = true;
                video.play().catch(() => {});
              }
              else {
                video.pause();
              }
            });
          }, { threshold: [0, 0.5] });
          observer.observe(player);
        }
      });
    },
  };
})(Drupal, once);
