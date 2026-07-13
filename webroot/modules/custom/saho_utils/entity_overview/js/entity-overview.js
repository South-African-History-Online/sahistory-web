/**
 * @file
 * Entity Overview block: reader-side display-mode toggle.
 *
 * Grid / Compact / List buttons swap the block's display-mode class and
 * remember the reader's pick per block in localStorage. No AJAX.
 */

(function (Drupal, once) {
  'use strict';

  var MODES = ['default', 'compact', 'full-width'];

  function storageKey(blockId) {
    return 'saho.entityOverview.' + blockId + '.mode';
  }

  function applyMode(block, mode) {
    if (MODES.indexOf(mode) === -1) {
      return;
    }
    MODES.forEach(function (m) {
      block.classList.toggle('entity-overview--' + m, m === mode);
    });
    block.setAttribute('data-display-mode', mode);
    block.querySelectorAll('.entity-overview-toggle__btn').forEach(function (btn) {
      btn.setAttribute('aria-pressed', btn.dataset.mode === mode ? 'true' : 'false');
    });
  }

  Drupal.behaviors.entityOverview = {
    attach: function (context) {
      once('entity-overview', '.entity-overview-block', context).forEach(function (block) {
        var blockId = block.getAttribute('data-block-id') || '';
        var buttons = block.querySelectorAll('.entity-overview-toggle__btn');
        if (!buttons.length) {
          return;
        }
        var saved = null;
        try {
          saved = window.localStorage.getItem(storageKey(blockId));
        }
        catch (e) {
          // Storage unavailable (private mode): fall back to the configured mode.
        }
        if (saved) {
          applyMode(block, saved);
        }
        buttons.forEach(function (btn) {
          btn.addEventListener('click', function () {
            applyMode(block, btn.dataset.mode);
            try {
              window.localStorage.setItem(storageKey(blockId), btn.dataset.mode);
            }
            catch (e) {
              // Best effort only.
            }
          });
        });
      });
    }
  };

})(Drupal, once);
