/**
 * @file
 * SAHO Classroom presentation deck engine.
 *
 * The interaction model from the #436 self-contained prototype
 * (prototype/group-areas-act.html), re-expressed as a Drupal behaviour so it
 * initialises once per deck (via once()) and scopes every query to its own
 * .saho-deck root. This lets several decks share a page without their keyboard,
 * fullscreen and notes state leaking into one another.
 *
 * Controls: Arrows / Space / PageUp-Down / Home / End navigate; F present;
 * S notes; P print. On-screen edge arrows, dots and toolbar buttons mirror
 * every shortcut.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Initialises a single deck.
   *
   * @param {HTMLElement} deck
   *   The .saho-deck root element.
   */
  function initDeck(deck) {
    var slides = Array.prototype.slice.call(deck.querySelectorAll('.slide'));
    var total = slides.length;
    if (!total) {
      return;
    }

    var current = 0;
    var stage = deck.querySelector('.saho-deck__stage');
    var counter = deck.querySelector('.js-counter');
    var live = deck.querySelector('.js-live');
    var progress = deck.querySelector('.js-progress');
    var dotsWrap = deck.querySelector('.js-dots');
    var notesPanel = deck.querySelector('.js-notes-panel');
    var hint = deck.querySelector('.js-hint');
    var btnNotes = deck.querySelector('[data-action="notes"]');
    var btnFull = deck.querySelector('[data-action="full"]');
    var notesOpen = false;

    // Build progress dots as real, keyboard-reachable buttons.
    var dots = slides.map(function (slide, i) {
      var d = document.createElement('button');
      d.className = 'dot';
      d.type = 'button';
      d.setAttribute('role', 'tab');
      d.setAttribute('aria-label', 'Go to slide ' + (i + 1) + ' of ' + total);
      d.addEventListener('click', function () {
        go(i);
      });
      if (dotsWrap) {
        dotsWrap.appendChild(d);
      }
      return d;
    });

    function currentNotesText() {
      var host = slides[current].querySelector('[data-notes]');
      return host ? host.innerHTML : '<h4>Speaker notes</h4><p>No notes for this slide.</p>';
    }

    function renderNotes() {
      if (notesPanel) {
        notesPanel.innerHTML = currentNotesText();
      }
    }

    function go(index) {
      if (index < 0) {
        index = 0;
      }
      if (index > total - 1) {
        index = total - 1;
      }
      slides[current].classList.remove('is-active');
      dots[current].classList.remove('is-active');
      dots[current].removeAttribute('aria-selected');
      current = index;
      slides[current].classList.add('is-active');
      dots[current].classList.add('is-active');
      dots[current].setAttribute('aria-selected', 'true');

      if (counter) {
        counter.textContent = (current + 1) + ' / ' + total;
      }
      if (progress) {
        progress.style.width = ((current + 1) / total * 100) + '%';
      }
      if (live) {
        var heading = slides[current].querySelector('h1, h2');
        live.textContent = 'Slide ' + (current + 1) + ' of ' + total +
          (heading ? ': ' + heading.textContent : '');
      }

      if (notesOpen) {
        renderNotes();
      }
      // Move keyboard focus to the active slide so screen readers land there.
      slides[current].setAttribute('tabindex', '-1');
      slides[current].focus({ preventScroll: true });
    }

    function next() {
      go(current + 1);
    }

    function prev() {
      go(current - 1);
    }

    function toggleNotes(force) {
      notesOpen = (typeof force === 'boolean') ? force : !notesOpen;
      if (notesPanel) {
        notesPanel.classList.toggle('is-open', notesOpen);
      }
      if (btnNotes) {
        btnNotes.setAttribute('aria-pressed', String(notesOpen));
      }
      if (notesOpen) {
        renderNotes();
      }
    }

    function isFullscreen() {
      return document.fullscreenElement === deck ||
        document.webkitFullscreenElement === deck;
    }

    function toggleFullscreen() {
      if (isFullscreen()) {
        (document.exitFullscreen || document.webkitExitFullscreen).call(document);
      }
      else {
        (deck.requestFullscreen || deck.webkitRequestFullscreen).call(deck);
      }
    }

    function syncFullscreenBtn() {
      if (!btnFull) {
        return;
      }
      var on = isFullscreen();
      btnFull.setAttribute('aria-pressed', String(on));
      if (btnFull.firstChild) {
        btnFull.firstChild.textContent = on ? 'Exit ' : 'Present ';
      }
    }
    document.addEventListener('fullscreenchange', syncFullscreenBtn);
    document.addEventListener('webkitfullscreenchange', syncFullscreenBtn);

    // Wire every control that carries a data-action, scoped to this deck.
    deck.querySelectorAll('[data-action="next"]').forEach(function (b) {
      b.addEventListener('click', next);
    });
    deck.querySelectorAll('[data-action="prev"]').forEach(function (b) {
      b.addEventListener('click', prev);
    });
    deck.querySelectorAll('[data-action="print"]').forEach(function (b) {
      b.addEventListener('click', function () {
        window.print();
      });
    });
    if (btnNotes) {
      btnNotes.addEventListener('click', function () {
        toggleNotes();
      });
    }
    if (btnFull) {
      btnFull.addEventListener('click', toggleFullscreen);
    }

    // Keyboard shortcuts only fire for the deck the user is actually in.
    document.addEventListener('keydown', function (e) {
      var t = e.target;
      if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA')) {
        return;
      }
      if (!isFullscreen() && !deck.contains(document.activeElement)) {
        return;
      }
      switch (e.key) {
        case 'ArrowRight':
        case 'ArrowDown':
        case 'PageDown':
        case ' ':
          e.preventDefault();
          next();
          break;

        case 'ArrowLeft':
        case 'ArrowUp':
        case 'PageUp':
          e.preventDefault();
          prev();
          break;

        case 'Home':
          e.preventDefault();
          go(0);
          break;

        case 'End':
          e.preventDefault();
          go(total - 1);
          break;

        case 'f':
        case 'F':
          e.preventDefault();
          toggleFullscreen();
          break;

        case 's':
        case 'S':
          e.preventDefault();
          toggleNotes();
          break;

        case 'p':
        case 'P':
          e.preventDefault();
          window.print();
          break;

        default:
          break;
      }
    });

    // Touch / swipe navigation for tablets.
    var startX = null;
    if (stage) {
      stage.addEventListener('touchstart', function (e) {
        startX = e.changedTouches[0].clientX;
      }, { passive: true });
      stage.addEventListener('touchend', function (e) {
        if (startX === null) {
          return;
        }
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 50) {
          if (dx < 0) {
            next();
          }
          else {
            prev();
          }
        }
        startX = null;
      }, { passive: true });
    }

    // Fade out the transient keyboard hint after a few seconds.
    if (hint) {
      window.setTimeout(function () {
        hint.style.transition = 'opacity .5s';
        hint.style.opacity = '0';
        window.setTimeout(function () {
          hint.style.display = 'none';
        }, 600);
      }, 4200);
    }

    go(current);
  }

  Drupal.behaviors.sahoClassroomDeck = {
    attach: function (context) {
      once('saho-classroom-deck', '.saho-deck', context).forEach(initDeck);
    }
  };
})(Drupal, once);
