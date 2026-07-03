/*
 * SAHO Classroom - presentation engine.
 *
 * Standalone, dependency-free deck controller factored out of the Classroom 2.0
 * proof-of-concept prototype (prototype/group-areas-act.html, issue #436) and
 * hardened for reuse. No external libraries; runs from file:// fully offline.
 *
 * It drives whatever slide markup is present, so it is deck-agnostic: it counts
 * .slide sections at runtime, builds keyboard-reachable progress dots, and
 * mirrors every shortcut on the on-screen controls.
 *
 * Controls:
 *   Right / Down / Space / PageDown  next slide
 *   Left / Up / PageUp               previous slide
 *   Home / End                       first / last slide
 *   F                                toggle fullscreen present mode
 *   S                                toggle speaker notes
 *   P                                print / save as PDF
 *   Esc                              leave fullscreen (browser default)
 *
 * Expected DOM (produced by render.php or a Twig template):
 *   #deck #stage #progress #dots #counter #live #notesPanel #hint
 *   #prev #next #btnPrev #btnNext #btnNotes #btnFull #btnPrint
 *   .slide[id="slide-N"] each optionally containing [data-notes].
 */
(function () {
  "use strict";

  var slides = Array.prototype.slice.call(document.querySelectorAll(".slide"));
  var total = slides.length;
  var current = 0;

  if (!total) { return; }

  var stage = document.getElementById("stage");
  var counter = document.getElementById("counter");
  var live = document.getElementById("live");
  var progress = document.getElementById("progress");
  var dotsWrap = document.getElementById("dots");
  var notesPanel = document.getElementById("notesPanel");
  var hint = document.getElementById("hint");
  var deck = document.getElementById("deck");

  var btnNotes = document.getElementById("btnNotes");
  var btnFull = document.getElementById("btnFull");
  var notesOpen = false;

  // Build progress dots as real buttons (keyboard reachable).
  var dots = slides.map(function (slide, i) {
    var d = document.createElement("button");
    d.className = "dot";
    d.type = "button";
    d.setAttribute("role", "tab");
    d.setAttribute("aria-label", "Go to slide " + (i + 1) + " of " + total);
    d.addEventListener("click", function () { go(i); });
    if (dotsWrap) { dotsWrap.appendChild(d); }
    return d;
  });

  function currentNotesText() {
    var host = slides[current].querySelector("[data-notes]");
    return host ? host.innerHTML : "<h4>Speaker notes</h4><p>No notes for this slide.</p>";
  }

  function renderNotes() {
    if (notesPanel) { notesPanel.innerHTML = currentNotesText(); }
  }

  function headingText() {
    var h = slides[current].querySelector("h1, h2");
    return h ? h.textContent : "";
  }

  function go(index) {
    if (index < 0) { index = 0; }
    if (index > total - 1) { index = total - 1; }
    slides[current].classList.remove("is-active");
    dots[current].classList.remove("is-active");
    dots[current].removeAttribute("aria-selected");
    current = index;
    slides[current].classList.add("is-active");
    dots[current].classList.add("is-active");
    dots[current].setAttribute("aria-selected", "true");

    if (counter) { counter.textContent = (current + 1) + " / " + total; }
    if (progress) { progress.style.width = ((current + 1) / total * 100) + "%"; }
    if (live) {
      live.textContent = "Slide " + (current + 1) + " of " + total + ": " + headingText();
    }
    // Keep the URL hash in step for deep links / reload restore.
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, "", "#slide-" + (current + 1));
    }

    if (notesOpen) { renderNotes(); }
    // Move keyboard focus to the active slide so screen readers land there.
    slides[current].setAttribute("tabindex", "-1");
    slides[current].focus({ preventScroll: true });
  }

  function next() { go(current + 1); }
  function prev() { go(current - 1); }

  function toggleNotes(force) {
    notesOpen = (typeof force === "boolean") ? force : !notesOpen;
    if (notesPanel) { notesPanel.classList.toggle("is-open", notesOpen); }
    if (btnNotes) { btnNotes.setAttribute("aria-pressed", String(notesOpen)); }
    if (notesOpen) { renderNotes(); }
  }

  function isFullscreen() {
    return document.fullscreenElement || document.webkitFullscreenElement;
  }

  function toggleFullscreen() {
    if (isFullscreen()) {
      var exit = document.exitFullscreen || document.webkitExitFullscreen;
      if (exit) { exit.call(document); }
    } else {
      var el = deck || document.documentElement;
      var request = el.requestFullscreen || el.webkitRequestFullscreen;
      if (request) { request.call(el); }
    }
  }

  function syncFullscreenBtn() {
    if (!btnFull) { return; }
    var on = !!isFullscreen();
    btnFull.setAttribute("aria-pressed", String(on));
    if (btnFull.firstChild) {
      btnFull.firstChild.textContent = on ? "Exit " : "Present ";
    }
  }
  document.addEventListener("fullscreenchange", syncFullscreenBtn);
  document.addEventListener("webkitfullscreenchange", syncFullscreenBtn);

  // Wire buttons (each guarded so a trimmed toolbar still works).
  function on(id, fn) {
    var node = document.getElementById(id);
    if (node) { node.addEventListener("click", fn); }
  }
  on("next", next);
  on("prev", prev);
  on("btnNext", next);
  on("btnPrev", prev);
  on("btnPrint", function () { window.print(); });
  if (btnNotes) { btnNotes.addEventListener("click", function () { toggleNotes(); }); }
  if (btnFull) { btnFull.addEventListener("click", toggleFullscreen); }

  // Keyboard shortcuts.
  document.addEventListener("keydown", function (e) {
    // Ignore when typing in a field (future-proofing).
    var t = e.target;
    if (t && (t.tagName === "INPUT" || t.tagName === "TEXTAREA")) { return; }
    switch (e.key) {
      case "ArrowRight": case "ArrowDown": case "PageDown": case " ":
        e.preventDefault(); next(); break;
      case "ArrowLeft": case "ArrowUp": case "PageUp":
        e.preventDefault(); prev(); break;
      case "Home": e.preventDefault(); go(0); break;
      case "End": e.preventDefault(); go(total - 1); break;
      case "f": case "F": e.preventDefault(); toggleFullscreen(); break;
      case "s": case "S": e.preventDefault(); toggleNotes(); break;
      case "p": case "P": e.preventDefault(); window.print(); break;
      default: break;
    }
  });

  // Touch / swipe navigation for tablets.
  if (stage) {
    var startX = null;
    stage.addEventListener("touchstart", function (e) {
      startX = e.changedTouches[0].clientX;
    }, { passive: true });
    stage.addEventListener("touchend", function (e) {
      if (startX === null) { return; }
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 50) { dx < 0 ? next() : prev(); }
      startX = null;
    }, { passive: true });
  }

  // Deep link + restore position via the URL hash (offline friendly).
  function fromHash() {
    var m = /slide-(\d+)/.exec(window.location.hash);
    if (m) {
      var n = parseInt(m[1], 10) - 1;
      if (n >= 0 && n < total) { current = n; }
    }
  }
  fromHash();

  // Fade out the keyboard hint after a few seconds.
  if (hint) {
    setTimeout(function () {
      hint.style.transition = "opacity .5s";
      hint.style.opacity = "0";
      setTimeout(function () { hint.style.display = "none"; }, 600);
    }, 4200);
  }

  go(current);
})();
