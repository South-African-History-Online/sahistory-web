/**
 * @file
 * Interactivity for the contributor dashboard.
 *
 * Count-up numbers, progress-bar fills, tier-up celebrations (confetti +
 * toast), role-badge tooltips and the Konami-code vault. Everything degrades
 * gracefully: server-rendered markup is complete without JS, and animation
 * honours prefers-reduced-motion.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var CONFETTI_COLORS = ['#990000', '#b88a2e', '#e2ded6', '#4c5259'];

  var TIER_RANK = { bronze: 1, silver: 2, gold: 3 };

  var KONAMI = [
    'ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown',
    'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a',
  ];

  /**
   * Safe localStorage JSON access (pattern borrowed from theme saho-utils).
   */
  var storage = {
    get: function (key) {
      try {
        var raw = window.localStorage.getItem(key);
        return raw === null ? null : JSON.parse(raw);
      }
      catch (e) {
        return null;
      }
    },
    set: function (key, value) {
      try {
        window.localStorage.setItem(key, JSON.stringify(value));
        return true;
      }
      catch (e) {
        return false;
      }
    },
  };

  /**
   * Mirrors DashboardBuilder::compact() so animation frames match the
   * server-rendered final text.
   */
  function formatCompact(n) {
    if (n >= 1000000) {
      var m = Math.round(n / 100000) / 10;
      return (m % 1 === 0 ? String(m) : m.toFixed(1)) + 'M';
    }
    if (n >= 10000) {
      return Math.round(n / 1000).toLocaleString('en-US') + 'k';
    }
    return n.toLocaleString('en-US');
  }

  /**
   * Eases a number from 0 to target inside the element (rAF, ease-out cubic).
   */
  function animateCount(el, target, compact) {
    var duration = 1200;
    var start = null;
    var format = compact ? formatCompact : function (n) {
      return n.toLocaleString('en-US');
    };
    function frame(ts) {
      if (start === null) {
        start = ts;
      }
      var progress = Math.min((ts - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = format(Math.round(target * eased));
      if (progress < 1) {
        window.requestAnimationFrame(frame);
      }
    }
    window.requestAnimationFrame(frame);
  }

  /**
   * Animates count-ups and progress bars as they scroll into view.
   */
  function initReveals(root) {
    var counters = root.querySelectorAll('[data-countup]');
    var bars = root.querySelectorAll('.saho-medallion__bar[data-progress]');
    if (REDUCED || !('IntersectionObserver' in window)) {
      return;
    }
    // Prime the bars at zero so the CSS width transition has a run-up.
    bars.forEach(function (bar) {
      bar.style.width = '0%';
    });
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        var el = entry.target;
        observer.unobserve(el);
        if (el.hasAttribute('data-countup')) {
          var target = Number.parseInt(el.getAttribute('data-countup'), 10);
          if (!Number.isNaN(target)) {
            animateCount(el, target, el.hasAttribute('data-countup-compact'));
          }
        }
        else {
          el.style.width = el.getAttribute('data-progress') + '%';
        }
      });
    }, { threshold: 0.4 });
    counters.forEach(function (el) {
      observer.observe(el);
    });
    bars.forEach(function (el) {
      observer.observe(el);
    });
  }

  /**
   * Initializes Bootstrap tooltips on role badges, if Tooltip is available.
   *
   * The theme's one-shot initializer runs at page load only, so re-init
   * defensively for anything it may have missed.
   */
  function initTooltips(root) {
    if (!window.bootstrap || !window.bootstrap.Tooltip) {
      return;
    }
    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      if (!window.bootstrap.Tooltip.getInstance(el)) {
        new window.bootstrap.Tooltip(el);
      }
    });
  }

  /**
   * Shows a small self-made toast (the theme bundle does not ship
   * bootstrap's Toast component).
   */
  function showToast(message, delay) {
    window.setTimeout(function () {
      var toast = document.createElement('div');
      toast.className = 'saho-dash-toast';
      toast.setAttribute('role', 'status');
      toast.textContent = message;
      document.body.appendChild(toast);
      window.requestAnimationFrame(function () {
        toast.classList.add('saho-dash-toast--visible');
      });
      window.setTimeout(function () {
        toast.classList.remove('saho-dash-toast--visible');
        window.setTimeout(function () {
          toast.remove();
        }, 400);
      }, 5000);
    }, delay || 0);
  }

  /**
   * Fires a short canvas confetti burst in the SAHO palette.
   */
  function confettiBurst(count) {
    if (REDUCED) {
      return;
    }
    var canvas = document.createElement('canvas');
    canvas.className = 'saho-dash-confetti';
    canvas.setAttribute('aria-hidden', 'true');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    document.body.appendChild(canvas);
    var ctx = canvas.getContext('2d');
    var particles = [];
    var total = count || 90;
    for (var i = 0; i < total; i++) {
      particles.push({
        x: canvas.width * (0.3 + Math.random() * 0.4),
        y: -20 - Math.random() * canvas.height * 0.2,
        vx: (Math.random() - 0.5) * 4,
        vy: 2 + Math.random() * 3,
        size: 5 + Math.random() * 6,
        color: CONFETTI_COLORS[i % CONFETTI_COLORS.length],
        rotation: Math.random() * Math.PI,
        spin: (Math.random() - 0.5) * 0.2,
      });
    }
    var start = null;
    function frame(ts) {
      if (start === null) {
        start = ts;
      }
      var elapsed = ts - start;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      particles.forEach(function (p) {
        p.x += p.vx;
        p.y += p.vy;
        p.vy += 0.06;
        p.rotation += p.spin;
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.rotation);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = Math.max(0, 1 - elapsed / 1800);
        ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
        ctx.restore();
      });
      if (elapsed < 1800) {
        window.requestAnimationFrame(frame);
      }
      else {
        canvas.remove();
      }
    }
    window.requestAnimationFrame(frame);
  }

  /**
   * Celebrates newly attained tiers since the visitor's last visit.
   *
   * Baseline lives in localStorage per uid. The very first visit seeds
   * silently, so long-standing gold holders do not get a confetti storm
   * the day this ships.
   */
  function initTierCelebration(settings) {
    var key = 'saho-dashboard-tiers-' + settings.uid;
    var current = settings.tiers || {};
    var stored = storage.get(key);
    if (stored === null || typeof stored !== 'object') {
      storage.set(key, current);
      return;
    }
    var gained = [];
    Object.keys(current).forEach(function (id) {
      var now = TIER_RANK[current[id]] || 0;
      var before = TIER_RANK[stored[id]] || 0;
      if (now > before && settings.names && settings.names[id]) {
        gained.push({ name: settings.names[id], tier: current[id] });
      }
    });
    storage.set(key, current);
    if (!gained.length) {
      return;
    }
    confettiBurst();
    gained.forEach(function (g, index) {
      var tier = g.tier.charAt(0).toUpperCase() + g.tier.slice(1);
      showToast(g.name + ' - ' + tier + ' attained', index * 1200);
    });
  }

  /**
   * Wires the Konami code to the hidden vault card.
   */
  function initKonami(root) {
    var vault = root.querySelector('[data-saho-easter]');
    if (!vault) {
      return;
    }
    var position = 0;
    function onKeydown(event) {
      var expected = KONAMI[position];
      var key = event.key.length === 1 ? event.key.toLowerCase() : event.key;
      position = key === expected ? position + 1 : (key === KONAMI[0] ? 1 : 0);
      if (position === KONAMI.length) {
        document.removeEventListener('keydown', onKeydown);
        vault.removeAttribute('hidden');
        vault.scrollIntoView({ behavior: REDUCED ? 'auto' : 'smooth', block: 'center' });
        vault.focus({ preventScroll: true });
        confettiBurst(40);
      }
    }
    document.addEventListener('keydown', onKeydown);
  }

  Drupal.behaviors.sahoDashboard = {
    attach: function (context) {
      once('saho-dashboard', '.saho-dashboard', context).forEach(function (root) {
        var settings = (drupalSettings && drupalSettings.sahoDashboard) || {};
        initReveals(root);
        initTooltips(root);
        if (settings.owner === true) {
          initTierCelebration(settings);
          initKonami(root);
        }
      });
    },
  };

})(Drupal, drupalSettings, once);
